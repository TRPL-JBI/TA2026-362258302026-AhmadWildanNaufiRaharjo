self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data?.json() ?? {};
    } catch {
        payload = {
            title: 'Safety Patrol K3LH',
            body: event.data?.text() ?? '',
        };
    }

    const title = payload.title ?? 'Safety Patrol K3LH';
    const options = {
        body: payload.body ?? '',
        icon: payload.icon,
        badge: payload.badge,
        data: payload.data ?? {},
        tag: payload.tag,
        actions: payload.actions,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url ?? '/';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                for (const client of clientList) {
                    if (client.url.includes(targetUrl) && 'focus' in client) {
                        return client.focus();
                    }
                }

                if (self.clients.openWindow) {
                    return self.clients.openWindow(targetUrl);
                }

                return undefined;
            }),
    );
});
