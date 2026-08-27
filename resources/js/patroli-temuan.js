import { appendQueryParam, postFormData, resolveQr } from './patroli-api';
import { clearTemuanDraft, loadTemuanDraft, saveTemuanDraft } from './patroli-draft';
import { appendPhotoEntries, hasPhotoDocumentation, pickPhotos } from './patroli-photo';
import { uiAlert } from './ui-dialog';

function clone(value) {
    return JSON.parse(JSON.stringify(value));
}

function riskLevel(score) {
    if (score <= 3) {
        return 'Rendah';
    }
    if (score <= 7) {
        return 'Sedang';
    }
    if (score <= 12) {
        return 'Tinggi';
    }

    return 'Sangat Tinggi';
}

const RISK_BADGE = {
    Rendah: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    Sedang: 'bg-yellow-100 text-yellow-700 border-yellow-200',
    Tinggi: 'bg-orange-100 text-orange-700 border-orange-200',
    'Sangat Tinggi': 'bg-red-100 text-red-700 border-red-200',
};

const RISK_DOT = {
    Rendah: 'bg-emerald-400',
    Sedang: 'bg-yellow-400',
    Tinggi: 'bg-orange-400',
    'Sangat Tinggi': 'bg-red-400',
};

export function registerPatroliTemuan() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('patroliTemuanPage', (opts = {}) => ({
            sections: [],
            showSuccess: false,
            saving: false,
            saveError: null,
            loadingContinue: false,
            scanPayload: typeof opts.scanPayload === 'string' ? opts.scanPayload : '',
            scanError: opts.scanError ?? null,
            readOnly: Boolean(opts.readOnly),
            storeUrl: opts.storeUrl ?? '',
            resolveUrl: opts.resolveUrl ?? '',
            aparHref: opts.aparHref ?? '',

            init() {
                this.$watch(
                    'sections',
                    (value) => {
                        saveTemuanDraft(value);
                    },
                    { deep: true },
                );

                const continueSections = Array.isArray(opts.continueSections) ? opts.continueSections : [];

                if (opts.showContinueLoading && continueSections.length > 0) {
                    this.hydrateFromContinue(continueSections);

                    return;
                }

                const stored = loadTemuanDraft();

                if (stored.length > 0) {
                    this.sections = stored;
                }

                if (opts.initialSection) {
                    this.addSection(opts.initialSection);
                } else if (this.scanPayload && this.resolveUrl) {
                    this.addLokasiFromPayload(this.scanPayload);
                }
            },

            async hydrateFromContinue(sections) {
                this.loadingContinue = true;

                await new Promise((resolve) => {
                    requestAnimationFrame(() => requestAnimationFrame(resolve));
                });

                clearTemuanDraft();
                this.sections = [];

                sections.forEach((section) => {
                    this.addSection(section);
                });

                this.loadingContinue = false;
                this.stripContinueQuery();
            },

            stripContinueQuery() {
                if (!window.history.replaceState) {
                    return;
                }

                const url = new URL(window.location.href);

                if (!url.searchParams.has('continue_periode')) {
                    return;
                }

                url.searchParams.delete('continue_periode');
                const next = url.pathname + (url.search || '');

                window.history.replaceState({}, '', next);
            },

            allItems() {
                return this.sections.flatMap((section) => section.items);
            },

            totalItems() {
                return this.allItems().length;
            },

            doneYa() {
                return this.allItems().filter((item) => item.status === 'ya').length;
            },

            doneTidak() {
                return this.allItems().filter((item) => item.status === 'tidak').length;
            },

            belum() {
                return this.allItems().filter((item) => item.status === 'belum').length;
            },

            progress() {
                const total = this.totalItems();

                return total === 0 ? 0 : Math.round(((this.doneYa() + this.doneTidak()) / total) * 100);
            },

            temuanKritis() {
                return this.allItems().filter((item) => {
                    if (item.status !== 'tidak') {
                        return false;
                    }

                    const level = this.riskLevel(item);

                    return level === 'Tinggi' || level === 'Sangat Tinggi';
                });
            },

            editableSections() {
                if (this.readOnly) {
                    return [];
                }

                return this.sections;
            },

            sectionEditable(section) {
                return !this.readOnly;
            },

            sectionPersisted(section) {
                return Boolean(section?.persisted || section?.inspeksi_id);
            },

            editableItems() {
                return this.editableSections().flatMap((section) => section.items);
            },

            isTidakItemComplete(item) {
                const analisa = String(item.analisaRisiko ?? '').trim();
                const rekomendasi = String(item.rekomendasi ?? '').trim();

                return analisa !== '' && rekomendasi !== '' && hasPhotoDocumentation(item.fotoDokumentasi);
            },

            saveBlockReason() {
                if (this.readOnly) {
                    return 'Mode lihat. Tidak dapat menyimpan perubahan.';
                }

                const sections = this.editableSections();

                if (sections.length === 0) {
                    return 'Belum ada lokasi. Scan QR lokasi terlebih dahulu.';
                }

                const items = this.editableItems();
                const unanswered = items.filter((item) => item.status === 'belum');

                if (unanswered.length > 0) {
                    return `${unanswered.length} item belum ditandai Ya atau Tidak.`;
                }

                const incompleteTidak = items.filter(
                    (item) => item.status === 'tidak' && !this.isTidakItemComplete(item),
                );

                if (incompleteTidak.length === 0) {
                    return '';
                }

                const missingPhoto = incompleteTidak.filter((item) => !hasPhotoFiles(item.fotoDokumentasi));

                if (missingPhoto.length > 0) {
                    return `${missingPhoto.length} temuan Tidak Sesuai masih perlu foto dokumentasi.`;
                }

                return 'Lengkapi analisa risiko dan rekomendasi untuk setiap temuan Tidak Sesuai.';
            },

            canSave() {
                return this.saveBlockReason() === '';
            },

            pickItemCamera(sectionId, item) {
                pickPhotos({
                    capture: true,
                    multiple: false,
                    onSelected: (files) => this.addItemPhotos(sectionId, item.id, files),
                });
            },

            pickItemGallery(sectionId, item) {
                pickPhotos({
                    capture: false,
                    multiple: false,
                    onSelected: (files) => this.addItemPhotos(sectionId, item.id, files),
                });
            },

            async addItemPhotos(sectionId, itemId, files) {
                const next = await appendPhotoEntries(files, [], { maxPhotos: 1 });

                this.updateField(sectionId, itemId, 'fotoDokumentasi', next);
            },

            removeItemPhoto(sectionId, itemId, photoId) {
                const section = this.sections.find((row) => row.id === sectionId);
                const item = section?.items?.find((row) => row.id === itemId);
                const next = (item?.fotoDokumentasi ?? []).filter((p) => p.id !== photoId);

                this.updateField(sectionId, itemId, 'fotoDokumentasi', next);
            },

            score(item) {
                return Number(item.probability || 0) * Number(item.severity || 0);
            },

            riskLevel(item) {
                return riskLevel(this.score(item));
            },

            riskClass(level) {
                return RISK_BADGE[level] || 'bg-gray-100 text-gray-600 border-gray-200';
            },

            riskDotClass(level) {
                return RISK_DOT[level] || 'bg-gray-400';
            },

            itemCardClass(item) {
                if (item.status === 'ya') {
                    return 'border-emerald-200 bg-emerald-50/20';
                }
                if (item.status === 'tidak') {
                    return 'border-red-200 bg-red-50/20';
                }

                return 'border-gray-200 bg-white';
            },

            indicatorClass(item) {
                if (item.status === 'ya') {
                    return 'bg-emerald-500 border-emerald-500';
                }
                if (item.status === 'tidak') {
                    return 'bg-red-500 border-red-500';
                }

                return 'bg-white border-gray-300';
            },

            yaButtonClass(item) {
                return item.status === 'ya'
                    ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm'
                    : 'bg-white border-gray-200 text-gray-600 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700';
            },

            tidakButtonClass(item) {
                return item.status === 'tidak'
                    ? 'bg-red-500 border-red-500 text-white shadow-sm'
                    : 'bg-white border-gray-200 text-gray-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700';
            },

            updateStatus(lokasiId, itemId, nextStatus) {
                this.sections = this.sections.map((section) => {
                    if (section.id !== lokasiId) {
                        return section;
                    }

                    return {
                        ...section,
                        items: section.items.map((item) => {
                            if (item.id !== itemId) {
                                return item;
                            }

                            return {
                                ...item,
                                status: item.status === nextStatus ? 'belum' : nextStatus,
                            };
                        }),
                    };
                });
            },

            updateField(lokasiId, itemId, field, value) {
                this.sections = this.sections.map((section) => {
                    if (section.id !== lokasiId) {
                        return section;
                    }

                    return {
                        ...section,
                        items: section.items.map((item) =>
                            item.id === itemId ? { ...item, [field]: value } : item,
                        ),
                    };
                });
            },

            toggleExpand(lokasiId) {
                this.sections = this.sections.map((section) =>
                    section.id === lokasiId ? { ...section, expanded: !section.expanded } : section,
                );
            },

            removeLokasi(lokasiId) {
                if (this.readOnly) {
                    return;
                }

                const section = this.sections.find((row) => row.id === lokasiId);

                if (section && this.sectionPersisted(section)) {
                    return;
                }

                this.sections = this.sections.filter((section) => section.id !== lokasiId);
            },

            lokasiDone(lokasi) {
                return lokasi.items.filter((item) => item.status !== 'belum').length;
            },

            lokasiProgress(lokasi) {
                if (!lokasi.items.length) {
                    return 0;
                }

                return Math.round((this.lokasiDone(lokasi) / lokasi.items.length) * 100);
            },

            lokasiTidakSesuai(lokasi) {
                return lokasi.items.filter((item) => item.status === 'tidak').length;
            },

            sectionExists(section) {
                return this.sections.some((row) => row.id === section.id);
            },

            addSection(section) {
                if (!section || this.sectionExists(section)) {
                    return false;
                }

                this.sections = [...this.sections, clone({ ...section, expanded: true })];

                return true;
            },

            async addLokasiFromPayload(payload) {
                if (!this.resolveUrl) {
                    return;
                }

                try {
                    const result = await resolveQr(this.resolveUrl, payload);

                    if (result.apar && this.aparHref) {
                        window.location.href = appendQueryParam(this.aparHref, 'q', payload);

                        return;
                    }

                    if (result.section) {
                        this.addSection(result.section);
                        this.scanError = null;
                        this.scanPayload = '';
                    } else if (result.message) {
                        this.scanError = result.message;
                    }
                } catch (error) {
                    this.scanError = error?.message ?? 'Gagal memuat data lokasi.';
                }
            },

            buildFormData() {
                const fd = new FormData();
                let sectionIndex = 0;

                this.sections
                    .forEach((section) => {
                        const items = section.items.filter(
                            (item) => item.status === 'ya' || item.status === 'tidak',
                        );

                        if (items.length === 0) {
                            return;
                        }

                        const sectionPrefix = `sections[${sectionIndex}]`;

                        fd.append(`${sectionPrefix}[lokasi_id]`, String(section.id));
                        fd.append(`${sectionPrefix}[master_checklist_id]`, String(section.master_checklist_id));

                        if (section.inspeksi_id) {
                            fd.append(`${sectionPrefix}[inspeksi_id]`, String(section.inspeksi_id));
                        }

                        items.forEach((item, itemIndex) => {
                            const itemPrefix = `${sectionPrefix}[items][${itemIndex}]`;

                            fd.append(`${itemPrefix}[item_checklist_id]`, String(item.id));
                            fd.append(`${itemPrefix}[status]`, item.status);

                            if (item.status === 'tidak') {
                                fd.append(`${itemPrefix}[analisa_risiko]`, String(item.analisaRisiko ?? ''));
                                fd.append(`${itemPrefix}[rekomendasi]`, String(item.rekomendasi ?? ''));

                                const photo = item.fotoDokumentasi?.[0];

                                if (photo?.file instanceof File) {
                                    fd.append(`foto_item[${item.id}]`, photo.file, photo.file.name);
                                }
                            }
                        });

                        sectionIndex += 1;
                    });

                return fd;
            },

            async save() {
                if (this.saving) {
                    return;
                }

                if (!this.storeUrl) {
                    await uiAlert('URL simpan tidak tersedia. Muat ulang halaman atau jalankan npm run dev / npm run build.');

                    return;
                }

                const blockReason = this.saveBlockReason();

                if (blockReason !== '') {
                    await uiAlert(blockReason);

                    return;
                }

                const missingChecklist = this.editableSections().some((section) => !section.master_checklist_id);

                if (missingChecklist) {
                    await uiAlert(
                        'Data checklist tidak lengkap. Hapus lokasi ini lalu tambahkan lagi lewat scan QR atau pilih manual dari inventaris.',
                    );

                    return;
                }

                this.saving = true;
                this.saveError = null;

                try {
                    const result = await postFormData(this.storeUrl, this.buildFormData());

                    if (result?.data?.inspeksi_count === 0) {
                        throw new Error('Tidak ada data yang tersimpan. Pastikan item sudah ditandai Ya atau Tidak.');
                    }

                    clearTemuanDraft();

                    if (result?.redirect) {
                        window.location.href = result.redirect;

                        return;
                    }

                    this.showSuccess = true;
                } catch (error) {
                    this.saveError = error?.message ?? 'Gagal menyimpan inspeksi.';
                    await uiAlert(this.saveError);
                } finally {
                    this.saving = false;
                }
            },
        }));
    });
}
