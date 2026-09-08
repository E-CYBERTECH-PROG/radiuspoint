// Hide/reveal every money amount on the page (.rp-money-masked/.rp-money-value pairs) from one
// shared tap target — hidden by default, no separate eye icon; the masked figure on the
// dashboard hero card is itself the toggle. State lives on <html> (applied synchronously in
// layouts/sidebar.blade.php's <head> script, same pattern as dark mode) so a returning user who
// chose to reveal amounts doesn't see a flash of masked ones first.

document.addEventListener('DOMContentLoaded', function () {
    var toggles = document.querySelectorAll('.rp-money-toggle');

    toggles.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var revealed = document.documentElement.classList.toggle('rp-money-revealed');
            localStorage.setItem('rp_hide_money', revealed ? '0' : '1');
        });
    });
});
