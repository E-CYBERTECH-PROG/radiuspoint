import './bootstrap';
import '@tabler/icons-webfont/dist/tabler-icons.css';
import * as Tabler from '@tabler/core/dist/js/tabler.min.js';

// Tabler's bundle is UMD, and unbundled it would assign itself to window.tabler as a global —
// but Vite/Rollup's CommonJS interop intercepts that (it looks like a CJS module from the
// bundler's side) and captures its exports (Modal, Offcanvas, Collapse, Dropdown, …) into
// Vite's own module graph instead, so window.tabler never actually gets set at runtime. Every
// inline script across this app (confirm dialogs, toasts, the autoshow-on-validation-error
// offcanvas panels, this sidebar's mobile drawer, …) was written against a bare `bootstrap.X`
// global, which was silently undefined either way — capturing the namespace import directly
// (rather than reading the dead window.tabler side effect) is what actually has the classes.
window.bootstrap = Tabler.default || Tabler;

import './rp-shell';
import './rp-toasts';
import './rp-notifications';
import './rp-search';
