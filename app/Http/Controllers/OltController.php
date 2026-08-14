<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\OltTerminalLog;
use App\Services\OltSshService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * OLT (GPON/EPON) remote access — see OltSshService's docblock for why this is a raw terminal
 * console rather than parsed ONU-list/provisioning screens (no verified CLI command reference for
 * VSOL/Hioso yet). Structurally mirrors RouterController's terminal feature.
 */
class OltController extends Controller
{
    public function index()
    {
        $olts = Olt::latest()->paginate(20);

        return view('olts.index', compact('olts'));
    }

    public function create()
    {
        return view('olts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'required|in:vsol,hioso,other',
            'ip_address' => 'required|string|max:255',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'pon_ports' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $olt = Olt::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'brand' => $request->brand,
            'ip_address' => $request->ip_address,
            'ssh_port' => $request->ssh_port ?: 22,
            'username' => $request->username,
            'password' => $request->password,
            'pon_ports' => $request->pon_ports,
            'notes' => $request->notes,
        ]);

        return redirect()->route('olts.show', $olt)->with('success', 'OLT added. Use "Test Connection" to confirm SSH reachability.');
    }

    public function show(Olt $olt)
    {
        return view('olts.show', compact('olt'));
    }

    public function destroy(Olt $olt)
    {
        $olt->delete();

        return redirect()->route('olts.index')->with('success', 'OLT removed.');
    }

    /**
     * Confirms SSH reachability only — opens a session, logs in, reads whatever banner/prompt
     * comes back. Deliberately does not attempt to parse system info (e.g. "show version") since
     * the exact command name/output format isn't confirmed per brand/model yet.
     */
    public function testConnection(Olt $olt)
    {
        $ssh = new OltSshService();

        $connected = $ssh->connect($olt->ip_address, $olt->ssh_port, $olt->username, $olt->password);

        if (! $connected) {
            $olt->update(['status' => 'offline']);

            return response()->json(['status' => 'offline', 'message' => 'SSH login failed — check IP, port, and credentials.'], 400);
        }

        $olt->update(['status' => 'active', 'last_seen' => now()]);
        $ssh->disconnect();

        return response()->json(['status' => 'active', 'message' => 'SSH login succeeded.']);
    }

    public function terminalHistory(Olt $olt)
    {
        $logs = OltTerminalLog::with('user')
            ->where('olt_id', $olt->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'command' => $log->command,
                'result' => $log->result,
                'success' => $log->success,
                'user' => $log->user->name ?? 'Unknown',
                'at' => $log->created_at->diffForHumans(),
            ]);

        return response()->json(['data' => $logs]);
    }

    /**
     * Runs exactly what the operator typed against the OLT's real CLI over SSH — this is a raw
     * terminal, not a structured API call, so there is no command allow/deny-list the way
     * RouterController's RouterOS console has one (that list works because RouterOS commands are
     * structured API paths; a text CLI has no equivalent structural hook to filter on). Treat
     * this like handing someone real SSH access, because that's what it is.
     */
    public function terminalExecute(Request $request, Olt $olt)
    {
        $command = trim((string) $request->input('command'));

        if (! $command) {
            return response()->json(['error' => 'No command entered.'], 422);
        }

        $ssh = new OltSshService();

        try {
            if (! $ssh->connect($olt->ip_address, $olt->ssh_port, $olt->username, $olt->password)) {
                $message = 'Could not open an SSH session to this OLT.';
                OltTerminalLog::record(Auth::user(), $olt, $command, $message, false);

                return response()->json(['error' => $message], 400);
            }

            $result = $ssh->exec($command);
            $ssh->disconnect();

            OltTerminalLog::record(Auth::user(), $olt, $command, $result, true);

            return response()->json(['result' => $result]);
        } catch (Throwable $e) {
            $message = 'Command failed: '.$e->getMessage();
            OltTerminalLog::record(Auth::user(), $olt, $command, $message, false);

            return response()->json(['error' => $message], 500);
        }
    }
}
