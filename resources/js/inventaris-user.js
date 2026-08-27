export function registerInventarisUser() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('inventarisUser', (initial = {}) => ({
            showForm: Boolean(initial.showForm),
            editing: null,
            deleteId: null,
            formUsername: initial.formUsername ?? '',
            formPassword: initial.formPassword ?? '',
            formNamaLengkap: initial.formNamaLengkap ?? '',
            formRole: initial.formRole ?? 'Petugas K3LH',
            formLokasiId: initial.formLokasiId ?? '',
            formIsActive: initial.formIsActive ?? true,
            items: initial.items ?? [],
            storeUrl: initial.storeUrl ?? '',
            baseUrl: initial.baseUrl ?? '',
            roles: initial.roles ?? [],
            laboratorium: initial.laboratorium ?? [],

            openCreate() {
                this.editing = null;
                this.formUsername = '';
                this.formPassword = '';
                this.formNamaLengkap = '';
                this.formRole = this.roles[0] ?? 'Petugas K3LH';
                this.formLokasiId = this.laboratorium[0]?.id ?? '';
                this.formIsActive = true;
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
                this.formUsername = item.username ?? '';
                this.formPassword = '';
                this.formNamaLengkap = item.nama_lengkap ?? '';
                this.formRole = item.role ?? 'Petugas K3LH';
                this.formLokasiId = item.lokasi_id ?? this.laboratorium[0]?.id ?? '';
                this.formIsActive = Boolean(item.is_active);
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

            passwordRequired() {
                return !this.editing;
            },

            showLokasiField() {
                return this.formRole === 'Kalab';
            },
        }));
    });
}
