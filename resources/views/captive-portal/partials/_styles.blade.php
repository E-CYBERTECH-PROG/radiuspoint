{{-- Base styles shared by every captive-portal template. Self-hosted only, no CDN assets.
     Every color is a custom-property token with a light default here — each template's own
     <style> block overrides the tokens it needs to repaint every shared component (card, modal,
     testimonial, action-card, inputs) without duplicating this file's ~200 lines 5 times. --}}
<style>
    :root {
        --brand: {{ $portal->primary_color ?? '#2563eb' }};
        --bg: #f3f4f6;
        --card-bg: #fff;
        --card-border: transparent;
        --card-radius: 18px;
        --card-shadow: 0 10px 30px rgba(0,0,0,.08);
        --text: #111827;
        --text-muted: #6b7280;
        --surface-2: #f9fafb;
        --border: #e5e7eb;
        --divider: #f3f4f6;
        --input-bg: #fff;
        --input-border: #e5e7eb;
        --modal-bg: #fff;
        --plan-radius: 14px;
        --btn-radius: 10px;
        --free-bg: #f0fdf4;
        --free-text: #15803d;
        --free-border: #bbf7d0;
        --spinner-track: #e5e7eb;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .card { width: 100%; max-width: 420px; background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--card-radius); box-shadow: var(--card-shadow); overflow: hidden; }
    .notice { background: #fffbeb; border-bottom: 1px solid #fde68a; padding: 10px 24px; }
    .notice .t { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #92400e; }
    .notice .b { font-size: 13px; color: #b45309; margin-top: 2px; }
    .body { padding: 22px 24px 20px; }
    .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-muted); margin: 0 0 10px; }
    .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 4px; }
    .plan { display: flex; flex-direction: column; position: relative; border: 1px solid var(--border); border-radius: var(--plan-radius); padding: 16px; transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; }
    .plan:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.08); border-color: var(--brand); }
    .plan .name { font-weight: 800; font-size: 14px; color: var(--text); }
    .plan .meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    .plan .tier { font-size: 10px; color: var(--brand); margin-top: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    {{-- Package-theme-only flourishes — invisible by default, unhidden and styled only by the
         one template that wants them, so this shared partial never forks per theme. --}}
    .plan .ribbon { display: none; }
    .plan .box-icon { display: none; }
    .testimonials { padding: 4px 24px 20px; }
    .testimonial { background: var(--surface-2); border-radius: 12px; padding: 12px 14px; margin-bottom: 8px; }
    .testimonial .quote { font-size: 13px; color: var(--text); font-style: italic; opacity: .85; }
    .testimonial .author { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
    .plan .price { font-weight: 800; color: var(--brand); font-size: 20px; margin-top: 12px; }
    .plan .btn-buy { width: 100%; margin-top: 10px; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: none; border-radius: var(--btn-radius); font-weight: 700; font-size: 13px; padding: 10px 16px; cursor: pointer; transition: opacity .15s; }
    .btn:disabled { opacity: .5; cursor: default; }
    .btn:not(:disabled):hover { opacity: .9; }
    .btn-brand { background: var(--brand); color: #fff; }
    .btn-outline { background: var(--surface-2); color: var(--text); border: 1px solid var(--border); }
    .btn-ghost { background: none; color: var(--brand); border: none; padding: 8px; }
    .btn-block { width: 100%; padding: 13px; font-size: 14px; }
    .btn-free { background: var(--free-bg); color: var(--free-text); border: 1px solid var(--free-border); }
    .actions-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .action-card { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 12px 6px; cursor: pointer; transition: transform .15s, box-shadow .15s, border-color .15s; text-align: center; }
    .action-card:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.06); border-color: var(--brand); }
    .action-card-icon { width: 26px; height: 26px; color: var(--brand); }
    .action-card-icon svg { width: 100%; height: 100%; }
    .action-card-label { font-size: 11px; font-weight: 700; color: var(--text); line-height: 1.25; }
    .divider { border-top: 1px solid var(--divider); margin: 18px 0; }
    .empty { text-align: center; color: var(--text-muted); font-size: 13px; padding: 20px 0; }
    .footer { padding: 14px 24px; border-top: 1px solid var(--divider); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .footer .item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); }
    .footer .item svg { flex-shrink: 0; }
    .footer a { color: inherit; text-decoration: none; }

    .modal-overlay { position: fixed; inset: 0; background: rgba(17,24,39,.55); display: none; align-items: center; justify-content: center; padding: 16px; z-index: 50; }
    .modal-overlay.open { display: flex; }
    .modal { background: var(--modal-bg); border: 1px solid var(--card-border); border-radius: 16px; width: 100%; max-width: 380px; padding: 24px; text-align: center; color: var(--text); }
    .modal .icon { width: 44px; height: 44px; margin: 0 auto 12px; }
    .modal h2 { font-size: 17px; margin: 0 0 6px; color: var(--text); }
    .modal p.sub { font-size: 13px; color: var(--text-muted); margin: 0 0 18px; }
    .modal input[type="tel"], .modal input[type="text"], .modal textarea { width: 100%; background: var(--input-bg); color: var(--text); border: 1px solid var(--input-border); border-radius: 10px; padding: 12px 14px; font-size: 15px; outline: none; text-align: center; margin-bottom: 14px; }
    .modal input:focus { border-color: var(--brand); }
    .modal .error-text { color: #dc2626; font-size: 13px; margin: 0 0 12px; }
    .modal .creds { background: var(--surface-2); border-radius: 10px; padding: 14px; text-align: left; margin-bottom: 16px; }
    .modal .creds .k { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); }
    .modal .creds .v { font-weight: 700; font-family: ui-monospace, monospace; font-size: 14px; color: var(--text); margin: 0 0 8px; }
    .modal .close-link { display: block; margin-top: 14px; font-size: 13px; color: var(--text-muted); background: none; border: none; cursor: pointer; }
    .spin { animation: rp-spin 0.8s linear infinite; }
    @keyframes rp-spin { to { transform: rotate(360deg); } }

    {{-- Splash overlay shell — shared positioning/transition, each template supplies its own
         background and loading animation (see the template's own <style> block). --}}
    .rp-splash { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity .35s ease, visibility .35s ease; }
    .rp-splash.hide { opacity: 0; visibility: hidden; pointer-events: none; }

    {{-- Optional slim full-width navbar (see routers/show.blade.php's "Show top navbar"
         toggle). Fixed positioning keeps it out of body's flex-centering flow entirely, so
         .card still centers correctly whether the navbar is present or not — only the
         body.has-navbar padding below reserves clearance so it never overlaps the card. --}}
    .rp-navbar { position: fixed; top: 0; left: 0; right: 0; height: 52px; z-index: 30; background: var(--card-bg); border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; padding: 0 20px; }
    .rp-navbar img { height: 26px; object-fit: contain; }
    .rp-navbar .rp-navbar-name { font-weight: 800; font-size: 14px; color: var(--text); }
    body.has-navbar { padding-top: 64px; }
</style>
