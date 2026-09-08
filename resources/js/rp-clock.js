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
        dateEl.textContent = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    tick();
    setInterval(tick, 1000);
});
