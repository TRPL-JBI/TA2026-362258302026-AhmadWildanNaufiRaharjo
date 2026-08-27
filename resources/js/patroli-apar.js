import { appendQueryParam, postFormData, resolveQr } from './patroli-api';
import { clearAparDraft, loadAparDraft, saveAparDraft } from './patroli-draft';
import { appendPhotoEntries, pickPhotos } from './patroli-photo';
import { uiAlert } from './ui-dialog';

function clone(value) {
    return JSON.parse(JSON.stringify(value));
}

function groupAparIntoSections(apar) {
    const lokasiNama = apar.lokasiApar || 'Lokasi APAR';

    return {
        id: lokasiNama,
        nama: lokasiNama,
        expanded: true,
        aparList: [apar],
    };
}

function mergeAparIntoSections(sections, apar) {
    const list = Array.isArray(sections) ? sections : [];
    const lokasiIndex = list.findIndex((lokasi) => lokasi.nama === apar.lokasiApar);

    if (lokasiIndex >= 0) {
        const lokasi = list[lokasiIndex];
        const exists = lokasi.aparList.some(
            (row) => row.apar_id === apar.apar_id || row.kodeApar === apar.kodeApar,
        );

        if (exists) {
            return list;
        }

        return list.map((row, index) =>
            index === lokasiIndex
                ? {
                      ...row,
                      expanded: true,
                      aparList: [...row.aparList, apar],
                  }
                : row,
        );
    }

    return [...list, groupAparIntoSections(apar)];
}

export function registerPatroliApar() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('patroliAparPage', (opts = {}) => ({
            lokasiSections: [],
            showSuccess: false,
            saving: false,
            saveError: null,
            loadingContinue: false,
            scanPayload: typeof opts.scanPayload === 'string' ? opts.scanPayload : '',
            scanError: opts.scanError ?? null,
            readOnly: Boolean(opts.readOnly),
            storeUrl: opts.storeUrl ?? '',
            resolveUrl: opts.resolveUrl ?? '',
            temuanHref: opts.temuanHref ?? '',

            init() {
                this.$watch(
                    'lokasiSections',
                    (value) => {
                        saveAparDraft(value);
                    },
                    { deep: true },
                );

                const continueSections = Array.isArray(opts.continueLokasiSections)
                    ? opts.continueLokasiSections
                    : [];

                if (opts.showContinueLoading && continueSections.length > 0) {
                    this.hydrateFromContinue(continueSections);

                    return;
                }

                let sections = loadAparDraft();

                if (opts.initialApar) {
                    sections = mergeAparIntoSections(sections, clone(opts.initialApar));
                }

                this.lokasiSections = sections;

                if (!opts.initialApar && this.scanPayload && this.resolveUrl) {
                    this.addScannedApar(this.scanPayload);
                }
            },

            async hydrateFromContinue(sections) {
                this.loadingContinue = true;

                await new Promise((resolve) => {
                    requestAnimationFrame(() => requestAnimationFrame(resolve));
                });

                clearAparDraft();

                let merged = [];

                sections.forEach((lokasi) => {
                    lokasi.aparList?.forEach((apar) => {
                        merged = mergeAparIntoSections(merged, clone(apar));
                    });
                });

                this.lokasiSections = merged;
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

            totalAPAR() {
                return this.lokasiSections.reduce((acc, lokasi) => acc + lokasi.aparList.length, 0);
            },

            totalLokasi() {
                return this.lokasiSections.length;
            },

            newAparList() {
                if (this.readOnly) {
                    return [];
                }

                return this.lokasiSections.flatMap((lokasi) => lokasi.aparList);
            },

            aparPersisted(apar) {
                return Boolean(apar?.persisted || apar?.pemeriksaan_id);
            },

            canSave() {
                if (this.readOnly) {
                    return false;
                }

                const list = this.newAparList();

                if (list.length === 0) {
                    return false;
                }

                return list.every(
                    (apar) => String(apar.kondisiTabung || '').trim() !== '' && apar.kondisiSegel !== null,
                );
            },

            aparId(apar) {
                const id = apar?.apar_id ?? apar?.id;

                return id != null ? String(id) : String(apar?.kodeApar ?? '');
            },

            pickAparCamera(apar) {
                pickPhotos({
                    capture: true,
                    multiple: true,
                    onSelected: (files) => this.addAparPhotos(apar, files),
                });
            },

            pickAparGallery(apar) {
                pickPhotos({
                    capture: false,
                    multiple: true,
                    onSelected: (files) => this.addAparPhotos(apar, files),
                });
            },

            async addAparPhotos(apar, files) {
                const aparId = this.aparId(apar);
                const next = await appendPhotoEntries(files, apar.fotoKondisi ?? []);

                this.updateAparField(aparId, 'fotoKondisi', next);
            },

            removeAparPhoto(apar, photoId) {
                const aparId = this.aparId(apar);
                const next = (apar.fotoKondisi ?? []).filter((p) => p.id !== photoId);

                this.updateAparField(aparId, 'fotoKondisi', next);
            },

            updateAparField(aparId, field, value) {
                const targetId = String(aparId);

                for (const lokasi of this.lokasiSections) {
                    const apar = lokasi.aparList.find((row) => this.aparId(row) === targetId);

                    if (apar) {
                        apar[field] = value;

                        return;
                    }
                }
            },

            setKondisiSegel(apar, value) {
                if (this.readOnly) {
                    return;
                }

                this.updateAparField(this.aparId(apar), 'kondisiSegel', value);
            },

            sealOptionClass(apar, value) {
                if (apar.kondisiSegel !== value) {
                    return 'border-gray-200 text-gray-600 hover:bg-gray-50';
                }

                if (value === 'tersegel') {
                    return 'border-emerald-500 bg-emerald-50 text-emerald-700';
                }

                return 'border-red-500 bg-red-50 text-red-700';
            },

            sealDotClass(apar, value) {
                if (value === 'tersegel' && apar.kondisiSegel === value) {
                    return 'border-emerald-500';
                }
                if (value === 'tidak-tersegel' && apar.kondisiSegel === value) {
                    return 'border-red-500';
                }

                return 'border-gray-300';
            },

            removeApar(lokasiId, aparId) {
                if (this.readOnly) {
                    return;
                }

                const targetId = String(aparId);
                const lokasi = this.lokasiSections.find((row) => row.id === lokasiId);

                if (!lokasi) {
                    return;
                }

                const aparIndex = lokasi.aparList.findIndex((row) => this.aparId(row) === targetId);

                if (aparIndex >= 0) {
                    lokasi.aparList.splice(aparIndex, 1);
                }

                if (lokasi.aparList.length === 0) {
                    const lokasiIndex = this.lokasiSections.findIndex((row) => row.id === lokasiId);

                    if (lokasiIndex >= 0) {
                        this.lokasiSections.splice(lokasiIndex, 1);
                    }
                }
            },

            removeLokasi(lokasiId) {
                if (this.readOnly) {
                    return;
                }

                const lokasiIndex = this.lokasiSections.findIndex((lokasi) => lokasi.id === lokasiId);

                if (lokasiIndex >= 0) {
                    this.lokasiSections.splice(lokasiIndex, 1);
                }
            },

            toggleLokasi(lokasiId) {
                const lokasi = this.lokasiSections.find((row) => row.id === lokasiId);

                if (lokasi) {
                    lokasi.expanded = !lokasi.expanded;
                }
            },

            mergeApar(apar) {
                const lokasiIndex = this.lokasiSections.findIndex((row) => row.nama === apar.lokasiApar);

                if (lokasiIndex >= 0) {
                    const lokasi = this.lokasiSections[lokasiIndex];
                    const exists = lokasi.aparList.some(
                        (row) => row.apar_id === apar.apar_id || row.kodeApar === apar.kodeApar,
                    );

                    if (exists) {
                        return;
                    }

                    lokasi.expanded = true;
                    lokasi.aparList.push(apar);

                    return;
                }

                this.lokasiSections.push(groupAparIntoSections(apar));
            },

            async addScannedApar(payload) {
                if (!this.resolveUrl) {
                    return;
                }

                try {
                    const result = await resolveQr(this.resolveUrl, payload);

                    if (result.section && this.temuanHref) {
                        window.location.href = appendQueryParam(this.temuanHref, 'q', payload);

                        return;
                    }

                    if (result.apar) {
                        this.mergeApar(clone(result.apar));
                        this.scanError = null;
                        this.scanPayload = '';
                    } else if (result.message) {
                        this.scanError = result.message;
                    }
                } catch (error) {
                    this.scanError = error?.message ?? 'Gagal memuat data APAR.';
                }
            },

            buildFormData() {
                const fd = new FormData();
                let index = 0;

                this.lokasiSections.forEach((lokasi) => {
                    lokasi.aparList.forEach((apar) => {
                        const aparId = this.aparId(apar);
                        const prefix = `pemeriksaan[${index}]`;

                        fd.append(`${prefix}[apar_id]`, String(aparId));

                        if (apar.pemeriksaan_id) {
                            fd.append(`${prefix}[pemeriksaan_id]`, String(apar.pemeriksaan_id));
                        }

                        fd.append(`${prefix}[kondisi_tabung]`, String(apar.kondisiTabung ?? ''));
                        fd.append(`${prefix}[kondisi_segel]`, String(apar.kondisiSegel ?? ''));

                        if (apar.catatan) {
                            fd.append(`${prefix}[catatan]`, String(apar.catatan));
                        }

                        (apar.fotoKondisi ?? []).forEach((photo) => {
                            if (photo?.file instanceof File) {
                                fd.append(`foto_apar[${aparId}][]`, photo.file, photo.file.name);
                            }
                        });

                        index += 1;
                    });
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

                if (!this.canSave()) {
                    await uiAlert('Lengkapi kondisi tabung dan pilih kondisi segel untuk setiap APAR baru.');

                    return;
                }

                this.saving = true;
                this.saveError = null;

                try {
                    const result = await postFormData(this.storeUrl, this.buildFormData());

                    clearAparDraft();

                    const target = result?.detail_redirect ?? result?.redirect;

                    if (target) {
                        window.location.href = target;

                        return;
                    }

                    this.showSuccess = true;
                } catch (error) {
                    this.saveError = error?.message ?? 'Gagal menyimpan pemeriksaan APAR.';
                    await uiAlert(this.saveError);
                } finally {
                    this.saving = false;
                }
            },

            inspectionDate() {
                return new Intl.DateTimeFormat('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                }).format(new Date());
            },
        }));
    });
}
