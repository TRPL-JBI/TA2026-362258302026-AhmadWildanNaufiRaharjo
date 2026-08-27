export function registerInventarisLokasi() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('inventarisLokasi', (initial = {}) => ({
            showForm: Boolean(initial.showForm),
            editing: null,
            formNama: initial.formNama ?? '',
            formJenis: initial.formJenis ?? '',
            formDeskripsi: initial.formDeskripsi ?? '',
            deleteId: null,
            items: initial.items ?? [],
            storeUrl: initial.storeUrl ?? '',
            baseUrl: initial.baseUrl ?? '',
            printBatchUrl: initial.printBatchUrl ?? '',
            selectedIds: [],

            get pageIds() {
                return this.items.map((row) => Number(row.id));
            },

            get selectedCount() {
                return this.selectedIds.length;
            },

            get allPageSelected() {
                return this.pageIds.length > 0 && this.pageIds.every((id) => this.selectedIds.includes(id));
            },

            isSelected(id) {
                return this.selectedIds.includes(Number(id));
            },

            toggleId(id) {
                const value = Number(id);
                if (this.selectedIds.includes(value)) {
                    this.selectedIds = this.selectedIds.filter((item) => item !== value);
                    return;
                }
                this.selectedIds = [...this.selectedIds, value];
            },

            toggleAllPage() {
                if (this.allPageSelected) {
                    this.selectedIds = this.selectedIds.filter((id) => !this.pageIds.includes(id));
                    return;
                }

                const merged = new Set([...this.selectedIds, ...this.pageIds]);
                this.selectedIds = [...merged];
            },

            clearSelection() {
                this.selectedIds = [];
            },

            printSelected() {
                if (!this.selectedIds.length) {
                    return;
                }

                const url = `${this.printBatchUrl}?ids=${this.selectedIds.join(',')}`;
                window.open(url, '_blank', 'noopener');
            },

            openCreate() {
                this.editing = null;
                this.formNama = '';
                this.formJenis = '';
                this.formDeskripsi = '';
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
                this.formNama = item.nama_lokasi ?? '';
                this.formJenis = item.jenis_lokasi ?? '';
                this.formDeskripsi = item.deskripsi ?? '';
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
