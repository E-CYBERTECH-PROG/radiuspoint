<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Custom escalating lockout, not Laravel's default flat "N attempts / fixed cooldown" throttle:
 * 3 failed attempts locks the account out, and each subsequent lockout (without a successful
 * login in between) doubles the wait — 30s, 1m, 2m, 4m, and so on. The escalation "level" is
 * tracked separately from the per-cycle attempt count so it survives across multiple lockout
 * cycles (a real brute-force attempt keeps getting slower to retry), but resets completely the
 * moment a login actually succeeds, and also decays after 24h of no further failures so someone
 * who genuinely forgot their password once isn't permanently penalized weeks later.
 */
class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 3;

    private const BASE_LOCKOUT_SECONDS = 30;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // Always throws — either "N attempts remaining" or a fresh lockout.
            $this->registerFailedAttempt();
        }

        $this->clearThrottle();
    }

    /**
     * Ensure the login request is not currently locked out.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $lockedUntil = Cache::get($this->lockedUntilKey());

        if (! $lockedUntil || $lockedUntil <= time()) {
            return;
        }

        event(new Lockout($this));

        $seconds = $lockedUntil - time();

        // Flashed alongside the error message (not just baked into the text) so the login page
        // can freeze the submit button and email/password inputs with a real live countdown
        // instead of a static message the user could keep clicking "Log in" through — every one
        // of those clicks would just hit ensureIsNotRateLimited() again and re-fail, but there
        // was nothing stopping the click itself before this.
        session()->flash('login_locked_until', $lockedUntil);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * One more failed attempt — either surface how many tries are left this cycle, or (once
     * MAX_ATTEMPTS is hit) escalate to the next lockout duration and lock the account out.
     *
     * @throws ValidationException
     */
    private function registerFailedAttempt(): void
    {
        $attempts = (int) Cache::get($this->attemptsKey(), 0) + 1;
        Cache::put($this->attemptsKey(), $attempts, now()->addMinutes(10));

        if ($attempts < self::MAX_ATTEMPTS) {
            $remaining = self::MAX_ATTEMPTS - $attempts;

            throw ValidationException::withMessages([
                'email' => trans('auth.failed').' '.trans_choice(
                    'You have :count attempt remaining.|You have :count attempts remaining.',
                    $remaining,
                    ['count' => $remaining]
                ),
            ]);
        }

        // Hit the limit this cycle — escalate the lockout level (doubling each time) and reset
        // the attempt counter so the next cycle starts fresh at MAX_ATTEMPTS again.
        $level = (int) Cache::get($this->levelKey(), 0) + 1;
        Cache::put($this->levelKey(), $level, now()->addHours(24));

        $lockoutSeconds = self::BASE_LOCKOUT_SECONDS * (2 ** ($level - 1));
        $lockedUntil = time() + $lockoutSeconds;
        Cache::put($this->lockedUntilKey(), $lockedUntil, $lockoutSeconds);
        Cache::forget($this->attemptsKey());

        event(new Lockout($this));

        session()->flash('login_locked_until', $lockedUntil);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $lockoutSeconds,
                'minutes' => ceil($lockoutSeconds / 60),
            ]),
        ]);
    }

    /**
     * A real login clears everything — attempt count, lockout, and the escalation level.
     */
    private function clearThrottle(): void
    {
        Cache::forget($this->attemptsKey());
        Cache::forget($this->levelKey());
        Cache::forget($this->lockedUntilKey());
    }

    private function attemptsKey(): string
    {
        return 'login_throttle:'.$this->throttleKey().':attempts';
    }

    private function levelKey(): string
    {
        return 'login_throttle:'.$this->throttleKey().':level';
    }

    private function lockedUntilKey(): string
    {
        return 'login_throttle:'.$this->throttleKey().':locked_until';
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
