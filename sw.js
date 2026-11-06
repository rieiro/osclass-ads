// Osclass OLX Real-Time Chat Service Worker
self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function(event) {
    let payload = {
        title: 'Nova Mensagem no Chat - Osclass',
        body: 'Você recebeu uma nova mensagem sobre seu anúncio.',
        url: '/index.php?page=custom&action=olx_user_chat',
        icon: '/favicon.ico'
    };

    if (event.data) {
        try {
            const json = event.data.json();
            payload = Object.assign(payload, json);
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        icon: payload.icon || '/favicon.ico',
        badge: '/favicon.ico',
        tag: 'olx-chat-notification',
        renotify: true,
        vibrate: [200, 100, 200],
        data: {
            url: payload.url || '/index.php?page=custom&action=olx_user_chat'
        }
    };

    event.waitUntil(self.registration.showNotification(payload.title, options));
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : '/index.php?page=custom&action=olx_user_chat';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            for (let i = 0; i < clientList.length; i++) {
                let client = clientList[i];
                if (client.url && client.url.indexOf('olx_user_chat') !== -1 && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
