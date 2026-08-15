{{-- Base styles shared by every captive-portal template. Self-hosted only, no CDN assets. --}}
<style>
    :root { --brand: {{ $portal->primary_color ?? '#2563eb' }}; }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: #f3f4f6;
        color: #111827;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .card { width: 100%; max-width: 420px; background: #fff; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.08); overflow: hidden; }
    .notice { background: #fffbeb; border-bottom: 1px solid #fde68a; padding: 10px 24px; }
    .notice .t { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #92400e; }
    .notice .b { font-size: 13px; color: #b45309; margin-top: 2px; }
    .body { padding: 22px 24px 20px; }
    .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; margin: 0 0 10px; }
    .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 4px; }
    .plan { display: flex; flex-direction: column; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; }
    .plan:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.08); border-color: var(--brand); }
    .plan .name { font-weight: 800; font-size: 14px; color: #111827; }
    .plan .meta { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .plan .tier { font-size: 10px; color: var(--brand); margin-top: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .testimonials { padding: 4px 24px 20px; }
    .testimonial { background: #f9fafb; border-radius: 12px; padding: 12px 14px; margin-bottom: 8px; }
    .testimonial .quote { font-size: 13px; color: #374151; font-style: italic; }
    .testimonial .author { font-size: 11px; color: #9ca3af; margin-top: 4px; }
    .plan .price { font-weight: 800; color: var(--brand); font-size: 20px; margin-top: 12px; }
    .plan .btn-buy { width: 100%; margin-top: 10px; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: none; border-radius: 10px; font-weight: 700; font-size: 13px; padding: 10px 16px; cursor: pointer; transition: opacity .15s; }
    .btn:disabled { opacity: .5; cursor: default; }
    .btn:not(:disabled):hover { opacity: .9; }
    .btn-brand { background: var(--brand); color: #fff; }
    .btn-outline { background: #f9fafb; color: #374151; border: 1px solid #e5e7eb; }
    .btn-ghost { background: none; color: var(--brand); border: none; padding: 8px; }
    .btn-block { width: 100%; padding: 13px; font-size: 14px; }
    .actions-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .action-card { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 6px; cursor: pointer; transition: transform .15s, box-shadow .15s, border-color .15s; text-align: center; }
    .action-card:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.06); border-color: var(--brand); }
    .action-card-icon { width: 26px; height: 26px; color: var(--brand); }
    .action-card-icon svg { width: 100%; height: 100%; }
    .action-card-label { font-size: 11px; font-weight: 700; color: #374151; line-height: 1.25; }
    .divider { border-top: 1px solid #f3f4f6; margin: 18px 0; }
    .empty { text-align: center; color: #6b7280; font-size: 13px; padding: 20px 0; }
    .footer { padding: 14px 24px; border-top: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .footer .item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280; }
    .footer .item svg { flex-shrink: 0; }
    .footer a { color: inherit; text-decoration: none; }

    .modal-overlay { position: fixed; inset: 0; background: rgba(17,24,39,.55); display: none; align-items: center; justify-content: center; padding: 16px; z-index: 50; }
    .modal-overlay.open { display: flex; }
    .modal { background: #fff; border-radius: 16px; width: 100%; max-width: 380px; padding: 24px; text-align: center; }
    .modal .icon { width: 44px; height: 44px; margin: 0 auto 12px; }
    .modal h2 { font-size: 17px; margin: 0 0 6px; }
    .modal p.sub { font-size: 13px; color: #6b7280; margin: 0 0 18px; }
    .modal input[type="tel"], .modal input[type="text"] { width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; font-size: 15px; outline: none; text-align: center; margin-bottom: 14px; }
    .modal input:focus { border-color: var(--brand); }
    .modal .error-text { color: #dc2626; font-size: 13px; margin: 0 0 12px; }
    .modal .creds { background: #f9fafb; border-radius: 10px; padding: 14px; text-align: left; margin-bottom: 16px; }
    .modal .creds .k { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #9ca3af; }
    .modal .creds .v { font-weight: 700; font-family: ui-monospace, monospace; font-size: 14px; color: #111827; margin: 0 0 8px; }
    .modal .close-link { display: block; margin-top: 14px; font-size: 13px; color: #6b7280; background: none; border: none; cursor: pointer; }
    .spin { animation: rp-spin 0.8s linear infinite; }
    @keyframes rp-spin { to { transform: rotate(360deg); } }

    {{-- Splash overlay shell — shared positioning/transition, each template supplies its own
         background and loading animation (see the template's own <style> block). --}}
    .rp-splash { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity .35s ease, visibility .35s ease; }
    .rp-splash.hide { opacity: 0; visibility: hidden; pointer-events: none; }
</style>
