import { patchJson, postJson } from './patroli-api';
import { uiAlert, uiConfirm } from './ui-dialog';

export function registerPatroliRiwayatOverview() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('patroliRiwayatPage', (opts = {}) => ({
            overview: opts.overview ?? { temuan: {}, apar: {} },
            periodeOptions: Array.isArray(opts.periodeOptions) ? opts.periodeOptions : [],
            storeChecklistUrl: opts.storeChecklistUrl ?? '',
            storeItemUrlTemplate: opts.storeItemUrlTemplate ?? '',
            toggleItemUrlTemplate: opts.toggleItemUrlTemplate ?? '',
            q: '',
            sectionFilter: 'semua',
            filterStatus: 'semua',
            expandedIds: [],
            togglingItemId: null,
            finishingTemuan: false,
            finishingApar: false,
            submitting: false,
            formError: null,
            checklistModalOpen: false,
            itemModalOpen: false,
            checklistForm: {
                lokasi_id: '',
                nama_checklist: '',
            },
            itemForm: {
                master_checklist_id: '',
                nama_item: '',
                probability: 3,
                severity: 3,
            },

            changePeriode(periode) {
                const key = String(periode ?? '').trim();

                if (!/^\d{4}-[1-3]$/.test(key)) {
                    return;
                }

                const target = `/patroli/riwayat/${key}`;

                if (window.location.pathname === target) {
                    return;
                }

                window.location.assign(target);
            },

            cardClass(status) {
                if (status === 'selesai') {
                    return 'border-emerald-400 bg-emerald-50';
                }

                if (status === 'belum_siap') {
                    return 'border-amber-400 bg-amber-50/70';
                }

                return 'border-red-400 bg-red-50';
            },

            statusBadgeClass(status) {
                if (status === 'selesai') {
                    return 'bg-emerald-600 text-white';
                }

                if (status === 'belum_siap') {
                    return 'bg-amber-600 text-white';
                }

                return 'bg-red-600 text-white';
            },

            filteredTemuanSiap() {
                return (this.overview.temuan?.lokasi ?? []).filter((row) =>
                    (row.status === 'selesai' || row.status === 'belum')
                    && this.matchesQuery(row, ['nama', 'nama_checklist'])
                    && this.matchesStatusFilter(row.status),
                );
            },

            filteredTemuanBelumSiap() {
                return (this.overview.temuan?.lokasi ?? []).filter((row) =>
                    row.status === 'belum_siap'
                    && this.matchesQuery(row, ['nama', 'nama_checklist', 'status_label'])
                    && this.matchesStatusFilter(row.status),
                );
            },

            matchesQuery(row, fields) {
                const query = (this.q || '').toLowerCase().trim();

                if (!query) {
                    return true;
                }

                const blob = fields.map((field) => row[field]).filter(Boolean).join(' ').toLowerCase();

                return blob.includes(query);
            },

            matchesStatusFilter(status) {
                if (this.filterStatus === 'semua') {
                    return true;
                }

                if (this.filterStatus === 'selesai') {
                    return status === 'selesai';
                }

                if (this.filterStatus === 'persiapan') {
                    return status === 'belum_siap';
                }

                if (this.filterStatus === 'belum') {
                    return status === 'belum';
                }

                return status === 'belum' || status === 'belum_siap';
            },

            filteredTemuan() {
                return [...this.filteredTemuanSiap(), ...this.filteredTemuanBelumSiap()];
            },

            filteredApar() {
                return (this.overview.apar?.units ?? []).filter((row) =>
                    this.matchesQuery(row, ['kode_apar', 'lokasi', 'jenis_kapasitas'])
                    && this.matchesStatusFilter(row.status),
                );
            },

            toggleDetail(key) {
                if (this.expandedIds.includes(key)) {
                    this.expandedIds = this.expandedIds.filter((id) => id !== key);
                } else {
                    this.expandedIds = [...this.expandedIds, key];
                }
            },

            openChecklistModal(lokasiId = null) {
                this.formError = null;
                this.checklistForm = {
                    lokasi_id: lokasiId ? String(lokasiId) : '',
                    nama_checklist: '',
                };
                this.checklistModalOpen = true;
            },

            closeChecklistModal() {
                this.checklistModalOpen = false;
            },

            openItemModal(checklistId = null) {
                this.formError = null;
                this.itemForm = {
                    master_checklist_id: checklistId ? String(checklistId) : '',
                    nama_item: '',
                    probability: 3,
                    severity: 3,
                };
                this.itemModalOpen = true;
            },

            closeItemModal() {
                this.itemModalOpen = false;
            },

            async toggleChecklistItem(row, item) {
                if (!this.toggleItemUrlTemplate || !this.overview.temuan?.can_modify || this.togglingItemId) {
                    return;
                }

                const url = this.toggleItemUrlTemplate.replace('__ID__', String(item.id));

                this.togglingItemId = item.id;

                try {
                    const result = await patchJson(url, {});
                    const data = result?.data ?? {};

                    item.status = data.status ?? (item.aktif ? 'Nonaktif' : 'Aktif');
                    item.aktif = data.aktif ?? item.status === 'Aktif';

                    if (row.status === 'belum') {
                        row.item_count = (row.checklist_items ?? []).filter((entry) => entry.aktif).length;
                    }
                } catch (error) {
                    await uiAlert(error?.message ?? 'Gagal mengubah status item.');
                } finally {
                    this.togglingItemId = null;
                }
            },

            async submitChecklist() {
                if (!this.storeChecklistUrl || this.submitting) {
                    return;
                }

                this.submitting = true;
                this.formError = null;

                try {
                    const result = await postJson(this.storeChecklistUrl, {
                        lokasi_id: Number(this.checklistForm.lokasi_id),
                        nama_checklist: this.checklistForm.nama_checklist,
                    });

                    if (result?.redirect) {
                        window.location.href = result.redirect;

                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    this.formError = error?.message ?? 'Gagal menyimpan checklist.';
                } finally {
                    this.submitting = false;
                }
            },

            async submitItem() {
                const checklistId = this.itemForm.master_checklist_id;

                if (!checklistId || this.submitting) {
                    return;
                }

                const url = this.storeItemUrlTemplate.replace('__ID__', String(checklistId));

                this.submitting = true;
                this.formError = null;

                try {
                    const result = await postJson(url, {
                        nama_item: this.itemForm.nama_item,
                        probability: Number(this.itemForm.probability),
                        severity: Number(this.itemForm.severity),
                    });

                    if (result?.redirect) {
                        window.location.href = result.redirect;

                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    this.formError = error?.message ?? 'Gagal menyimpan item.';
                } finally {
                    this.submitting = false;
                }
            },

            async tandaiSelesai(jenis) {
                const section = jenis === 'apar' ? this.overview.apar : this.overview.temuan;
                const url = section?.finish_url;
                const nama = section?.nama_laporan;
                const progress = section?.progress ?? {};
                const selesai = Number(progress.selesai ?? 0);
                const total = Number(progress.total ?? 0);
                const sisa = Math.max(0, total - selesai);
                const satuan = jenis === 'apar' ? 'unit APAR' : 'lokasi';

                if (!url) {
                    return;
                }

                if (jenis === 'temuan' && this.finishingTemuan) {
                    return;
                }

                if (jenis === 'apar' && this.finishingApar) {
                    return;
                }

                if (total < 1 || selesai !== total) {
                    await uiAlert(
                        total < 1
                            ? `Belum ada ${satuan} yang dapat ditandai selesai.`
                            : `Belum semua ${satuan} dicek.\nMasih tersisa ${sisa} ${satuan} yang belum diperiksa.\nLengkapi semua pemeriksaan sebelum menandai selesai.`,
                        { title: 'Belum Lengkap' },
                    );

                    return;
                }

                if (!await uiConfirm(
                    `Tandai "${nama}" sebagai selesai? Setelah selesai, inspeksi tidak dapat dilanjutkan.`,
                    { confirmLabel: 'Tandai Selesai' },
                )) {
                    return;
                }

                if (jenis === 'temuan') {
                    this.finishingTemuan = true;
                } else {
                    this.finishingApar = true;
                }

                try {
                    const result = await patchJson(url, {});

                    if (result?.redirect) {
                        window.location.href = result.redirect;

                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    await uiAlert(error?.message ?? 'Gagal menandai selesai.', { title: 'Gagal' });
                } finally {
                    if (jenis === 'temuan') {
                        this.finishingTemuan = false;
                    } else {
                        this.finishingApar = false;
                    }
                }
            },
        }));
    });
}
