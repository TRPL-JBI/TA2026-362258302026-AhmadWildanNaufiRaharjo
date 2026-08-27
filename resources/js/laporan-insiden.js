import { postFormData } from './patroli-api';
import { appendPhotoEntries, hasPhotoFiles, pickPhotos } from './patroli-photo';
import { uiAlert } from './ui-dialog';

const MAX_FOTOS = 10;
const MAX_KORBAN = 20;

function pad(value) {
    return String(value).padStart(2, '0');
}

function newKorbanEntry() {
    return {
        id: `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
        nama: '',
        usia: '',
        unitProdi: '',
        jabatan: '',
        status: '',
    };
}

export function registerLaporanInsiden() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('laporanInsidenPage', (config = {}) => ({
            jenisOptions: config.jenisOptions ?? [],
            lokasiOptions: config.lokasiOptions ?? [],
            storeUrl: config.storeUrl ?? '',
            jenis: '',
            lokasiId: '',
            isManualLocation: false,
            manualLocation: '',
            tanggal: '',
            waktu: '',
            kronologi: '',
            korbanList: [],
            fotos: [],
            showSuccess: false,
            isSubmitting: false,
            lastNomor: '',

            init() {
                this.fillCurrentDateTime();
            },

            fillCurrentDateTime() {
                const now = new Date();

                this.tanggal = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
                this.waktu = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
            },

            setManualLocation(enabled) {
                this.isManualLocation = enabled;

                if (enabled) {
                    this.lokasiId = '';
                } else {
                    this.manualLocation = '';
                }
            },

            addKorban() {
                if (this.korbanList.length >= MAX_KORBAN) {
                    return;
                }

                this.korbanList.push(newKorbanEntry());
            },

            removeKorban(id) {
                this.korbanList = this.korbanList.filter((item) => item.id !== id);
            },

            async pickCamera() {
                await pickPhotos({
                    capture: true,
                    multiple: true,
                    onSelected: (files) => this.addPhotos(files),
                });
            },

            async pickGallery() {
                await pickPhotos({
                    capture: false,
                    multiple: true,
                    onSelected: (files) => this.addPhotos(files),
                });
            },

            async addPhotos(files) {
                this.fotos = await appendPhotoEntries(files, this.fotos, { maxPhotos: MAX_FOTOS });
            },

            removePhoto(photoId) {
                this.fotos = this.fotos.filter((p) => p.id !== photoId);
            },

            buildFormData() {
                const fd = new FormData();

                fd.append('jenis_insiden', this.jenis);

                if (this.isManualLocation) {
                    fd.append('lokasi_manual', this.manualLocation.trim());
                } else if (this.lokasiId !== '' && this.lokasiId != null) {
                    fd.append('lokasi_id', String(this.lokasiId));
                }

                fd.append('tanggal', this.tanggal);
                fd.append('waktu', this.waktu);
                fd.append('kronologi', this.kronologi.trim());

                this.korbanList.forEach((item, index) => {
                    fd.append(`korban_list[${index}][nama]`, item.nama.trim());
                    fd.append(`korban_list[${index}][usia]`, item.usia.trim());
                    fd.append(`korban_list[${index}][unit_prodi]`, item.unitProdi.trim());
                    fd.append(`korban_list[${index}][jabatan]`, item.jabatan.trim());
                    fd.append(`korban_list[${index}][status]`, item.status.trim());
                });

                this.fotos.forEach((photo) => {
                    if (photo?.file instanceof File) {
                        fd.append('foto[]', photo.file, photo.file.name);
                    }
                });

                return fd;
            },

            resetForm() {
                this.jenis = '';
                this.lokasiId = '';
                this.isManualLocation = false;
                this.manualLocation = '';
                this.kronologi = '';
                this.korbanList = [];
                this.fotos = [];
                this.showSuccess = false;
                this.lastNomor = '';
                this.fillCurrentDateTime();
            },

            async submit() {
                if (!this.jenis) {
                    await uiAlert('Pilih jenis insiden.');

                    return;
                }

                if (!this.isManualLocation && !this.lokasiId) {
                    await uiAlert('Pilih lokasi kejadian.');

                    return;
                }

                if (this.isManualLocation && !this.manualLocation.trim()) {
                    await uiAlert('Isi lokasi manual.');

                    return;
                }

                if (!this.tanggal || !this.waktu || !this.kronologi.trim()) {
                    await uiAlert('Harap isi tanggal, waktu, dan kronologi.');

                    return;
                }

                for (const item of this.korbanList) {
                    const hasExtra = [item.usia, item.unitProdi, item.jabatan, item.status]
                        .some((value) => String(value || '').trim() !== '');

                    if (!item.nama.trim() && hasExtra) {
                        await uiAlert('Nama korban wajib diisi jika detail korban diisi.');

                        return;
                    }
                }

                if (!hasPhotoFiles(this.fotos)) {
                    await uiAlert('Minimal satu foto TKP wajib diunggah.');

                    return;
                }

                if (!this.storeUrl) {
                    await uiAlert('Konfigurasi halaman tidak lengkap. Muat ulang halaman.');

                    return;
                }

                this.isSubmitting = true;

                try {
                    const result = await postFormData(this.storeUrl, this.buildFormData());

                    this.lastNomor = result?.data?.nomor ?? '';
                    this.showSuccess = true;
                } catch (error) {
                    await uiAlert(error?.message ?? 'Gagal mengirim laporan.');
                } finally {
                    this.isSubmitting = false;
                }
            },
        }));
    });
}
