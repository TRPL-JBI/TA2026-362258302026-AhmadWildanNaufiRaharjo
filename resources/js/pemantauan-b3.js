import { csrfToken } from './patroli-api';
import { listPaginationMixin } from './list-pagination';
import { uiAlert, uiConfirm } from './ui-dialog';

const BULAN_OPTIONS = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const SEMESTER_BULAN = {
    1: BULAN_OPTIONS.slice(0, 6),
    2: BULAN_OPTIONS.slice(6, 12),
};

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

async function requestJson(url, method, body) {
    const targetUrl = url.startsWith('http') ? url : new URL(url, window.location.origin).href;

    const response = await fetch(targetUrl, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: body !== undefined ? JSON.stringify(body) : undefined,
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

function toDecimal(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }

    const normalized = String(value).replace(',', '.').trim();
    const num = parseFloat(normalized);

    return Number.isFinite(num) ? num : null;
}

function uid() {
    return Date.now() + Math.random();
}

function emptyJenis() {
    return {
        id: uid(),
        nama_limbah: '',
        kode_limbah: '',
        sumber_limbah: '',
        karakteristik: '',
        pengemasan: '',
        masa_simpan_hari: '',
    };
}

function emptyLogbook() {
    return {
        id: uid(),
        tanggal_masuk: '',
        tanggal_keluar: '',
        jenis_limbah: '',
        sumber_limbah: '',
        jumlah_masuk_kg: '',
        jumlah_keluar_kg: '',
        pengemasan: '',
    };
}

function emptyManifest() {
    return {
        id: uid(),
        nomor_manifest: '',
        tanggal_manifest: '',
        nama_pengirim: '',
        alamat_pengirim: '',
        nama_fasilitas_penyimpanan: '',
        penanggung_jawab_pengirim: '',
        jabatan_pj_pengirim: '',
        kode_limbah: '',
        nama_limbah: '',
        nama_teknik: '',
        periode_limbah_mulai: '',
        periode_limbah_selesai: '',
        karakteristik_limbah: '',
        jenis_kemasan: '',
        jumlah_kemasan: '',
        jumlah_limbah_ton: '',
        keterangan_tambahan: '',
        tujuan_pengangkutan: '',
        nama_pengangkut: '',
        alamat_pengangkut: '',
        no_telepon_darurat: '',
        jumlah_ril: '',
        identitas_alat_angkut: '',
        waktu_mulai_pengangkutan: '',
        waktu_selesai_pengangkutan: '',
        penanggung_jawab_pengangkut: '',
        jabatan_pj_pengangkut: '',
        nama_penerima: '',
        alamat_penerima: '',
        no_telepon_penerima: '',
        jenis_pengelolaan: '',
        jumlah_diterima_kg: '',
        penanggung_jawab_penerima: '',
        jabatan_pj_penerima: '',
    };
}

function makeLogbookBulanList(semester, apiBulanList = null, forEdit = false) {
    const names = SEMESTER_BULAN[Number(semester)] || SEMESTER_BULAN[1];

    return names.map((nama, index) => {
        const fromApi = (apiBulanList || []).find((bulan) => bulan.nama === nama);
        const entries = (fromApi?.entries || []).map((entry) => ({
            ...entry,
            id: entry.id ?? uid(),
        }));

        let resolvedEntries = entries;
        if (!forEdit && index === 0 && resolvedEntries.length === 0) {
            resolvedEntries = [emptyLogbook()];
        }

        return {
            id: `semester-${semester}-bulan-${index + 1}`,
            nama,
            expanded: index === 0 || resolvedEntries.length > 0,
            entries: resolvedEntries,
        };
    });
}

function buildPayload(component) {
    return {
        semester: Number(component.selectedSemester),
        tahun: Number(component.selectedTahun),
        jenis_list: component.jenisList.map((item) => ({
            nama_limbah: item.nama_limbah,
            kode_limbah: item.kode_limbah,
            sumber_limbah: item.sumber_limbah,
            karakteristik: item.karakteristik,
            pengemasan: item.pengemasan,
            masa_simpan_hari: Number(item.masa_simpan_hari),
        })),
        logbook_bulan_list: component.logbookBulanList
            .map((bulan) => ({
                nama: bulan.nama,
                entries: bulan.entries
                    .filter((entry) => isLogbookTouched(entry))
                    .map((entry) => ({
                        tanggal_masuk: entry.tanggal_masuk,
                        tanggal_keluar: entry.tanggal_keluar || null,
                        jenis_limbah: entry.jenis_limbah,
                        sumber_limbah: entry.sumber_limbah,
                        jumlah_masuk_kg: toDecimal(entry.jumlah_masuk_kg),
                        jumlah_keluar_kg: toDecimal(entry.jumlah_keluar_kg),
                        pengemasan: entry.pengemasan || null,
                    }))
                    .filter((entry) => entry.jumlah_masuk_kg !== null),
            }))
            .filter((bulan) => bulan.entries.length > 0),
        manifest_list: component.manifestList
            .filter((item) => isManifestTouched(item))
            .map((item) => ({
                nomor_manifest: item.nomor_manifest,
                tanggal_manifest: item.tanggal_manifest,
                nama_pengirim: item.nama_pengirim,
                alamat_pengirim: item.alamat_pengirim,
                nama_fasilitas_penyimpanan: item.nama_fasilitas_penyimpanan || null,
                penanggung_jawab_pengirim: item.penanggung_jawab_pengirim || null,
                jabatan_pj_pengirim: item.jabatan_pj_pengirim || null,
                kode_limbah: item.kode_limbah,
                nama_limbah: item.nama_limbah,
                nama_teknik: item.nama_teknik || null,
                periode_limbah_mulai: item.periode_limbah_mulai || null,
                periode_limbah_selesai: item.periode_limbah_selesai || null,
                karakteristik_limbah: item.karakteristik_limbah,
                jenis_kemasan: item.jenis_kemasan,
                jumlah_kemasan: Number(item.jumlah_kemasan),
                jumlah_limbah_ton: toDecimal(item.jumlah_limbah_ton),
                keterangan_tambahan: item.keterangan_tambahan || null,
                tujuan_pengangkutan: item.tujuan_pengangkutan,
                nama_pengangkut: item.nama_pengangkut,
                alamat_pengangkut: item.alamat_pengangkut,
                no_telepon_darurat: item.no_telepon_darurat || null,
                jumlah_ril: item.jumlah_ril !== '' && item.jumlah_ril != null ? Number(item.jumlah_ril) : null,
                identitas_alat_angkut: item.identitas_alat_angkut || null,
                waktu_mulai_pengangkutan: item.waktu_mulai_pengangkutan || null,
                waktu_selesai_pengangkutan: item.waktu_selesai_pengangkutan || null,
                penanggung_jawab_pengangkut: item.penanggung_jawab_pengangkut || null,
                jabatan_pj_pengangkut: item.jabatan_pj_pengangkut || null,
                nama_penerima: item.nama_penerima,
                alamat_penerima: item.alamat_penerima,
                no_telepon_penerima: item.no_telepon_penerima || null,
                jenis_pengelolaan: item.jenis_pengelolaan,
                jumlah_diterima_kg: toDecimal(item.jumlah_diterima_kg),
                penanggung_jawab_penerima: item.penanggung_jawab_penerima || null,
                jabatan_pj_penerima: item.jabatan_pj_penerima || null,
            })),
    };
}

function isManifestTouched(item) {
    return Boolean(
        item.nomor_manifest ||
            item.tanggal_manifest ||
            item.nama_pengirim ||
            item.alamat_pengirim ||
            item.nama_fasilitas_penyimpanan ||
            item.penanggung_jawab_pengirim ||
            item.jabatan_pj_pengirim ||
            item.kode_limbah ||
            item.nama_limbah ||
            item.nama_teknik ||
            item.periode_limbah_mulai ||
            item.periode_limbah_selesai ||
            item.karakteristik_limbah ||
            item.jenis_kemasan ||
            item.jumlah_kemasan ||
            item.jumlah_limbah_ton ||
            item.keterangan_tambahan ||
            item.tujuan_pengangkutan ||
            item.nama_pengangkut ||
            item.alamat_pengangkut ||
            item.no_telepon_darurat ||
            item.jumlah_ril ||
            item.identitas_alat_angkut ||
            item.waktu_mulai_pengangkutan ||
            item.waktu_selesai_pengangkutan ||
            item.penanggung_jawab_pengangkut ||
            item.jabatan_pj_pengangkut ||
            item.nama_penerima ||
            item.alamat_penerima ||
            item.no_telepon_penerima ||
            item.jenis_pengelolaan ||
            item.jumlah_diterima_kg ||
            item.penanggung_jawab_penerima ||
            item.jabatan_pj_penerima,
    );
}

function isLogbookTouched(item) {
    return Boolean(
        item.tanggal_masuk ||
            item.tanggal_keluar ||
            item.jenis_limbah ||
            item.sumber_limbah ||
            item.jumlah_masuk_kg ||
            item.jumlah_keluar_kg ||
            item.pengemasan,
    );
}

export function registerPemantauanB3() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('pemantauanB3', (config = {}) => ({
            ...listPaginationMixin(),
            view: 'list',
            reports: JSON.parse(JSON.stringify(config.initialReports || [])),
            semesterToBulan: config.semesterToBulan || SEMESTER_BULAN,
            storeUrl: config.storeUrl ?? '',
            baseUrl: config.baseUrl ?? '',
            canManage: config.canManage ?? false,
            isReadOnly: false,
            saving: false,
            loadingEdit: false,
            suspendSemesterWatch: false,
            editingReportId: null,
            selectedSemester: '1',
            selectedTahun: String(config.defaultTahun || '2026'),
            jenisList: [],
            logbookBulanList: makeLogbookBulanList(1),
            manifestList: [],
            showSuccess: false,
            q: '',
            filterStatus: 'semua',
            expanded: {
                jenis: true,
                logbook: true,
                manifest: true,
            },

            init() {
                this.watchPaginationFilters(['q', 'filterStatus']);

                this.$watch('selectedSemester', async (semester, oldSemester) => {
                    if (this.suspendSemesterWatch) {
                        return;
                    }

                    if (this.loadingEdit || this.isReadOnly) {
                        return;
                    }

                    if (this.editingReportId && (!oldSemester || oldSemester === semester)) {
                        return;
                    }

                    if (oldSemester && oldSemester !== semester && this.hasLogbookData()) {
                        const confirmChange = await uiConfirm(
                            'Mengganti semester akan mengosongkan logbook yang sudah diisi. Lanjutkan?',
                        );
                        if (!confirmChange) {
                            this.revertSemesterSelection(oldSemester);

                            return;
                        }
                    }

                    this.logbookBulanList = makeLogbookBulanList(semester);
                });
            },

            revertSemesterSelection(previousValue) {
                this.suspendSemesterWatch = true;
                this.selectedSemester = previousValue;
                this.$nextTick(() => {
                    this.suspendSemesterWatch = false;
                });
            },

            hasLogbookData() {
                return this.logbookBulanList.some((bulan) =>
                    bulan.entries.some((entry) => isLogbookTouched(entry)),
                );
            },

            semesterLabel(semester = this.selectedSemester) {
                return Number(semester) === 1 ? 'Semester I' : 'Semester II';
            },

            statusClass(status) {
                return status === 'Selesai'
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                    : 'bg-blue-50 text-blue-700 border-blue-200';
            },

            filtered() {
                const query = (this.q || '').toLowerCase().trim();

                return this.reports.filter((r) => {
                    if (this.filterStatus === 'berlangsung' && r.status !== 'Berlangsung') {
                        return false;
                    }
                    if (this.filterStatus === 'selesai' && r.status !== 'Selesai') {
                        return false;
                    }
                    if (!query) {
                        return true;
                    }

                    const blob = [
                        r.tanggal,
                        r.nama_laporan,
                        r.jumlah,
                        r.progress,
                        r.status,
                        r.tahun,
                        this.semesterLabel(r.semester),
                    ]
                        .join(' ')
                        .toLowerCase();

                    return blob.includes(query);
                });
            },

            paginationMeta() {
                return this.paginateItems(this.filtered());
            },

            paginated() {
                return this.paginationMeta().items;
            },

            totalJenis() {
                return this.jenisList.filter((item) =>
                    item.nama_limbah &&
                    item.kode_limbah &&
                    item.sumber_limbah &&
                    item.karakteristik &&
                    item.pengemasan &&
                    item.masa_simpan_hari,
                ).length;
            },

            totalLogbook() {
                return this.logbookBulanList.reduce(
                    (total, bulan) => total + bulan.entries.filter((item) => isLogbookTouched(item)).length,
                    0,
                );
            },

            totalManifest() {
                return this.manifestList.filter((item) => isManifestTouched(item)).length;
            },

            openForm() {
                if (!this.canManage) {
                    return;
                }

                this.view = 'form';
                this.isReadOnly = false;
                this.editingReportId = null;
                this.selectedSemester = '1';
                this.selectedTahun = String(config.defaultTahun || '2026');
                this.jenisList = [];
                this.logbookBulanList = makeLogbookBulanList(1);
                this.manifestList = [];
                this.showSuccess = false;
                this.expanded = { jenis: true, logbook: true, manifest: true };
            },

            async openEditReport(report) {
                if (!this.canManage) {
                    return;
                }

                await this.loadReportIntoForm(report, false);
            },

            async openViewReport(report) {
                await this.loadReportIntoForm(report, true);
            },

            async loadReportIntoForm(report, readOnly) {
                this.editingReportId = report.id;
                this.isReadOnly = readOnly;
                this.view = 'form';
                this.showSuccess = false;
                this.loadingEdit = true;

                try {
                    const { data } = await requestJson(`${this.baseUrl}/${report.id}`, 'GET');

                    this.suspendSemesterWatch = true;
                    this.selectedSemester = String(data.semester ?? '1');
                    this.selectedTahun = String(data.tahun ?? config.defaultTahun ?? '2026');
                    this.jenisList = (data.jenisList || []).map((item) => ({
                        ...item,
                        id: item.id ?? uid(),
                    }));
                    this.logbookBulanList = makeLogbookBulanList(
                        Number(this.selectedSemester),
                        data.logbookBulanList || [],
                        true,
                    );
                    this.manifestList = (data.manifestList || []).map((item) => ({
                        ...item,
                        id: item.id ?? uid(),
                    }));
                    this.expanded = { jenis: true, logbook: true, manifest: this.manifestList.length > 0 };

                    await this.$nextTick();
                    this.suspendSemesterWatch = false;
                } catch (error) {
                    this.suspendSemesterWatch = false;
                    await uiAlert(error instanceof Error ? error.message : 'Gagal memuat data laporan.');
                    this.backToList();
                } finally {
                    this.loadingEdit = false;
                }
            },

            async tandaiSelesai(reportId) {
                try {
                    const { listItem } = await requestJson(`${this.baseUrl}/${reportId}/selesai`, 'PATCH');
                    if (listItem) {
                        this.reports = this.reports.map((row) =>
                            Number(row.id) === Number(reportId) ? listItem : row,
                        );
                    }
                } catch (error) {
                    await uiAlert(error instanceof Error ? error.message : 'Gagal menandai selesai.');
                }
            },

            async hapusLaporan(report) {
                const label = report?.nama_laporan
                    || `${this.semesterLabel(report?.semester)} ${report?.tahun || ''}`.trim();
                if (!await uiConfirm(`Hapus ${label}? Tindakan ini tidak dapat dibatalkan.`, {
                    destructive: true,
                    confirmLabel: 'Hapus',
                })) {
                    return;
                }

                try {
                    await requestJson(`${this.baseUrl}/${report.id}`, 'DELETE');
                    this.reports = this.reports.filter(
                        (row) => Number(row.id) !== Number(report.id),
                    );
                    if (Number(this.editingReportId) === Number(report.id)) {
                        this.backToList();
                    }
                } catch (error) {
                    await uiAlert(error instanceof Error ? error.message : 'Gagal menghapus laporan.');
                }
            },

            backToList() {
                this.view = 'list';
                this.showSuccess = false;
                this.editingReportId = null;
                this.isReadOnly = false;
            },

            toggle(section) {
                this.expanded[section] = !this.expanded[section];
            },

            toggleLogbookBulan(bulanId) {
                this.logbookBulanList = this.logbookBulanList.map((bulan) =>
                    bulan.id === bulanId ? { ...bulan, expanded: !bulan.expanded } : bulan,
                );
            },

            addJenis() {
                if (this.isReadOnly) {
                    return;
                }
                this.jenisList = [...this.jenisList, emptyJenis()];
            },

            removeJenis(id) {
                if (this.isReadOnly) {
                    return;
                }
                this.jenisList = this.jenisList.filter((item) => item.id !== id);
            },

            addLogbook(bulanId) {
                if (this.isReadOnly) {
                    return;
                }
                this.logbookBulanList = this.logbookBulanList.map((bulan) =>
                    bulan.id === bulanId
                        ? { ...bulan, expanded: true, entries: [...bulan.entries, emptyLogbook()] }
                        : bulan,
                );
            },

            removeLogbook(bulanId, entryId) {
                if (this.isReadOnly) {
                    return;
                }
                this.logbookBulanList = this.logbookBulanList.map((bulan) => {
                    if (bulan.id !== bulanId) {
                        return bulan;
                    }

                    return {
                        ...bulan,
                        entries: bulan.entries.filter((item) => item.id !== entryId),
                    };
                });
            },

            addManifest() {
                if (this.isReadOnly) {
                    return;
                }
                this.manifestList = [...this.manifestList, emptyManifest()];
            },

            removeManifest(id) {
                if (this.isReadOnly) {
                    return;
                }
                this.manifestList = this.manifestList.filter((item) => item.id !== id);
            },

            validateJenis() {
                if (!this.jenisList.length) {
                    return true;
                }

                return this.jenisList.every(
                    (item) =>
                        item.nama_limbah &&
                        item.kode_limbah &&
                        item.sumber_limbah &&
                        item.karakteristik &&
                        item.pengemasan &&
                        item.masa_simpan_hari,
                );
            },

            validateLogbook() {
                const filledEntries = this.logbookBulanList.flatMap((bulan) =>
                    bulan.entries.filter((item) => isLogbookTouched(item)),
                );

                if (!filledEntries.length) {
                    return false;
                }

                return filledEntries.every(
                    (item) => item.tanggal_masuk && item.jenis_limbah && item.sumber_limbah && item.jumlah_masuk_kg,
                );
            },

            validateManifest() {
                const filledEntries = this.manifestList.filter((item) => isManifestTouched(item));

                if (!filledEntries.length) {
                    return true;
                }

                return filledEntries.every(
                    (item) =>
                        item.nomor_manifest &&
                        item.tanggal_manifest &&
                        item.nama_pengirim &&
                        item.alamat_pengirim &&
                        item.kode_limbah &&
                        item.nama_limbah &&
                        item.karakteristik_limbah &&
                        item.jenis_kemasan &&
                        item.jumlah_kemasan &&
                        item.jumlah_limbah_ton &&
                        item.tujuan_pengangkutan &&
                        item.nama_pengangkut &&
                        item.alamat_pengangkut &&
                        item.nama_penerima &&
                        item.alamat_penerima &&
                        item.jenis_pengelolaan,
                );
            },

            async handleSubmit() {
                if (this.isReadOnly || !this.canManage) {
                    return;
                }

                if (!this.validateJenis()) {
                    await uiAlert('Lengkapi semua field pada setiap jenis limbah yang ditambahkan');
                    this.expanded.jenis = true;
                    return;
                }

                if (!this.validateLogbook()) {
                    await uiAlert('Lengkapi data Logbook Limbah B3');
                    this.expanded.logbook = true;
                    return;
                }

                if (!this.validateManifest()) {
                    await uiAlert('Lengkapi semua field manifest yang sudah mulai diisi');
                    this.expanded.manifest = true;
                    return;
                }

                this.saving = true;

                try {
                    const payload = buildPayload(this);
                    const url = this.editingReportId
                        ? `${this.baseUrl}/${this.editingReportId}`
                        : this.storeUrl;
                    const method = this.editingReportId ? 'PUT' : 'POST';
                    const result = await requestJson(url, method, payload);

                    if (result.listItem) {
                        const exists = this.reports.some(
                            (row) => Number(row.id) === Number(result.listItem.id),
                        );
                        this.reports = exists
                            ? this.reports.map((row) =>
                                  Number(row.id) === Number(result.listItem.id)
                                      ? result.listItem
                                      : row,
                              )
                            : [result.listItem, ...this.reports];
                    }

                    this.showSuccess = true;
                } catch (error) {
                    await uiAlert(error instanceof Error ? error.message : 'Gagal menyimpan laporan.');
                } finally {
                    this.saving = false;
                }
            },

            closeSuccessAndList() {
                this.showSuccess = false;
                this.view = 'list';
                this.editingReportId = null;
            },
        }));
    });
}
