import { pickPhotos } from './patroli-photo';
import { patchJson } from './patroli-api';
import { uiAlert, uiConfirm } from './ui-dialog';
import { listPaginationMixin } from './list-pagination';

const RISK_BADGE = {
    Darurat: 'bg-red-50 text-red-700 border-red-200',
    'Sangat Tinggi': 'bg-red-50 text-red-700 border-red-200',
    Tinggi: 'bg-orange-50 text-orange-700 border-orange-200',
    Sedang: 'bg-yellow-50 text-yellow-800 border-yellow-200',
    Rendah: 'bg-emerald-50 text-emerald-700 border-emerald-200',
};

const STATUS_BADGE = {
    'Menunggu Tindakan': 'bg-yellow-50 text-yellow-800 border-yellow-200',
    'Dalam Proses': 'bg-blue-50 text-blue-700 border-blue-200',
    Selesai: 'bg-emerald-50 text-emerald-700 border-emerald-200',
};

const RISK_WEIGHT = {
    Darurat: 0,
    'Sangat Tinggi': 1,
    Tinggi: 2,
    Sedang: 3,
    Rendah: 4,
};

function buildUrl(template, id) {
    return String(template || '').replace('__ID__', String(id));
}

async function postMultipart(url, data, csrf) {
    const fd = new FormData();
    Object.entries(data || {}).forEach(([k, v]) => {
        if (v === undefined) return;
        if (v === null) return;
        fd.append(k, v);
    });

    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf,
            Accept: 'application/json',
        },
        body: fd,
    });

    const contentType = res.headers.get('content-type') || '';

    if (!res.ok) {
        const text = await res.text();
        throw new Error(text || 'Gagal menyimpan tindak lanjut.');
    }

    if (!contentType.includes('application/json')) {
        // Biasanya terjadi jika request kena redirect ke halaman HTML (role access / login / 419).
        throw new Error('Respon server tidak valid (bukan JSON). Silakan refresh halaman lalu coba lagi.');
    }

    return await res.json();
}

export function registerTindakLanjut() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('tindakLanjutPage', (items = [], config = {}) => ({
            ...listPaginationMixin(),
            items: Array.isArray(items) ? [...items] : [],
            config,
            periode: config.periode ?? '',
            periodeLabel: config.periodeLabel ?? '',
            periodeRentang: config.periodeRentang ?? '',
            periodeOptions: Array.isArray(config.periodeOptions) ? config.periodeOptions : [],
            periodeState: config.periodeState ?? { status: 'Berlangsung', total: 0, selesai: 0, dalam_proses: 0, menunggu: 0, can_finish: false, is_locked: false },
            finishPeriodeUrl: config.finishPeriodeUrl ?? '',
            selected: null,
            filterSearch: '',
            filterJenis: 'semua',
            filterStatus: 'semua',
            filterRisiko: 'semua',
            errorMessage: '',
            isSaving: false,
            finishingPeriode: false,

            init() {
                this.watchPaginationFilters(['filterSearch', 'filterJenis', 'filterStatus', 'filterRisiko']);
            },

            changePeriode(periode) {
                const key = String(periode ?? '').trim();

                if (!/^\d{4}-[1-3]$/.test(key)) {
                    return;
                }

                const target = `/tindak-lanjut/${key}`;

                if (window.location.pathname === target) {
                    return;
                }

                window.location.assign(target);
            },

            async tandaiPeriodeSelesai() {
                if (!this.finishPeriodeUrl || this.finishingPeriode || !this.periodeState?.can_finish) {
                    return;
                }

                if (!await uiConfirm(
                    `Tandai periode ${this.periodeLabel || this.periode} sebagai selesai? Laporan rekap akan dibuat. Item yang belum selesai atau sedang berlangsung akan tetap muncul di periode berikutnya.`,
                    { confirmLabel: 'Tandai Selesai' },
                )) {
                    return;
                }

                this.finishingPeriode = true;

                try {
                    const result = await patchJson(this.finishPeriodeUrl, {});

                    if (result?.redirect) {
                        window.location.href = result.redirect;
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    await uiAlert(error?.message ?? 'Gagal menutup periode tindak lanjut.');
                } finally {
                    this.finishingPeriode = false;
                }
            },

            filteredItems() {
                const q = (this.filterSearch || '').toLowerCase().trim();
                return this.items
                    .slice()
                    .sort((a, b) => {
                        const ca = a.status === 'Selesai' ? 1 : 0;
                        const cb = b.status === 'Selesai' ? 1 : 0;
                        if (ca !== cb) return ca - cb;

                        const wa = RISK_WEIGHT[a.risiko] ?? 99;
                        const wb = RISK_WEIGHT[b.risiko] ?? 99;
                        if (wa !== wb) return wa - wb;

                        const sa = a.status === 'Menunggu Tindakan' ? 0 : a.status === 'Dalam Proses' ? 1 : 2;
                        const sb = b.status === 'Menunggu Tindakan' ? 0 : b.status === 'Dalam Proses' ? 1 : 2;
                        if (sa !== sb) return sa - sb;

                        return String(b.tanggal || '').localeCompare(String(a.tanggal || ''));
                    })
                    .filter((item) => {
                    if (this.filterJenis !== 'semua' && item.jenis !== this.filterJenis) {
                        return false;
                    }
                    if (this.filterStatus !== 'semua' && item.status !== this.filterStatus) {
                        return false;
                    }
                    if (this.filterRisiko !== 'semua' && item.risiko !== this.filterRisiko) {
                        return false;
                    }
                    if (!q) {
                        return true;
                    }
                    const hay = [
                        item.lokasi,
                        item.deskripsi,
                        item.tanggal_list || item.tanggal,
                        item.jenis,
                        item.status,
                        item.risiko,
                        item.skor != null ? String(item.skor) : '',
                        item.catatan || '',
                    ]
                        .join(' ')
                        .toLowerCase();

                    return hay.includes(q);
                });
            },

            paginationMeta() {
                return this.paginateItems(this.filteredItems());
            },

            paginated() {
                return this.paginationMeta().items;
            },

            clearFilters() {
                this.filterSearch = '';
                this.filterJenis = 'semua';
                this.filterStatus = 'semua';
                this.filterRisiko = 'semua';
                this.resetPage();
            },

            hasActiveFilters() {
                return (
                    (this.filterSearch || '').trim() !== '' ||
                    this.filterJenis !== 'semua' ||
                    this.filterStatus !== 'semua' ||
                    this.filterRisiko !== 'semua'
                );
            },

            riskClass(risiko) {
                return RISK_BADGE[risiko] || 'bg-gray-50 text-gray-600 border-gray-200';
            },

            statusClass(status) {
                return STATUS_BADGE[status] || 'bg-gray-50 text-gray-600 border-gray-200';
            },

            countByStatus(status) {
                return this.items.filter((i) => i.status === status).length;
            },

            openDetail(item) {
                this.selected = item;
                this.selected.fotoBuktiFile = null;
                this.selected.fotoBuktiPreview = '';
                this.errorMessage = '';
            },

            closeDetail() {
                this.selected = null;
                this.errorMessage = '';
            },

            async saveDetail() {
                if (!this.selected) return;

                if (this.periodeState?.is_locked) {
                    this.errorMessage = 'Periode sudah ditutup. Data tidak dapat diubah.';
                    return;
                }

                this.isSaving = true;
                this.errorMessage = '';

                try {
                    const refType = this.selected.ref_type || this.selected.refType;
                    const refId = this.selected.ref_id || this.selected.refId;

                    const payload = {
                        status: this.selected.status,
                        catatan: this.selected.catatan || '',
                    };

                    if (this.selected.fotoBuktiFile instanceof File) {
                        payload.foto = this.selected.fotoBuktiFile;
                    }

                    const url =
                        refType === 'insiden'
                            ? buildUrl(this.config.updateInsidenUrl, refId)
                            : buildUrl(this.config.updateInspeksiUrl, refId);

                    const result = await postMultipart(url, payload, this.config.csrf);

                    this.selected.status = result.status || this.selected.status;
                    this.selected.catatan = result.catatan ?? this.selected.catatan;
                    this.selected.tanggal_mulai = result.tanggal_mulai ?? this.selected.tanggal_mulai;
                    this.selected.tanggal_selesai = result.tanggal_selesai ?? this.selected.tanggal_selesai;

                    if (result.foto_path) {
                        this.selected.foto_bukti = [
                            {
                                id: `stored-${result.foto_path}`,
                                preview: `/storage/${String(result.foto_path).replaceAll('\\', '/')}`,
                                storedPath: result.foto_path,
                                existing: true,
                            },
                        ];
                        this.selected.fotoBuktiFile = null;
                        this.selected.fotoBuktiPreview = '';
                    }

                    const idx = this.items.findIndex((i) => i.uid === this.selected.uid);
                    if (idx >= 0) {
                        this.items[idx] = { ...this.items[idx], ...this.selected };
                    }

                    this.closeDetail();
                } catch (e) {
                    this.errorMessage = e instanceof Error ? e.message : 'Gagal menyimpan tindak lanjut.';
                } finally {
                    this.isSaving = false;
                }
            },

            pickFotoBuktiCamera() {
                if (this.periodeState?.is_locked || !this.selected) {
                    return;
                }

                pickPhotos({
                    capture: true,
                    multiple: false,
                    onSelected: async (files) => {
                        const file = files?.[0];
                        if (!file || !(file instanceof File) || !this.selected) return;
                        this.selected.fotoBuktiFile = file;
                        this.selected.fotoBuktiPreview = URL.createObjectURL(file);
                    },
                });
            },

            pickFotoBuktiGallery() {
                if (this.periodeState?.is_locked || !this.selected) {
                    return;
                }

                pickPhotos({
                    capture: false,
                    multiple: false,
                    onSelected: async (files) => {
                        const file = files?.[0];
                        if (!file || !(file instanceof File) || !this.selected) return;
                        this.selected.fotoBuktiFile = file;
                        this.selected.fotoBuktiPreview = URL.createObjectURL(file);
                    },
                });
            },

            isSangatTinggiRow(item) {
                return item.risiko === 'Sangat Tinggi' || item.risiko === 'Darurat';
            },
        }));
    });
}
