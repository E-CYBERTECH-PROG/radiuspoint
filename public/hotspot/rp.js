(function () {
    'use strict';

    // Sample data, inline — a fetch('packages.json') call is blocked by the browser's own
    // CORS rules when this page is opened straight from disk (file://) instead of through a
    // web server, which is exactly how a skin like this usually gets previewed. Swap this out
    // for a real fetch() once there's an actual endpoint to call.
    var SAMPLE_DATA = {
        business: {
            name: 'Cosmas Limited',
            phone: '0793073080'
        },
        packages: [
            { label: '2HOURS', price: 10 },
            { label: '12HOURS', price: 20 },
            { label: '1DAY', price: 30 },
            { label: '1WEEK', price: 140 }
        ],
        announcements: [
            'Welcome! Buy any package below to get connected.',
            'Weekly plan now KES 140 — best value for heavy use.',
            'Having trouble? Call or text us any time.'
        ]
    };

    var PHONE_PATTERN = /^(?:254|0)7\d{8}$/;

    /* ===== THEME ===== */
    (function initTheme() {
        var root = document.documentElement;
        var toggle = document.getElementById('rp-theme-toggle');
        var stored = null;
        try { stored = localStorage.getItem('rp_hotspot_theme'); } catch (e) {}

        if (stored === 'dark' || stored === 'light') {
            root.setAttribute('data-theme', stored);
        }

        toggle.addEventListener('click', function () {
            var isDark = root.getAttribute('data-theme') === 'dark'
                || (!root.getAttribute('data-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
            var next = isDark ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try { localStorage.setItem('rp_hotspot_theme', next); } catch (e) {}
        });
    })();

    /* ===== DEVICE LINE ===== */
    // RouterOS supplies these as query params on the real login flow — same convention this
    // app's other captive-portal pages already use ($(mac)/$(ip) template variables). The whole
    // "This Device" tile is hidden on a plain preview with neither.
    var params = new URLSearchParams(window.location.search);
    var mac = params.get('mac');
    var ip = params.get('ip');
    var deviceLine = document.getElementById('rp-device-line');
    if (mac || ip) {
        deviceLine.textContent = [mac, ip].filter(Boolean).join(' ');
    } else {
        document.getElementById('rp-device-item').remove();
    }

    /* ===== BUSINESS + PACKAGES + ANNOUNCEMENTS ===== */
    renderBusiness(SAMPLE_DATA.business);
    renderPackages(SAMPLE_DATA.packages);
    startAnnouncements(SAMPLE_DATA.announcements);

    function startAnnouncements(announcements) {
        var bar = document.getElementById('rp-announcement-bar');
        var textEl = document.getElementById('rp-announcement-text');

        if (!announcements || announcements.length === 0) {
            bar.remove();
            return;
        }

        var i = 0;
        textEl.textContent = announcements[0];

        if (announcements.length === 1) return;

        setInterval(function () {
            textEl.classList.add('is-fading');
            setTimeout(function () {
                i = (i + 1) % announcements.length;
                textEl.textContent = announcements[i];
                textEl.classList.remove('is-fading');
            }, 350);
        }, 4500);
    }

    function renderBusiness(business) {
        if (!business) return;
        document.getElementById('rp-business-name').textContent = business.name || 'WiFi Hotspot';
        document.getElementById('rp-business-phone').textContent = business.phone || '—';
    }

    function renderPackages(packages) {
        var list = document.getElementById('rp-packages-list');
        list.innerHTML = '';

        if (!packages || packages.length === 0) {
            list.innerHTML = '<p class="empty-text">No packages available right now.</p>';
            return;
        }

        var popularIndex = packages.length >= 3 ? Math.floor(packages.length / 2) : -1;

        packages.forEach(function (pkg, i) {
            var isPopular = i === popularIndex;

            var card = document.createElement('div');
            card.className = 'package-card' + (isPopular ? ' is-popular' : '');

            var icon = document.createElement('div');
            icon.className = 'package-icon';
            icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none"><path d="M4 9.5a12 12 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7 13.2a7.2 7.2 0 0 1 10 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="17.3" r="1.5" fill="currentColor"/></svg>';

            var main = document.createElement('div');
            main.className = 'package-main';

            var topRow = document.createElement('div');
            topRow.className = 'package-top-row';
            var label = document.createElement('span');
            label.className = 'package-label';
            label.textContent = pkg.label;
            topRow.appendChild(label);
            if (isPopular) {
                var badge = document.createElement('span');
                badge.className = 'package-badge';
                badge.textContent = 'Popular';
                topRow.appendChild(badge);
            }

            var price = document.createElement('div');
            price.className = 'package-price';
            price.innerHTML = 'KES <strong>' + pkg.price + '</strong>';

            main.appendChild(topRow);
            main.appendChild(price);

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn-buy';
            btn.textContent = 'Buy';
            btn.addEventListener('click', function () { openPay(pkg); });

            card.appendChild(icon);
            card.appendChild(main);
            card.appendChild(btn);
            list.appendChild(card);
        });
    }

    /* ===== PAYMENT MODAL ===== */
    var payOverlay = document.getElementById('rp-pay-overlay');
    var paySheet = payOverlay.querySelector('.pay-sheet');
    var payPhoneInput = document.getElementById('rp-pay-phone');
    var payError = document.getElementById('rp-pay-error');
    var paySteps = {
        phone: document.getElementById('rp-pay-step-phone'),
        waiting: document.getElementById('rp-pay-step-waiting'),
        notice: document.getElementById('rp-pay-step-notice')
    };
    var payTimer = null;

    function showPayStep(name) {
        Object.keys(paySteps).forEach(function (key) {
            paySteps[key].style.display = key === name ? 'block' : 'none';
        });
    }

    function openPay(pkg) {
        document.getElementById('rp-pay-plan-name').textContent = pkg.label;
        document.getElementById('rp-pay-plan-price').textContent = 'KES ' + pkg.price;
        payPhoneInput.value = '';
        payError.textContent = '';
        showPayStep('phone');
        payOverlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        setTimeout(function () { payPhoneInput.focus(); }, 250);
    }

    function closePay() {
        payOverlay.classList.remove('is-open');
        document.body.style.overflow = '';
        if (payTimer) { clearTimeout(payTimer); payTimer = null; }
    }

    document.getElementById('rp-pay-close').addEventListener('click', closePay);
    payOverlay.addEventListener('click', function (e) {
        if (e.target === payOverlay) closePay();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && payOverlay.classList.contains('is-open')) closePay();
    });
    // Prevent a tap inside the sheet from bubbling to the overlay and closing it.
    paySheet.addEventListener('click', function (e) { e.stopPropagation(); });

    document.getElementById('rp-pay-submit').addEventListener('click', function () {
        var phone = payPhoneInput.value.trim();

        if (!PHONE_PATTERN.test(phone)) {
            payError.textContent = 'Enter a valid phone number (e.g. 0712345678).';
            return;
        }
        payError.textContent = '';

        document.getElementById('rp-pay-waiting-phone').textContent = phone;
        showPayStep('waiting');

        // Simulated STK-push wait — payment isn't wired up on this preview page yet, so this
        // resolves to an honest notice rather than a fabricated success state.
        payTimer = setTimeout(function () {
            showPayStep('notice');
        }, 1800);
    });

    document.getElementById('rp-pay-back').addEventListener('click', function () {
        showPayStep('phone');
    });

    /* ===== M-PESA MESSAGE VERIFY (honest placeholder) ===== */
    var searchForm = document.getElementById('rp-search-form');
    var searchFeedback = document.getElementById('rp-search-feedback');
    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var value = document.getElementById('rp-search-input').value.trim();

        if (!value) {
            searchFeedback.textContent = 'Paste the M-Pesa confirmation message you received.';
            searchFeedback.className = 'search-feedback is-error';
            return;
        }

        searchFeedback.textContent = "Manual verification isn't available on this preview page yet.";
        searchFeedback.className = 'search-feedback';
    });
})();
