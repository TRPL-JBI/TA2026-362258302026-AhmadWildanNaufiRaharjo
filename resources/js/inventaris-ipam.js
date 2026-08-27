export function registerInventarisIpam() {
    window.Alpine.data('inventarisIpam', (initial = {}) => ({
            showUnitForm: Boolean(initial.showUnitForm),
            showTitikForm: Boolean(initial.showTitikForm),
            editingTitik: null,
            formUnitNama: initial.formUnitNama ?? '',
            formUnitDeskripsi: initial.formUnitDeskripsi ?? '',
            formTitikUnitId: initial.formTitikUnitId ?? '',
            formTitikLokasi: initial.formTitikLokasi ?? '',
            formTitikDeskripsi: initial.formTitikDeskripsi ?? '',
            deleteTitikId: null,
            deleteUnitId: null,
            titikItems: initial.titikItems ?? [],
            unitStoreUrl: initial.unitStoreUrl ?? '',
            unitBaseUrl: initial.unitBaseUrl ?? '',
            titikStoreUrl: initial.titikStoreUrl ?? '',
            titikBaseUrl: initial.titikBaseUrl ?? '',

            openUnitCreate() {
                this.formUnitNama = '';
                this.formUnitDeskripsi = '';
                this.showUnitForm = true;
            },

            closeUnitForm() {
                this.showUnitForm = false;
            },

            openTitikCreate() {
                this.editingTitik = null;
                this.formTitikUnitId = '';
                this.formTitikLokasi = '';
                this.formTitikDeskripsi = '';
                this.showTitikForm = true;
            },

            openTitikEditById(id) {
                const item = this.titikItems.find((row) => Number(row.id) === Number(id));
                if (!item) {
                    return;
                }
                this.openTitikEdit(item);
            },

            openTitikEdit(item) {
                this.editingTitik = item;
                this.formTitikUnitId = String(item.unit_ipam_id ?? '');
                this.formTitikLokasi = item.titik_lokasi ?? '';
                this.formTitikDeskripsi = item.deskripsi ?? '';
                this.showTitikForm = true;
            },

            closeTitikForm() {
                this.showTitikForm = false;
                this.editingTitik = null;
            },

            confirmDeleteTitik(id) {
                this.deleteTitikId = id;
            },

            cancelDeleteTitik() {
                this.deleteTitikId = null;
            },

            confirmDeleteUnit(id) {
                this.deleteUnitId = id;
            },

            cancelDeleteUnit() {
                this.deleteUnitId = null;
            },

            titikFormAction() {
                return this.editingTitik
                    ? `${this.titikBaseUrl}/${this.editingTitik.id}`
                    : this.titikStoreUrl;
            },

            titikDeleteAction() {
                return this.deleteTitikId ? `${this.titikBaseUrl}/${this.deleteTitikId}` : '#';
            },

            unitDeleteAction() {
                return this.deleteUnitId ? `${this.unitBaseUrl}/${this.deleteUnitId}` : '#';
            },
        }));
}
