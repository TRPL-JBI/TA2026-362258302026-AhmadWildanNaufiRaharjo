import { csrfToken } from './patroli-api';
import { listPaginationMixin } from './list-pagination';
import { uiAlert, uiConfirm } from './ui-dialog';

function titikByUnitId(titikIpamData, unitId) {
    return (titikIpamData || []).filter((t) => Number(t.unit_id) === Number(unitId));
}

function emptyTitikRow() {
    return { ph: '', alt: '', salmonella: '', status: '' };
}

function normalizeDataTitik(dataTitik, titikIpamData, unitId) {
    const normalized = {};

    titikByUnitId(titikIpamData, unitId).forEach((t) => {
        const id = Number(t.id);
        const raw = dataTitik?.[id] ?? dataTitik?.[String(id)] ?? emptyTitikRow();

        normalized[id] = {
            ph: raw.ph ?? '',
            alt: raw.alt ?? '',
            salmonella: raw.salmonella ?? '',
            status: raw.status ?? '',
        };
    });

    return normalized;
}

function isFilled(value) {
    return value !== '' && value !== null && value !== undefined;
}

function titikRowPartial(d) {
    if (!d) {
        return false;
    }

    return isFilled(d.ph) || isFilled(d.alt) || isFilled(d.salmonella) || isFilled(d.status);
}

function titikRowComplete(d) {
    return (
        titikRowPartial(d)
        && isFilled(d.ph)
        && isFilled(d.alt)
        && isFilled(d.salmonella)
        && isFilled(d.status)
    );
}

function normalizeMinggu(minggu, titikIpamData, unitId, defaultExpanded = false) {
    return {
        mingguKe: Number(minggu.mingguKe) || 1,
        expanded: minggu.expanded ?? defaultExpanded,
        dataTitik: normalizeDataTitik(minggu.dataTitik, titikIpamData, unitId),
    };
}

function normalizeUnit(unit, titikIpamData) {
    const unitId = Number(unit.unitId);
    const unitExpanded = unit.expanded ?? true;

    return {
        unitId,
        expanded: unitExpanded,
        mingguList: (unit.mingguList || []).map((minggu, index) =>
            normalizeMinggu(minggu, titikIpamData, unitId, unitExpanded && index === 0),
        ),
    };
}

function normalizeUnits(units, titikIpamData) {
    return (units || []).map((unit) => normalizeUnit(unit, titikIpamData));
}

function createWeek(titikIpamData, unitId, mingguKe, expanded = true) {
    return normalizeMinggu({ mingguKe, dataTitik: {}, expanded }, titikIpamData, unitId, expanded);
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

function normalizeAlt(value) {
    return String(value ?? '').trim().replace(/\s+/g, ' ');
}

function toDecimal(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }

    const normalized = String(value).replace(',', '.').trim();
    const num = parseFloat(normalized);

    return Number.isFinite(num) ? num : null;
}

function buildPayload(component) {
    return {
        bulan: component.bulan,
        tahun: Number(component.tahun),
        units: component.units.map((unit) => ({
            unit_id: unit.unitId,
            minggu_list: unit.mingguList.map((minggu) => ({
                minggu_ke: minggu.mingguKe,
                data_titik: component
                    .titikByUnit(unit.unitId)
                    .map((titik) => {
                        const d = minggu.dataTitik[titik.id] || {};
                        return {
                            titik_id: titik.id,
                            ph: toDecimal(d.ph),
                            alt: normalizeAlt(d.alt),
                            salmonella: d.salmonella || null,
                            status: d.status || null,
                        };
                    })
                    .filter((row) => {
                        const d = {
                            ph: row.ph,
                            alt: row.alt,
                            salmonella: row.salmonella,
                            status: row.status,
                        };

                        return titikRowComplete(d);
                    }),
            })),
        })),
        notes: {
            kendala: component.notes.kendala,
            rekomendasi: component.notes.rekomendasi,
            kesimpulan: component.notes.kesimpulan,
        },
    };
}

export function registerPemantauanIpam() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('pemantauanIpam', (config = {}) => {
            const unitIpamData = config.unitIpamData || [];
            const titikIpamData = config.titikIpamData || [];

            return {
                ...listPaginationMixin(),
                view: 'list',
                reports: JSON.parse(JSON.stringify(config.initialReports || [])),
                q: '',
                filterStatus: 'semua',
                bulanOptions: config.bulanOptions || [],
                storeUrl: config.storeUrl ?? '',
                baseUrl: config.baseUrl ?? '',
                saving: false,
                loadingEdit: false,
                editingReportId: null,

                bulan: config.defaultBulan || 'Januari',
                tahun: String(config.defaultTahun || new Date().getFullYear()),

                units: [],
                rekapRowsList: [],
                notes: { kendala: '', rekomendasi: '', kesimpulan: '' },
                showSuccess: false,

                init() {
                    this.watchPaginationFilters(['q', 'filterStatus']);

                    this.$watch('units', () => {
                        this.rekapRowsList = this.rekapRows();
                    }, { deep: true });
                    if (this.units.length === 0 && unitIpamData.length > 0) {
                        const unitId = unitIpamData[0]?.id || 1;
                        this.units = normalizeUnits(
                            [{ unitId, mingguList: [createWeek(titikIpamData, unitId, 1)] }],
                            titikIpamData,
                        );
                    }
                },

                /** Memastikan objek titik ada sebelum x-model (hindari error Alpine di konsol). */
                titikData(minggu, titikId) {
                    const id = Number(titikId);
                    if (!minggu.dataTitik) {
                        minggu.dataTitik = {};
                    }
                    if (!minggu.dataTitik[id]) {
                        minggu.dataTitik[id] = emptyTitikRow();
                    }
                    return minggu.dataTitik[id];
                },

                unitName(unitId) {
                    return unitIpamData.find((u) => Number(u.id) === Number(unitId))?.nama_unit || '-';
                },

                titikByUnit(unitId) {
                    return titikByUnitId(titikIpamData, unitId);
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
                            r.status,
                            r.bulan,
                            r.tahun,
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

                availableUnits() {
                    return unitIpamData.filter((u) => !this.units.some((x) => Number(x.unitId) === Number(u.id)));
                },

                openForm() {
                    this.editingReportId = null;
                    this.view = 'form';
                    this.showSuccess = false;
                    this.bulan = config.defaultBulan || 'Januari';
                    this.tahun = String(config.defaultTahun || new Date().getFullYear());
                    this.notes = { kendala: '', rekomendasi: '', kesimpulan: '' };

                    if (unitIpamData.length > 0) {
                        const unitId = unitIpamData[0].id;
                        this.units = normalizeUnits(
                            [{ unitId, mingguList: [createWeek(titikIpamData, unitId, 1)] }],
                            titikIpamData,
                        );
                    } else {
                        this.units = [];
                    }
                },

                async openEditReport(r) {
                    this.editingReportId = r.id;
                    this.view = 'form';
                    this.showSuccess = false;
                    this.loadingEdit = true;

                    try {
                        const { data } = await requestJson(`${this.baseUrl}/${r.id}`, 'GET');
                        this.bulan = data.bulan || this.bulan;
                        this.tahun = String(data.tahun ?? this.tahun);
                        this.notes = { ...this.notes, ...(data.notes || {}) };
                        this.units = normalizeUnits(
                            (data.units || []).map((unit) => ({
                                unitId: unit.unitId,
                                mingguList: unit.mingguList || [],
                            })),
                            titikIpamData,
                        );
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
                                String(row.id) === String(reportId) ? listItem : row,
                            );
                        }
                    } catch (error) {
                        await uiAlert(error instanceof Error ? error.message : 'Gagal menandai selesai.');
                    }
                },

                async hapusLaporan(report) {
                    const label = report?.nama_laporan || report?.tanggal || 'laporan ini';
                    if (!await uiConfirm(`Hapus ${label}? Tindakan ini tidak dapat dibatalkan.`, {
                        destructive: true,
                        confirmLabel: 'Hapus',
                    })) {
                        return;
                    }

                    try {
                        await requestJson(`${this.baseUrl}/${report.id}`, 'DELETE');
                        this.reports = this.reports.filter(
                            (row) => String(row.id) !== String(report.id),
                        );
                        if (String(this.editingReportId) === String(report.id)) {
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

                tambahUnit(unitIdRaw) {
                    const unitId = Number(unitIdRaw);
                    if (!unitId || this.units.some((u) => Number(u.unitId) === unitId)) return;
                    this.units = normalizeUnits(
                        [...this.units, { unitId, mingguList: [createWeek(titikIpamData, unitId, 1)] }],
                        titikIpamData,
                    );
                },

                tambahMinggu(unitId) {
                    this.units = normalizeUnits(
                        this.units.map((u) => {
                            if (Number(u.unitId) !== Number(unitId)) return u;
                            const mingguKe = u.mingguList.length + 1;
                            return {
                                ...u,
                                mingguList: [...u.mingguList, createWeek(titikIpamData, unitId, mingguKe)],
                            };
                        }),
                        titikIpamData,
                    );
                },

                hapusMinggu(unitId, mingguKe) {
                    this.units = normalizeUnits(
                        this.units.map((u) => {
                            if (Number(u.unitId) !== Number(unitId)) return u;
                            if (u.mingguList.length <= 1) return u;

                            const mingguList = u.mingguList
                                .filter((m) => Number(m.mingguKe) !== Number(mingguKe))
                                .map((m, index) => ({ ...m, mingguKe: index + 1 }));

                            return { ...u, mingguList };
                        }),
                        titikIpamData,
                    );
                },

                totalMinggu() {
                    return this.units.reduce((acc, u) => acc + (u.mingguList?.length || 0), 0);
                },

                toggleUnitExpand(unitIdx) {
                    this.units = this.units.map((u, i) =>
                        i === unitIdx ? { ...u, expanded: !u.expanded } : u,
                    );
                },

                toggleMingguExpand(unitIdx, mingguIdx) {
                    this.units = this.units.map((u, i) => {
                        if (i !== unitIdx) {
                            return u;
                        }

                        return {
                            ...u,
                            mingguList: u.mingguList.map((m, j) =>
                                j === mingguIdx ? { ...m, expanded: !m.expanded } : m,
                            ),
                        };
                    });
                },

                mingguTitikTerisi(unitId, minggu) {
                    return this.titikByUnit(unitId).filter((t) =>
                        titikRowComplete(minggu.dataTitik?.[t.id] || minggu.dataTitik?.[String(t.id)]),
                    ).length;
                },

                rekapRows() {
                    const rows = [];
                    this.units.forEach((u) => {
                        u.mingguList.forEach((m) => {
                            const titik = this.titikByUnit(u.unitId);
                            let baik = 0;
                            let tidakBaik = 0;
                            let sumPh = 0;
                            let cntPh = 0;
                            let sumAlt = 0;
                            let cntAlt = 0;
                            let salmonellaPositif = 0;

                            titik.forEach((t) => {
                                const d = m.dataTitik?.[t.id] || m.dataTitik?.[String(t.id)] || {};
                                if (!titikRowComplete(d)) {
                                    return;
                                }
                                if ((d.status || '') === 'Baik') baik++;
                                if ((d.status || '') === 'Tidak Baik') tidakBaik++;
                                if ((d.salmonella || '') === 'Positif') salmonellaPositif++;

                                const ph = parseFloat(d.ph);
                                if (!Number.isNaN(ph)) {
                                    sumPh += ph;
                                    cntPh++;
                                }
                                const altNum = parseFloat(String(d.alt).replace(',', '.'));
                                if (!Number.isNaN(altNum) && /^\d+([,.]\d+)?$/.test(String(d.alt).trim())) {
                                    sumAlt += altNum;
                                    cntAlt++;
                                }
                            });

                            const rataPh = cntPh ? (sumPh / cntPh).toFixed(1) : '-';
                            const rataAlt = cntAlt ? Math.round(sumAlt / cntAlt) : '-';

                            rows.push({
                                key: `${u.unitId}-${m.mingguKe}`,
                                unit: this.unitName(u.unitId),
                                minggu: m.mingguKe,
                                jumlahTitik: titik.length,
                                baik,
                                tidakBaik,
                                rataPh,
                                rataAlt,
                                salmonellaPositif,
                            });
                        });
                    });
                    return rows;
                },

                async handleSubmit() {
                    let filledTitik = 0;

                    for (const u of this.units) {
                        for (const m of u.mingguList) {
                            for (const t of this.titikByUnit(u.unitId)) {
                                const d = m.dataTitik?.[t.id] || m.dataTitik?.[String(t.id)] || {};
                                const hasAny = titikRowPartial(d);
                                const hasAll = titikRowComplete(d);

                                if (hasAny && !hasAll) {
                                    await uiAlert(
                                        `Lengkapi semua field (pH, ALT, Salmonella, Status) untuk titik "${t.nama_titik}" di ${this.unitName(u.unitId)}, Minggu ${m.mingguKe}, atau kosongkan semuanya.`,
                                    );
                                    return;
                                }

                                if (hasAll) {
                                    const ph = toDecimal(d.ph);
                                    if (ph === null || ph < 0 || ph > 14) {
                                        await uiAlert(`Nilai pH titik "${t.nama_titik}" harus antara 0–14.`);
                                        return;
                                    }

                                    filledTitik++;
                                }
                            }
                        }
                    }

                    if (filledTitik === 0) {
                        await uiAlert('Minimal isi data lengkap untuk satu titik sebelum menyimpan.');
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
                                (row) => String(row.id) === String(result.listItem.id),
                            );
                            this.reports = exists
                                ? this.reports.map((row) =>
                                      String(row.id) === String(result.listItem.id)
                                          ? result.listItem
                                          : row,
                                  )
                                : [result.listItem, ...this.reports];
                        }

                        if (result.data?.id) {
                            this.editingReportId = result.data.id;
                        }

                        this.showSuccess = true;
                    } catch (error) {
                        await uiAlert(error instanceof Error ? error.message : 'Gagal menyimpan laporan.');
                    } finally {
                        this.saving = false;
                    }
                },

                goBackToList() {
                    this.backToList();
                },
            };
        });
    });
}
