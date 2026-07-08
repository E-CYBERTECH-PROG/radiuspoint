// Must live at the site root (not /js/sw.js etc.) — a service worker's registration scope is
// limited to its own path and everything below it, and push notifications need to reach the
// whole app, not just one subdirectory.
self.addEventListener('push', function (event) {
    if (!event.data) return;

    const payload = event.data.json();
    const title = payload.title || 'RadiusPoint';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/favicon.svg',
        badge: payload.badge || '/favicon.svg',
        data: payload.data || {},
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/dashboard';
    event.waitUntil(clients.openWindow(url));
});
