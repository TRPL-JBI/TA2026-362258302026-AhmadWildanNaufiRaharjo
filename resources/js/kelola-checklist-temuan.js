import { uiConfirm } from './ui-dialog';

function computeRiskLevel(skor) {
    if (skor <= 3) return 'Rendah';
    if (skor <= 7) return 'Sedang';
    if (skor <= 12) return 'Tinggi';
    return 'Sangat Tinggi';
}

function computeRiskStyle(level) {
    switch (level) {
        case 'Rendah':
            return {
                badge: 'bg-emerald-100 text-emerald-700 border-emerald-200',
                bar: 'bg-emerald-400',
                barBg: 'bg-emerald-100',
                text: 'text-emerald-700',
                borderR: 'border-emerald-200',
            };
        case 'Sedang':
            return {
                badge: 'bg-yellow-100 text-yellow-700 border-yellow-200',
                bar: 'bg-yellow-400',
                barBg: 'bg-yellow-100',
                text: 'text-yellow-700',
                borderR: 'border-yellow-200',
            };
        case 'Tinggi':
            return {
                badge: 'bg-orange-100 text-orange-700 border-orange-200',
                bar: 'bg-orange-400',
                barBg: 'bg-orange-100',
                text: 'text-orange-700',
                borderR: 'border-orange-200',
            };
        case 'Sangat Tinggi':
            return {
                badge: 'bg-red-100 text-red-700 border-red-200',
                bar: 'bg-red-500',
                barBg: 'bg-red-100',
                text: 'text-red-700',
                borderR: 'border-red-200',
            };
        default:
            return {
                badge: 'bg-gray-100 text-gray-500 border-gray-200',
                bar: 'bg-gray-300',
                barBg: 'bg-gray-100',
                text: 'text-gray-500',
                borderR: 'border-gray-200',
            };
    }
}

const pLabel = { 1: 'Sangat Jarang', 2: 'Jarang', 3: 'Kadang-kadang', 4: 'Sering', 5: 'Sangat Sering' };
const sLabel = {
    1: 'Tidak Signifikan',
    2: 'Minor',
    3: 'Moderate',
    4: 'Mayor',
    5: 'Catastrophic',
};

function postAction(url, method = 'POST', fields = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrf) {
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrf;
        form.appendChild(token);
    }

    if (method !== 'POST') {
        const spoof = document.createElement('input');
        spoof.type = 'hidden';
        spoof.name = '_method';
        spoof.value = method;
        form.appendChild(spoof);
    }

    Object.entries(fields).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value ?? '';
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

export function registerKelolaChecklistTemuan() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('kelolaChecklistTemuan', (config = {}) => ({
            checklists: config.checklists ?? [],
            lokasiOptions: config.lokasiOptions ?? [],
            roleScope: config.roleScope ?? 'petugas',
            canCreate: Boolean(config.canCreate),
            urls: config.urls ?? {},
            showChecklistModal: Boolean(config.oldChecklist?.nama || config.oldChecklist?.lokasiId),
            editingChecklist: null,
            showItemModal: Boolean(config.oldItem?.namaItem),
            editingItem: null,
            checklistForm: {
                nama: config.oldChecklist?.nama ?? '',
                lokasiId: config.oldChecklist?.lokasiId ?? '',
            },
            itemForm: {
                namaItem: config.oldItem?.namaItem ?? '',
                deskripsi: config.oldItem?.deskripsi ?? '',
                probability: config.oldItem?.probability ?? 0,
                severity: config.oldItem?.severity ?? 0,
            },
            pLabel,
            sLabel,

            itemSkor(item) {
                return (item.probability || 0) * (item.severity || 0);
            },
            itemLevel(item) {
                return computeRiskLevel(this.itemSkor(item));
            },
            itemStyle(item) {
                return computeRiskStyle(this.itemLevel(item));
            },
            totalItems() {
                return this.checklists.reduce((a, c) => a + c.items.length, 0);
            },
            totalAktif() {
                return this.checklists.filter((c) => c.status === 'Aktif').length;
            },
            totalItemAktif() {
                return this.checklists.reduce((a, c) => a + c.items.filter((i) => i.aktif).length, 0);
            },
            risikoTinggiCount(items) {
                return items.filter((i) => {
                    const s = computeRiskLevel(i.probability * i.severity);
                    return s === 'Tinggi' || s === 'Sangat Tinggi';
                }).length;
            },
            modalItemSkor() {
                return (this.itemForm.probability || 0) * (this.itemForm.severity || 0);
            },
            modalItemLevel() {
                const skor = this.modalItemSkor();
                return skor > 0 ? computeRiskLevel(skor) : null;
            },
            modalItemStyle() {
                const level = this.modalItemLevel();
                return level ? computeRiskStyle(level) : null;
            },

            checklistFormAction() {
                if (this.editingChecklist?.id) {
                    return `${this.urls.updateChecklist}/${this.editingChecklist.id}`;
                }
                return this.urls.storeChecklist;
            },

            openChecklistModal(checklist) {
                this.editingChecklist = checklist;
                this.checklistForm = {
                    nama: checklist?.namaChecklist ?? '',
                    lokasiId: checklist?.lokasiId ?? this.lokasiOptions[0]?.id ?? '',
                };
                this.showChecklistModal = true;
            },
            closeChecklistModal() {
                this.showChecklistModal = false;
                this.editingChecklist = null;
            },
            saveChecklist() {
                const nama = (this.checklistForm.nama || '').trim();
                const lokasiId = this.checklistForm.lokasiId;
                if (!nama || !lokasiId) return;

                if (this.editingChecklist?.id) {
                    postAction(`${this.urls.updateChecklist}/${this.editingChecklist.id}`, 'PUT', {
                        nama_checklist: nama,
                        lokasi_id: lokasiId,
                    });
                    return;
                }

                postAction(this.urls.storeChecklist, 'POST', {
                    nama_checklist: nama,
                    lokasi_id: lokasiId,
                });
            },
            toggleChecklistStatus(id) {
                postAction(`${this.urls.toggleChecklist}/${id}/status`, 'PATCH');
            },
            async deleteChecklist(id) {
                if (!await uiConfirm('Hapus checklist ini beserta semua itemnya?', {
                    destructive: true,
                    confirmLabel: 'Hapus',
                })) {
                    return;
                }

                postAction(`${this.urls.destroyChecklist}/${id}`, 'DELETE');
            },
            toggleExpand(id) {
                this.checklists = this.checklists.map((c) =>
                    c.id === id ? { ...c, expanded: !c.expanded } : c,
                );
            },

            openItemModal(checklistId, item) {
                this.editingItem = { checklistId, item: item || {} };
                const it = item || {};
                this.itemForm = {
                    namaItem: it.namaItem ?? '',
                    deskripsi: it.deskripsi ?? '',
                    probability: it.probability ?? 0,
                    severity: it.severity ?? 0,
                };
                this.showItemModal = true;
            },
            closeItemModal() {
                this.showItemModal = false;
                this.editingItem = null;
            },
            saveItem() {
                if (!this.editingItem) return;
                const namaItem = (this.itemForm.namaItem || '').trim();
                const { probability, severity } = this.itemForm;
                if (!namaItem || probability === 0 || severity === 0) return;

                const { checklistId, item } = this.editingItem;

                if (item.id) {
                    postAction(`${this.urls.updateItem}/${item.id}`, 'PUT', {
                        nama_item: namaItem,
                        deskripsi: this.itemForm.deskripsi || '',
                        probability,
                        severity,
                    });
                    return;
                }

                postAction(`${this.urls.storeItem}/${checklistId}/items`, 'POST', {
                    nama_item: namaItem,
                    deskripsi: this.itemForm.deskripsi || '',
                    probability,
                    severity,
                });
            },
            toggleItem(checklistId, itemId) {
                postAction(`${this.urls.toggleItem}/${itemId}/status`, 'PATCH');
            },
            async deleteItem(checklistId, itemId) {
                if (!await uiConfirm('Hapus item bahaya ini?', {
                    destructive: true,
                    confirmLabel: 'Hapus',
                })) {
                    return;
                }

                postAction(`${this.urls.destroyItem}/${itemId}`, 'DELETE');
            },
        }));
    });
}
