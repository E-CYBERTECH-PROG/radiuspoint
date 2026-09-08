// Reveal every money amount on the page (.rp-money-masked/.rp-money-value pairs) from one
// shared tap target — the masked figure on the dashboard hero card is itself the toggle, no
// separate eye icon. Deliberately not persisted to localStorage: every page load starts hidden
// again, since a "leave it revealed" choice surviving a reload defeats the point of a screen
// someone glances over your shoulder at not showing revenue by default.

document.addEventListener('DOMContentLoaded', function () {
    var toggles = document.querySelectorAll('.rp-money-toggle');

    toggles.forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.documentElement.classList.toggle('rp-money-revealed');
        });
    });
});
