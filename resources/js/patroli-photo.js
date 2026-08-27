import { uiAlert } from './ui-dialog';

const ACCEPT = 'image/jpeg,image/png,image/webp,image/heic,image/heif';
const MAX_FILE_BYTES = 10 * 1024 * 1024;
const MAX_PHOTOS_DEFAULT = 5;

let activeInput = null;

function ensureInput({ capture = false, multiple = false }) {
    if (activeInput) {
        activeInput.remove();
    }

    const input = document.createElement('input');
    input.type = 'file';
    input.accept = ACCEPT;
    input.multiple = multiple;
    input.className = 'sr-only';
    input.setAttribute('aria-hidden', 'true');
    input.tabIndex = -1;

    if (capture) {
        input.setAttribute('capture', 'environment');
    }

    document.body.appendChild(input);
    activeInput = input;

    return input;
}

function readPreview(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result ?? ''));
        reader.onerror = () => reject(new Error('Gagal membaca foto.'));
        reader.readAsDataURL(file);
    });
}

function validImageFile(file) {
    if (!file || !file.type.startsWith('image/')) {
        return 'File harus berupa gambar (JPG, PNG, atau WebP).';
    }

    if (file.size > MAX_FILE_BYTES) {
        return 'Ukuran foto maksimal 10 MB.';
    }

    return null;
}

/**
 * Buka pemilih foto: kamera (capture) atau galeri.
 */
export function pickPhotos({ capture = false, multiple = false, onSelected }) {
    const input = ensureInput({ capture, multiple });

    input.onchange = async () => {
        const files = Array.from(input.files ?? []);

        input.remove();
        activeInput = null;

        if (files.length === 0) {
            return;
        }

        try {
            await onSelected(files);
        } catch (error) {
            await uiAlert(error?.message ?? 'Gagal memproses foto.');
        }
    };

    input.click();
}

/**
 * @param {File[]} files
 * @param {{ id: string, preview: string, file: File }[]} existing
 */
export async function appendPhotoEntries(files, existing = [], { maxPhotos = MAX_PHOTOS_DEFAULT } = {}) {
    const list = Array.isArray(existing) ? [...existing] : [];
    const slots = maxPhotos - list.length;

    if (slots <= 0) {
        throw new Error(`Maksimal ${maxPhotos} foto.`);
    }

    const added = [];

    for (const file of files.slice(0, slots)) {
        const error = validImageFile(file);

        if (error) {
            throw new Error(error);
        }

        const preview = await readPreview(file);

        added.push({
            id: `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
            preview,
            file,
        });
    }

    return [...list, ...added];
}

/** Hilangkan File agar draft sessionStorage tidak error; preview base64 tetap disimpan. */
export function stripPhotoFilesForDraft(data) {
    if (Array.isArray(data)) {
        return data.map(stripPhotoFilesForDraft);
    }

    if (data && typeof data === 'object') {
        if (data.file instanceof File) {
            const { file: _f, ...rest } = data;

            return rest;
        }

        return Object.fromEntries(
            Object.entries(data).map(([key, value]) => [key, stripPhotoFilesForDraft(value)]),
        );
    }

    return data;
}

export function hasPhotoFiles(photos) {
    if (!Array.isArray(photos) || photos.length === 0) {
        return false;
    }

    return photos.some((p) => p?.file instanceof File);
}

export function hasPhotoDocumentation(photos) {
    if (!Array.isArray(photos) || photos.length === 0) {
        return false;
    }

    return photos.some((p) => p?.file instanceof File || p?.existing);
}

/** Bangun kembali File dari preview data URL (draft sessionStorage). */
export function fileFromDataUrl(dataUrl, filename = 'photo.jpg') {
    const match = String(dataUrl).match(/^data:([^;]+);base64,(.+)$/);

    if (!match) {
        throw new Error('Preview foto tidak valid.');
    }

    const mime = match[1];
    const binary = atob(match[2]);
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
    }

    const ext = mime.split('/')[1]?.replace('jpeg', 'jpg') ?? 'jpg';
    const name = filename.includes('.') ? filename : `${filename}.${ext}`;

    return new File([bytes], name, { type: mime });
}

/** Pulihkan objek foto draft agar validasi & upload simpan tetap jalan setelah reload halaman. */
export function restoreDraftPhotoEntry(photo) {
    if (!photo || typeof photo !== 'object') {
        return photo;
    }

    if (photo.file instanceof File) {
        return photo;
    }

    const preview = String(photo.preview ?? '');

    if (!preview.startsWith('data:')) {
        return photo;
    }

    try {
        return {
            ...photo,
            file: fileFromDataUrl(preview, `patroli-${photo.id ?? Date.now()}`),
        };
    } catch {
        return { ...photo, preview: '' };
    }
}

export function restoreDraftPhotoList(photos) {
    if (!Array.isArray(photos)) {
        return [];
    }

    return photos.map(restoreDraftPhotoEntry);
}
