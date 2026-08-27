export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function firstValidationMessage(errors) {
    if (!errors || typeof errors !== 'object') {
        return null;
    }

    for (const messages of Object.values(errors)) {
        if (Array.isArray(messages) && messages[0]) {
            return String(messages[0]);
        }
    }

    return null;
}

export async function deleteJson(url) {
    const targetUrl = url.startsWith('http') ? url : new URL(url, window.location.origin).href;

    const response = await fetch(targetUrl, {
        method: 'DELETE',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const contentType = response.headers.get('content-type') ?? '';
    const data = contentType.includes('application/json')
        ? await response.json().catch(() => ({}))
        : {};

    if (!response.ok) {
        const message =
            firstValidationMessage(data.errors)
            ?? data.message
            ?? (response.status === 419
                ? 'Sesi habis. Muat ulang halaman lalu login kembali.'
                : 'Gagal menghapus data.');

        throw new Error(message);
    }

    return data;
}

export async function patchJson(url) {
    const targetUrl = url.startsWith('http') ? url : new URL(url, window.location.origin).href;

    const response = await fetch(targetUrl, {
        method: 'PATCH',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const contentType = response.headers.get('content-type') ?? '';
    const data = contentType.includes('application/json')
        ? await response.json().catch(() => ({}))
        : {};

    if (!response.ok) {
        const message =
            firstValidationMessage(data.errors)
            ?? data.message
            ?? (response.status === 419
                ? 'Sesi habis. Muat ulang halaman lalu login kembali.'
                : 'Permintaan gagal. Periksa data dan coba lagi.');

        throw new Error(message);
    }

    return data;
}

export async function postFormData(url, formData) {
    const targetUrl = url.startsWith('http') ? url : new URL(url, window.location.origin).href;

    const response = await fetch(targetUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: formData,
    });

    const contentType = response.headers.get('content-type') ?? '';
    const data = contentType.includes('application/json')
        ? await response.json().catch(() => ({}))
        : {};

    if (!response.ok) {
        const message =
            firstValidationMessage(data.errors)
            ?? data.message
            ?? (response.status === 419
                ? 'Sesi habis. Muat ulang halaman lalu login kembali.'
                : 'Permintaan gagal. Periksa data dan coba lagi.');

        throw new Error(message);
    }

    return data;
}

export async function postJson(url, body) {
    const targetUrl = url.startsWith('http') ? url : new URL(url, window.location.origin).href;

    const response = await fetch(targetUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    const contentType = response.headers.get('content-type') ?? '';
    const data = contentType.includes('application/json')
        ? await response.json().catch(() => ({}))
        : {};

    if (!response.ok) {
        const message =
            firstValidationMessage(data.errors)
            ?? data.message
            ?? (response.status === 419
                ? 'Sesi habis. Muat ulang halaman lalu login kembali.'
                : 'Permintaan gagal. Periksa data dan coba lagi.');

        throw new Error(message);
    }

    return data;
}

export async function resolveQr(url, payload, timing = null) {
    const body = { q: payload };

    if (timing && Number.isFinite(timing.scan_ms) && timing.scan_ms >= 0) {
        body.scan_ms = Math.round(timing.scan_ms);
    }

    return postJson(url, body);
}

/** @returns {'apar'|'lokasi'|null} */
export function parseQrPayloadType(raw) {
    const text = String(raw || '').trim();

    if (!text) {
        return null;
    }

    try {
        const parsed = JSON.parse(text);

        if (parsed && typeof parsed === 'object' && parsed.type) {
            const type = String(parsed.type).toLowerCase();

            if (type === 'apar' || type === 'lokasi') {
                return type;
            }
        }
    } catch {
        /* bukan JSON — lanjut deteksi kode inventaris */
    }

    if (/^APAR-/i.test(text)) {
        return 'apar';
    }

    if (/^(?:GED|LAB|RU|LOK)-/i.test(text)) {
        return 'lokasi';
    }

    return null;
}

export function appendQueryParam(href, key, value) {
    if (!href || value == null || value === '') {
        return href;
    }

    try {
        const u = new URL(href, window.location.origin);

        u.searchParams.set(key, value);

        return u.pathname + u.search + u.hash;
    } catch {
        const sep = href.includes('?') ? '&' : '?';

        return `${href}${sep}${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
    }
}
