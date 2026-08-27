import { csrfToken } from './patroli-api';

function formatRelativeTime(isoString) {
    if (!isoString) {
        return '';
    }

    const date = new Date(isoString);
    const now = new Date();
    const diffMs = now - date;
    const diffMinutes = Math.floor(diffMs / 60000);

    if (diffMinutes < 1) {
        return 'Baru saja';
    }

    if (diffMinutes < 60) {
        return `${diffMinutes} menit lalu`;
    }

    const diffHours = Math.floor(diffMinutes / 60);
    if (diffHours < 24) {
        return `${diffHours} jam lalu`;
    }

    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) {
        return `${diffDays} hari lalu`;
    }

    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

async function postJson(url) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Gagal memproses notifikasi.');
    }

    return response.json();
}

export function registerNotifikasi() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('notificationBell', (config = {}) => ({
            indexUrl: config.indexUrl ?? '',
            readUrlTemplate: config.readUrlTemplate ?? '',
            readAllUrl: config.readAllUrl ?? '',
            enabled: Boolean(config.enabled),
            open: false,
            loading: false,
            items: [],
            unreadCount: 0,

            init() {
                if (!this.enabled) {
                    return;
                }

                this.fetchNotifications();
            },

            async fetchNotifications() {
                if (!this.indexUrl) {
                    return;
                }

                this.loading = true;

                try {
                    const response = await fetch(this.indexUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    this.items = (payload.data ?? []).map((item) => ({
                        ...item,
                        relativeTime: formatRelativeTime(item.created_at),
                    }));
                    this.unreadCount = payload.unread_count ?? 0;
                } catch {
                    // Abaikan error jaringan agar navbar tetap berfungsi.
                } finally {
                    this.loading = false;
                }
            },

            togglePanel() {
                this.open = !this.open;

                if (this.open) {
                    this.fetchNotifications();
                }
            },

            readUrlFor(id) {
                return this.readUrlTemplate.replace('__ID__', String(id));
            },

            async openNotification(item) {
                if (!item.is_read) {
                    try {
                        const payload = await postJson(this.readUrlFor(item.id));
                        item.is_read = true;
                        this.unreadCount = payload.unread_count ?? Math.max(0, this.unreadCount - 1);
                    } catch {
                        // Tetap navigasi meski gagal menandai dibaca.
                    }
                }

                this.open = false;

                if (item.url) {
                    window.location.href = item.url;
                }
            },

            async markAllRead() {
                if (this.unreadCount === 0) {
                    return;
                }

                try {
                    await postJson(this.readAllUrl);
                    this.items = this.items.map((item) => ({ ...item, is_read: true }));
                    this.unreadCount = 0;
                } catch {
                    // Diamkan error.
                }
            },
        }));
    });
}
