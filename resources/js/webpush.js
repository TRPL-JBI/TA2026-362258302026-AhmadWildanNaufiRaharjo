import { csrfToken } from './patroli-api';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);

    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

export function vapidPublicKey() {
    return document.querySelector('meta[name="vapid-public-key"]')?.content?.trim() ?? '';
}

export function isWebPushSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
}

export function isWebPushConfigured() {
    return isWebPushSupported() && Boolean(vapidPublicKey());
}

async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        throw new Error('Gagal menyimpan subscription push.');
    }

    return response.json();
}

async function currentSubscription() {
    if (!isWebPushSupported()) {
        return null;
    }

    const registration = await navigator.serviceWorker.getRegistration('/sw.js');

    if (!registration) {
        return null;
    }

    return registration.pushManager.getSubscription();
}

/**
 * Minta izin notifikasi. Harus dipanggil dari user gesture (mis. klik Login).
 */
export async function requestNotificationPermission() {
    if (!isWebPushConfigured()) {
        return typeof Notification !== 'undefined' ? Notification.permission : 'denied';
    }

    if (Notification.permission !== 'default') {
        return Notification.permission;
    }

    return Notification.requestPermission();
}

/**
 * Daftarkan subscription jika izin sudah granted.
 */
export async function ensurePushSubscription(config = {}) {
    const subscribeUrl = config.subscribeUrl ?? '';

    if (!subscribeUrl || !isWebPushConfigured() || Notification.permission !== 'granted') {
        return false;
    }

    await navigator.serviceWorker.register('/sw.js');
    const registration = await navigator.serviceWorker.ready;
    const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];

    let subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey()),
        });
    }

    const json = subscription.toJSON();
    const keys = json.keys ?? {};

    await postJson(subscribeUrl, {
        endpoint: json.endpoint,
        key: keys.p256dh,
        token: keys.auth,
        encoding: contentEncoding,
    });

    return true;
}

export function registerWebPushLoginPrompt() {
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('login-form');

        if (!form || !isWebPushConfigured()) {
            return;
        }

        form.addEventListener('submit', async (event) => {
            if (form.dataset.webpushPrompted === '1') {
                return;
            }

            if (Notification.permission !== 'default') {
                return;
            }

            event.preventDefault();

            try {
                await requestNotificationPermission();
            } catch {
                // Lanjut login meski izin gagal diminta.
            }

            form.dataset.webpushPrompted = '1';
            form.submit();
        });
    });
}

export function registerWebPushAutoSubscribe() {
    document.addEventListener('DOMContentLoaded', () => {
        const configElement = document.getElementById('webpush-config');

        if (!configElement) {
            return;
        }

        let config = {};

        try {
            config = JSON.parse(configElement.textContent || '{}');
        } catch {
            return;
        }

        if (!config.enabled || !config.subscribeUrl) {
            return;
        }

        ensurePushSubscription(config).catch(() => {
            // Abaikan error subscribe otomatis agar app tetap berjalan.
        });
    });
}

export function registerWebPush() {
    registerWebPushLoginPrompt();
    registerWebPushAutoSubscribe();
}
