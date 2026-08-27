import { csrfToken } from './patroli-api';
import { listPaginationMixin } from './list-pagination';
import { uiAlert, uiConfirm } from './ui-dialog';

function emptyEvaluasi() {
    return {
        jenisDampak: '',
        sumberDampak: '',
        parameterPemantauan: '',
        tolakUkur: '',
        lokasiPengelolaan: '',
        evaluasiHasil: '',
        tindakanPerbaikan: '',
    };
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

function newCatatanId() {
    return `tmp-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}

function emptyCatatanRow() {
    return {
        id: newCatatanId(),
        tanggal: '',
        debitIn: '',
        debitOut: '',
        pH: '',
        suhu: '',
    };
}

function isFilled(value) {
    return value !== '' && value !== null && value !== undefined;
}

function toDecimal(value) {
    if (! isFilled(value)) {
        return null;
    }

    const normalized = String(value).replace(',', '.').trim();
    const num = parseFloat(normalized);

    return Number.isFinite(num) ? num : null;
}

function buildPayload(component) {
    return {
        triwulan_key: component.selectedTriwulan,
        tahun: Number(component.selectedTahun),
        bulan_list: component.bulanList
            .map((bulan) => ({
                nama: bulan.nama,
                catatan: bulan.catatan
                    .filter((catatan) =>
                        isFilled(catatan.tanggal)
                        && isFilled(catatan.debitIn)
                        && isFilled(catatan.debitOut)
                        && isFilled(catatan.pH)
                        && isFilled(catatan.suhu),
                    )
                    .map((catatan) => ({
                        tanggal: catatan.tanggal,
                        debit_in: toDecimal(catatan.debitIn),
                        debit_out: toDecimal(catatan.debitOut),
                        ph: toDecimal(catatan.pH),
                        suhu: toDecimal(catatan.suhu),
                    }))
                    .filter((catatan) =>
                        catatan.debit_in !== null
                        && catatan.debit_out !== null
                        && catatan.ph !== null
                        && catatan.suhu !== null,
                    ),
            }))
            .filter((bulan) => bulan.catatan.length > 0),
        evaluasi: {
            jenis_dampak: component.evaluasi.jenisDampak,
            sumber_dampak: component.evaluasi.sumberDampak,
            parameter_pemantauan: component.evaluasi.parameterPemantauan,
            tolak_ukur: component.evaluasi.tolakUkur,
            lokasi_pengelolaan: component.evaluasi.lokasiPengelolaan,
            evaluasi_hasil: component.evaluasi.evaluasiHasil,
            tindakan_perbaikan: component.evaluasi.tindakanPerbaikan,
        },
    };
}

export function registerPemantauanIpal() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('pemantauanIpal', (config = {}) => ({
            ...listPaginationMixin(),
            view: 'list',
            reports: JSON.parse(JSON.stringify(config.initialReports || [])),
            triwulanToBulan: config.triwulanToBulan || {},
            storeUrl: config.storeUrl ?? '',
            baseUrl: config.baseUrl ?? '',
            saving: false,
            loadingEdit: false,
            suspendTriwulanWatch: false,

            editingReportId: null,
            selectedTriwulan: '',
            selectedTahun: String(config.defaultTahun || '2026'),
            bulanList: [],
            evaluasi: emptyEvaluasi(),
            showSuccess: false,
            q: '',
            filterStatus: 'semua',

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
                        r.status,
                        r.tahun,
                        r.triwulan,
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

            statusClass(status) {
                return status === 'Selesai'
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                    : 'bg-blue-50 text-blue-700 border-blue-200';
            },

            get triwulanKeys() {
                return Object.keys(this.triwulanToBulan);
            },

            init() {
                this.watchPaginationFilters(['q', 'filterStatus']);

                this.$watch('selectedTriwulan', async (v, oldV) => {
                    if (this.suspendTriwulanWatch) {
                        return;
                    }

                    if (!v) {
                        this.bulanList = [];
                        return;
                    }
                    if (this.loadingEdit) {
                        return;
                    }

                    // Muat edit: triwulan di-set programatik, jangan timpa data dari server.
                    if (this.editingReportId) {
                        if (!oldV || oldV === v) {
                            return;
                        }
                        const confirmChange = await uiConfirm(
                            'Mengganti triwulan akan mengosongkan catatan yang sudah diisi. Lanjutkan?',
                        );
                        if (!confirmChange) {
                            this.revertTriwulanSelection(oldV);

                            return;
                        }
                        this.applyBulanTemplate(v);
                        return;
                    }

                    if (oldV && oldV !== v && this.bulanList.length > 0) {
                        const confirmChange = await uiConfirm(
                            'Mengganti triwulan akan mengosongkan catatan yang sudah diisi. Lanjutkan?',
                        );
                        if (!confirmChange) {
                            this.revertTriwulanSelection(oldV);

                            return;
                        }
                    }
                    this.applyBulanTemplate(v);
                });
            },

            revertTriwulanSelection(previousValue) {
                this.suspendTriwulanWatch = true;
                this.selectedTriwulan = previousValue;
                this.$nextTick(() => {
                    this.suspendTriwulanWatch = false;
                });
            },

            applyBulanTemplate(triwulanKey) {
                const bulanNames = this.triwulanToBulan[triwulanKey] || [];
                this.bulanList = bulanNames.map((nama, index) => ({
                    nama,
                    catatan: index === 0 ? [emptyCatatanRow()] : [],
                    expanded: index === 0,
                }));
            },

            toggleBulanExpand(indexBulan) {
                this.bulanList = this.bulanList.map((b, i) =>
                    i === indexBulan ? { ...b, expanded: !b.expanded } : b,
                );
            },

            openForm() {
                this.editingReportId = null;
                this.view = 'form';
                this.showSuccess = false;
                this.selectedTriwulan = '';
                this.selectedTahun = String(config.defaultTahun || '2026');
                this.bulanList = [];
                this.evaluasi = emptyEvaluasi();
            },

            async openEditReport(r) {
                this.editingReportId = r.id;
                this.view = 'form';
                this.showSuccess = false;
                this.loadingEdit = true;

                try {
                    const { data } = await requestJson(`${this.baseUrl}/${r.id}`, 'GET');
                    const bulanList = (data.bulanList || []).map((bulan) => ({
                        ...bulan,
                        catatan: (bulan.catatan || []).map((catatan) => ({
                            ...catatan,
                            id: catatan.id ?? newCatatanId(),
                        })),
                        expanded: (bulan.catatan || []).length > 0,
                    }));

                    if (bulanList.length > 0 && !bulanList.some((b) => b.expanded)) {
                        bulanList[0].expanded = true;
                    }

                    this.evaluasi = { ...emptyEvaluasi(), ...(data.evaluasi || {}) };
                    this.bulanList = bulanList;
                    this.selectedTahun = String(data.tahun ?? config.defaultTahun ?? '2026');
                    this.selectedTriwulan = data.triwulanKey || '';

                    await this.$nextTick();
                } catch (error) {
                    await uiAlert(error instanceof Error ? error.message : 'Gagal memuat data laporan.');
                    this.backToList();
                } finally {
                    this.loadingEdit = false;
                }
            },

            async tandaiSelesai(reportId) {
                try {
                    const { listItem } = await requestJson(
                        `${this.baseUrl}/${reportId}/selesai`,
                        'PATCH',
                    );
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
                const label = report?.nama_laporan || report?.triwulan || 'laporan ini';
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
            },

            addCatatan(indexBulan) {
                this.bulanList = this.bulanList.map((b, i) => {
                    if (i !== indexBulan) return b;
                    return {
                        ...b,
                        expanded: true,
                        catatan: [...b.catatan, emptyCatatanRow()],
                    };
                });
            },

            removeCatatan(indexBulan, idCatatan) {
                this.bulanList = this.bulanList.map((b, i) => {
                    if (i !== indexBulan) return b;
                    return {
                        ...b,
                        catatan: b.catatan.filter((c) => c.id != idCatatan),
                    };
                });
            },

            totalCatatan() {
                return this.bulanList.reduce((acc, b) => acc + b.catatan.length, 0);
            },

            async handleSubmit() {
                if (!this.selectedTriwulan) {
                    await uiAlert('Pilih periode triwulan');
                    return;
                }

                const filledCatatan = [];
                for (const bulan of this.bulanList) {
                    for (const catatan of bulan.catatan) {
                        const hasAny = isFilled(catatan.tanggal)
                            || isFilled(catatan.debitIn)
                            || isFilled(catatan.debitOut)
                            || isFilled(catatan.pH)
                            || isFilled(catatan.suhu);
                        const hasAll = isFilled(catatan.tanggal)
                            && isFilled(catatan.debitIn)
                            && isFilled(catatan.debitOut)
                            && isFilled(catatan.pH)
                            && isFilled(catatan.suhu);

                        if (hasAny && ! hasAll) {
                            await uiAlert(`Lengkapi semua field catatan di bulan ${bulan.nama}`);
                            return;
                        }

                        if (! hasAll) {
                            continue;
                        }

                        const ph = toDecimal(catatan.pH);
                        const suhu = toDecimal(catatan.suhu);

                        if (ph === null || ph < 0 || ph > 14) {
                            await uiAlert(`Nilai pH di ${bulan.nama} harus angka antara 0 sampai 14 (contoh: 7.5).`);
                            return;
                        }

                        if (suhu === null || suhu < -10 || suhu > 100) {
                            await uiAlert(`Nilai suhu di ${bulan.nama} harus angka antara -10 sampai 100.`);
                            return;
                        }

                        filledCatatan.push({ bulan: bulan.nama, catatan });
                    }
                }

                if (filledCatatan.length === 0) {
                    await uiAlert('Minimal isi satu catatan harian sebelum menyimpan.');
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
                    } else if (this.editingReportId) {
                        const refreshed = await requestJson(
                            `${this.baseUrl}/${this.editingReportId}`,
                            'GET',
                        );
                        const listItem = {
                            id: refreshed.data.id,
                            triwulan: `${refreshed.data.triwulanKey} ${refreshed.data.tahun}`,
                            triwulanKey: refreshed.data.triwulanKey,
                            tahun: refreshed.data.tahun,
                            status: refreshed.data.status,
                            progress: 'Diperbarui',
                        };
                        this.reports = this.reports.map((row) =>
                            Number(row.id) === Number(this.editingReportId) ? listItem : row,
                        );
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
