// Live clock + date for the dashboard hero card (dashboard/partials/_oneisp-hero.blade.php) —
// replaced the static "Good morning/afternoon/evening" greeting, which kept saying "morning"
// on a dashboard tab left open into the afternoon since it was only ever computed once, server-side.

document.addEventListener('DOMContentLoaded', function () {
    var clockEl = document.getElementById('rp-hero-clock');
    var dateEl = document.getElementById('rp-hero-date');
    if (!clockEl || !dateEl) return;

    function tick() {
        var now = new Date();
        clockEl.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        // Short form (weekday + month + day) — this sits in a compact status chip now, not a
        // full headline, so "Tue, Sep 8" rather than "Tuesday, September 8, 2026".
        dateEl.textContent = now.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' });
    }

    tick();
    setInterval(tick, 1000);
});
