export function registerInventarisApar() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('inventarisApar', (initial = {}) => ({
            showForm: Boolean(initial.showForm),
            editing: null,
            formLokasiId: initial.formLokasiId ?? '',
            formJenis: initial.formJenis ?? '',
            formKapasitas: initial.formKapasitas ?? '',
            formTanggalExpired: initial.formTanggalExpired ?? '',
            formKeterangan: initial.formKeterangan ?? '',
            deleteId: null,
            items: initial.items ?? [],
            storeUrl: initial.storeUrl ?? '',
            baseUrl: initial.baseUrl ?? '',

            openCreate() {
                this.editing = null;
                this.formLokasiId = '';
                this.formJenis = '';
                this.formKapasitas = '';
                this.formTanggalExpired = '';
                this.formKeterangan = '';
                this.showForm = true;
            },

            openEditById(id) {
                const item = this.items.find((row) => Number(row.id) === Number(id));
                if (!item) {
                    return;
                }
                this.openEdit(item);
            },

            openEdit(item) {
                this.editing = item;
                this.formLokasiId = String(item.lokasi_id ?? '');
                this.formJenis = item.jenis_apar ?? '';
                this.formKapasitas = item.kapasitas_kg ?? '';
                this.formTanggalExpired = item.tanggal_expired ?? '';
                this.formKeterangan = item.keterangan ?? '';
                this.showForm = true;
            },

            closeForm() {
                this.showForm = false;
                this.editing = null;
            },

            confirmDelete(id) {
                this.deleteId = id;
            },

            cancelDelete() {
                this.deleteId = null;
            },

            formAction() {
                return this.editing ? `${this.baseUrl}/${this.editing.id}` : this.storeUrl;
            },

            deleteAction() {
                return this.deleteId ? `${this.baseUrl}/${this.deleteId}` : '#';
            },
        }));
    });
}
