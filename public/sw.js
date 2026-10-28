/* Paradigm Cargo CRM service worker: push notifications only (no offline cache of private data). */
self.addEventListener('push', (event) => {
  let d = {};
  try { d = event.data ? event.data.json() : {}; } catch (e) { d = { body: event.data && event.data.text() }; }
  event.waitUntil(self.registration.showNotification(d.title || 'Paradigm Cargo CRM', {
    body: d.body || '',
    icon: d.icon || '/icons/icon-192.png',
    badge: '/icons/icon-192.png',
    data: d.data || {},
    dir: 'auto',
  }));
});
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/notifications';
  event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
    for (const c of list) { if (new URL(c.url).origin === self.location.origin && 'focus' in c) { c.navigate(url); return c.focus(); } }
    return clients.openWindow(url);
  }));
});
