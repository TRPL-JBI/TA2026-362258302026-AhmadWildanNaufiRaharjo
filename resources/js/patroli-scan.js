import { Html5Qrcode } from 'html5-qrcode';
import { appendQueryParam, parseQrPayloadType, resolveQr } from './patroli-api';
import { clearAparDraft, clearPatroliDrafts, clearTemuanDraft } from './patroli-draft';

const RE_URL = /^https?:\/\//i;

const UNKNOWN_QR_MESSAGE = 'QR tidak dikenali. Pastikan memindai QR lokasi atau APAR dari inventaris.';

/** Pilih kamera belakang jika ada. */
function preferredCameraIndex(cameras) {
    if (!cameras?.length) {
        return -1;
    }
    const i = cameras.findIndex((c) => /back|belakang|rear|environment|wide/i.test(c.label || ''));

    return i >= 0 ? i : 0;
}

export function registerPatroliScan() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('patroliScanPage', (opts = {}) => ({
            scanner: null,
            scannerStartedAt: null,
            /** @type {{ id: string; label: string }[]} */
            cameras: [],
            cameraIndex: 0,
            torchOn: false,
            torchSupported: false,
            scanError: null,
            pageAlert: null,
            resolving: false,
            initializing: true,
            temuanHref: typeof opts.temuanHref === 'string' ? opts.temuanHref : '',
            aparHref: typeof opts.aparHref === 'string' ? opts.aparHref : '',
            resolveUrl: typeof opts.resolveUrl === 'string' ? opts.resolveUrl : '',
            scanType: ['temuan', 'apar'].includes(opts.scanType) ? opts.scanType : 'umum',
            continuePatrol: Boolean(opts.continuePatrol),
            manualItems: Array.isArray(opts.manualItems) ? opts.manualItems : [],
            manualModalOpen: false,
            manualQuery: '',

            initScanSession() {
                if (this.continuePatrol) {
                    return;
                }

                if (this.scanType === 'temuan') {
                    clearTemuanDraft();
                } else if (this.scanType === 'apar') {
                    clearAparDraft();
                } else {
                    clearPatroliDrafts();
                }
            },

            get canFlipCamera() {
                return Array.isArray(this.cameras) && this.cameras.length > 1;
            },

            targetHref(decodedText) {
                const qrType = parseQrPayloadType(decodedText);

                if (qrType === 'apar' && this.aparHref) {
                    return this.aparHref;
                }

                if (qrType === 'lokasi' && this.temuanHref) {
                    return this.temuanHref;
                }

                return '';
            },

            manualTitle() {
                if (this.scanType === 'apar') {
                    return 'Pilih APAR Manual';
                }
                if (this.scanType === 'temuan') {
                    return 'Pilih Lokasi Manual';
                }

                return 'Pilih Manual';
            },

            manualDescription() {
                if (this.scanType === 'apar') {
                    return 'Pilih unit APAR dari inventaris untuk dimasukkan ke patroli.';
                }
                if (this.scanType === 'temuan') {
                    return 'Pilih lokasi yang akan ditambahkan ke checklist patroli.';
                }

                return 'Pilih lokasi untuk checklist inspeksi atau unit APAR untuk pemeriksaan.';
            },

            manualButtonLabel() {
                if (this.scanType === 'apar') {
                    return 'Pilih APAR Manual';
                }
                if (this.scanType === 'temuan') {
                    return 'Input Manual Lokasi';
                }

                return 'Input Manual';
            },

            manualSearchPlaceholder() {
                if (this.scanType === 'apar') {
                    return 'Cari kode APAR, lokasi, atau jenis...';
                }
                if (this.scanType === 'temuan') {
                    return 'Cari nama gedung, lab, atau area...';
                }

                return 'Cari lokasi, kode APAR, atau jenis...';
            },

            itemKind(item) {
                if (item?.kind === 'apar' || item?.kind === 'lokasi') {
                    return item.kind;
                }

                const type = item?.payload?.type;

                return type === 'apar' ? 'apar' : 'lokasi';
            },

            manualItemKey(item) {
                return `${this.itemKind(item)}-${item.label}`;
            },

            filteredManualItems() {
                const q = (this.manualQuery || '').toLowerCase().trim();

                if (!q) {
                    return this.manualItems;
                }

                return this.manualItems.filter((item) =>
                    [item.label, item.subLabel, item.kodeApar, item.lokasiApar, item.nama, item.jenisKapasitas]
                        .filter(Boolean)
                        .join(' ')
                        .toLowerCase()
                        .includes(q),
                );
            },

            openManualModal() {
                this.manualQuery = '';
                this.manualModalOpen = true;

                if (this.scanner?.isScanning) {
                    try {
                        this.scanner.pause(true);
                    } catch {
                        /* ignore */
                    }
                }
            },

            closeManualModal() {
                this.manualModalOpen = false;

                if (this.scanner?.isScanning) {
                    try {
                        this.scanner.resume();
                    } catch {
                        /* ignore */
                    }
                }
            },

            async initScanner() {
                this.initScanSession();
                this.scanError = null;
                this.initializing = true;

                try {
                    this.cameras = await Html5Qrcode.getCameras();
                    this.cameraIndex = preferredCameraIndex(this.cameras);
                    await this.mountAndStart();
                } catch (e) {
                    this.scanError =
                        typeof e?.message === 'string'
                            ? e.message
                            : 'Tidak dapat mengakses kamera. Izinkan izin kamera di peramban Anda.';
                    this.initializing = false;
                }
            },

            async mountAndStart() {
                await this.teardownScanner();

                this.scanner = new Html5Qrcode('patroli-qr-reader', {
                    verbose: false,
                    formatsToSupport: undefined,
                });

                const config = {
                    fps: 10,
                    aspectRatio: 4 / 3,
                    qrbox: (viewfinderWidth, viewfinderHeight) => {
                        const m = Math.min(viewfinderWidth, viewfinderHeight);
                        const edge = Math.max(140, Math.min(280, Math.floor(m * 0.72)));

                        return { width: edge, height: edge };
                    },
                };

                const onOk = (decodedText) => {
                    this.handleDecoded(decodedText);
                };

                const onFail = () => {};

                try {
                    this.scannerStartedAt = performance.now();

                    if (this.cameras.length > 0) {
                        const id = this.cameras[this.cameraIndex].id;

                        await this.scanner.start(id, config, onOk, onFail);
                    } else {
                        await this.scanner.start({ facingMode: 'environment' }, config, onOk, onFail);
                    }

                    this.refreshTorchCapability();
                    this.scanError = null;
                } catch (e) {
                    this.scanError =
                        typeof e?.message === 'string'
                            ? e.message
                            : 'Gagal memulai pratinjau kamera.';
                    await this.teardownScanner();
                } finally {
                    this.initializing = false;
                }
            },

            refreshTorchCapability() {
                this.torchOn = false;
                this.torchSupported = false;

                if (!this.scanner?.isScanning) {
                    return;
                }

                try {
                    const caps = this.scanner.getRunningTrackCameraCapabilities();

                    if (caps?.torchFeature()?.isSupported()) {
                        this.torchSupported = true;
                    }
                } catch {
                    this.torchSupported = false;
                }
            },

            async teardownScanner() {
                if (!this.scanner) {
                    return;
                }

                try {
                    if (this.scanner.isScanning) {
                        await this.scanner.stop();
                    }
                } catch {
                    /* ignore */
                }

                try {
                    this.scanner.clear();
                } catch {
                    /* ignore */
                }

                this.scanner = null;
                this.scannerStartedAt = null;
                this.torchSupported = false;
                this.torchOn = false;
            },

            async destroyScanner() {
                await this.teardownScanner();
            },

            showPageAlert(type, message) {
                this.pageAlert = { type, message };
            },

            async handleDecoded(decodedText) {
                const text = (decodedText || '').trim();
                if (!text || !this.scanner?.isScanning || this.resolving) {
                    return;
                }

                /* URL / path → langsung buka (tanpa modal) */
                if (RE_URL.test(text) || (text.startsWith('/') && text.length > 1)) {
                    window.location.href = text;

                    return;
                }

                try {
                    this.scanner.pause(true);
                } catch {
                    /* ignore */
                }

                await this.processScannedPayload(text, this.scanTiming());
            },

            scanTiming() {
                if (this.scannerStartedAt == null) {
                    return null;
                }

                return { scan_ms: Math.max(0, performance.now() - this.scannerStartedAt) };
            },

            async resolvePayload(payload, timing = null) {
                if (!this.resolveUrl) {
                    const href = this.targetHref(payload);

                    if (!href) {
                        return { ok: false, message: UNKNOWN_QR_MESSAGE };
                    }

                    return { ok: true, href, payload };
                }

                const result = await resolveQr(this.resolveUrl, payload, timing);

                if (result.apar && this.aparHref) {
                    return { ok: true, href: this.aparHref, payload };
                }

                if (result.section && this.temuanHref) {
                    return { ok: true, href: this.temuanHref, payload };
                }

                if (result.message) {
                    return { ok: false, message: result.message };
                }

                return { ok: false, message: UNKNOWN_QR_MESSAGE };
            },

            async processScannedPayload(payload, timing = null) {
                this.resolving = true;
                this.pageAlert = null;

                try {
                    const resolved = await this.resolvePayload(payload, timing);

                    if (!resolved.ok) {
                        this.showPageAlert('error', resolved.message ?? UNKNOWN_QR_MESSAGE);
                        this.resumeScanning();

                        return;
                    }

                    window.location.href = appendQueryParam(resolved.href, 'q', resolved.payload);
                } catch (error) {
                    this.showPageAlert('error', error?.message ?? 'Gagal memvalidasi QR Code.');
                    this.resumeScanning();
                } finally {
                    this.resolving = false;
                }
            },

            async navigateWithPayload(href, payload) {
                if (!payload) {
                    if (href) {
                        window.location.href = href;
                    }

                    return;
                }

                await this.processScannedPayload(payload);
            },

            hrefForManualItem(item) {
                const payload = JSON.stringify(item.payload || item);
                let target = this.targetHref(payload);

                if (target) {
                    return target;
                }

                const kind = this.itemKind(item);

                if (kind === 'apar' && this.aparHref) {
                    return this.aparHref;
                }

                if (kind === 'lokasi' && this.temuanHref) {
                    return this.temuanHref;
                }

                return '';
            },

            async navigateWithManualItem(item) {
                const payload = JSON.stringify(item.payload || item);
                const target = this.hrefForManualItem(item);

                if (!target) {
                    this.showPageAlert('error', 'Tujuan formulir tidak tersedia untuk item ini.');

                    return;
                }

                await this.navigateWithPayload(target, payload);
            },

            resumeScanning() {
                if (this.scanner?.isScanning) {
                    try {
                        this.scanner.resume();
                    } catch {
                        /* ignore */
                    }
                }
            },

            async toggleTorch() {
                if (!this.scanner?.isScanning || !this.torchSupported) {
                    return;
                }

                try {
                    const caps = this.scanner.getRunningTrackCameraCapabilities();
                    const next = !this.torchOn;

                    await caps.torchFeature().apply(next);
                    this.torchOn = next;
                } catch {
                    this.torchSupported = false;
                    this.torchOn = false;
                }
            },

            async flipCamera() {
                if (!this.canFlipCamera) {
                    return;
                }

                this.initializing = true;
                this.cameraIndex = (this.cameraIndex + 1) % this.cameras.length;
                await this.mountAndStart();
            },
        }));
    });
}
