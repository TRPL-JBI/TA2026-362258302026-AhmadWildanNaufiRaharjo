import { deleteJson, postFormData } from './patroli-api';
import { uiAlert } from './ui-dialog';

export function registerSopDokumen() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('sopDokumenPage', (config = {}) => ({
            items: config.items ?? [],
            canManage: Boolean(config.canManage),
            storeUrl: config.storeUrl ?? '',
            baseUrl: config.baseUrl ?? '',
            showPreview: false,
            previewItem: null,
            showForm: false,
            editing: null,
            formJudul: '',
            formDeskripsi: '',
            formFile: null,
            deleteItem: null,
            isSubmitting: false,

            openPreview(item) {
                this.previewItem = item;
                this.showPreview = true;
            },

            closePreview() {
                this.showPreview = false;
                this.previewItem = null;
            },

            openCreate() {
                this.editing = null;
                this.formJudul = '';
                this.formDeskripsi = '';
                this.formFile = null;
                this.showForm = true;
            },

            openEdit(item) {
                this.editing = item;
                this.formJudul = item.judul ?? '';
                this.formDeskripsi = item.deskripsi ?? '';
                this.formFile = null;
                this.showForm = true;
            },

            closeForm() {
                this.showForm = false;
                this.editing = null;
                this.formFile = null;
            },

            onFileChange(event) {
                const input = event.target;
                this.formFile = input.files?.[0] ?? null;
            },

            confirmDelete(item) {
                this.deleteItem = item;
            },

            cancelDelete() {
                this.deleteItem = null;
            },

            buildFormData() {
                const fd = new FormData();
                fd.append('judul', this.formJudul);
                fd.append('deskripsi', this.formDeskripsi);

                if (this.formFile) {
                    fd.append('file', this.formFile);
                }

                return fd;
            },

            async submitForm() {
                if (this.isSubmitting) {
                    return;
                }

                if (!this.editing && !this.formFile) {
                    await uiAlert('File PDF wajib diunggah.');
                    return;
                }

                this.isSubmitting = true;

                try {
                    const url = this.editing
                        ? `${this.baseUrl}/${this.editing.id}`
                        : this.storeUrl;
                    const fd = this.buildFormData();

                    if (this.editing) {
                        fd.append('_method', 'PUT');
                    }

                    const data = await postFormData(url, fd);
                    const item = data.item;

                    if (this.editing) {
                        const index = this.items.findIndex((row) => Number(row.id) === Number(item.id));
                        if (index >= 0) {
                            this.items.splice(index, 1, item);
                        }
                    } else {
                        this.items.push(item);
                    }

                    this.closeForm();
                    await uiAlert(data.message ?? 'Berhasil disimpan.');
                } catch (error) {
                    await uiAlert(error.message ?? 'Gagal menyimpan dokumen SOP.');
                } finally {
                    this.isSubmitting = false;
                }
            },

            async destroyItem() {
                if (!this.deleteItem || this.isSubmitting) {
                    return;
                }

                this.isSubmitting = true;

                try {
                    const data = await deleteJson(`${this.baseUrl}/${this.deleteItem.id}`);
                    this.items = this.items.filter((row) => Number(row.id) !== Number(this.deleteItem.id));
                    this.cancelDelete();
                    await uiAlert(data.message ?? 'Dokumen dihapus.');
                } catch (error) {
                    await uiAlert(error.message ?? 'Gagal menghapus dokumen SOP.');
                } finally {
                    this.isSubmitting = false;
                }
            },
        }));
    });
}
