// Hide/reveal every money amount on the page (.rp-money) from one shared switch — a shoulder-
// surfing admin's screen shouldn't broadcast revenue figures by default. State lives on <html>
// (applied synchronously in layouts/sidebar.blade.php's <head> script, same pattern as dark
// mode) so a returning user with amounts hidden never sees a flash of the real numbers.

document.addEventListener('DOMContentLoaded', function () {
    var toggles = document.querySelectorAll('.rp-money-toggle');

    toggles.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var hidden = document.documentElement.classList.toggle('rp-money-hidden');
            localStorage.setItem('rp_hide_money', hidden ? '1' : '0');
        });
    });
});
