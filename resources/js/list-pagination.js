export const DEFAULT_LIST_PAGE_SIZE = 10;

export function listPaginationMixin(pageSize = DEFAULT_LIST_PAGE_SIZE) {
    return {
        currentPage: 1,
        pageSize,

        resetPage() {
            this.currentPage = 1;
        },

        watchPaginationFilters(keys = ['q']) {
            keys.forEach((key) => {
                this.$watch(key, () => {
                    this.resetPage();
                });
            });
        },

        paginateItems(items) {
            const list = Array.isArray(items) ? items : [];
            const total = list.length;
            const totalPages = total === 0 ? 1 : Math.ceil(total / this.pageSize);
            let page = Number(this.currentPage) || 1;

            if (page > totalPages) {
                page = totalPages;
                this.currentPage = page;
            }

            if (page < 1) {
                page = 1;
                this.currentPage = 1;
            }

            const start = total === 0 ? 0 : (page - 1) * this.pageSize;

            return {
                items: list.slice(start, start + this.pageSize),
                total,
                totalPages,
                page,
                from: total === 0 ? 0 : start + 1,
                to: total === 0 ? 0 : Math.min(start + this.pageSize, total),
                hasPrev: page > 1,
                hasNext: page < totalPages,
            };
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage -= 1;
            }
        },

        nextPage() {
            const total = this.paginationMeta?.().total ?? 0;
            const totalPages = total === 0 ? 1 : Math.ceil(total / this.pageSize);

            if (this.currentPage < totalPages) {
                this.currentPage += 1;
            }
        },
    };
}

export function registerLaporanListPage() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('laporanListPage', (reports = []) => ({
            ...listPaginationMixin(),
            q: '',
            jenis: 'semua',
            reports: Array.isArray(reports) ? reports : [],
            previewOpen: false,
            previewLoading: false,
            previewError: '',
            previewTitle: '',
            previewFormat: '',
            previewDownloadUrl: '',

            init() {
                this.watchPaginationFilters(['q', 'jenis']);
            },

            async openPreview(row) {
                this.previewOpen = true;
                this.previewLoading = true;
                this.previewError = '';
                this.previewTitle = row?.nama || 'Laporan';
                this.previewFormat = row?.format || '';
                this.previewDownloadUrl = row?.download_url || '';

                await this.$nextTick();

                const bodyEl = this.$refs.previewBody;
                const styleEl = this.$refs.previewStyle;
                const xlsxBodyEl = this.$refs.previewXlsxBody;
                const docxWrapEl = this.$refs.previewDocxWrap;
                const xlsxWrapEl = this.$refs.previewXlsxWrap;

                if (bodyEl) {
                    bodyEl.replaceChildren();
                }
                if (styleEl) {
                    styleEl.replaceChildren();
                }
                if (xlsxBodyEl) {
                    xlsxBodyEl.replaceChildren();
                }

                try {
                    const response = await fetch(row.preview_url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Gagal memuat preview laporan.');
                    }

                    const blob = await response.blob();
                    const { renderLaporanPreview } = await import('./laporan-preview');

                    await renderLaporanPreview(blob, row?.format, {
                        bodyEl,
                        styleEl,
                        xlsxBodyEl,
                        docxWrapEl,
                        xlsxWrapEl,
                    });
                } catch (error) {
                    this.previewError = error instanceof Error
                        ? error.message
                        : 'Gagal memuat preview laporan.';
                } finally {
                    this.previewLoading = false;
                }
            },

            closePreview() {
                this.previewOpen = false;
                this.previewLoading = false;
                this.previewError = '';
                this.previewFormat = '';

                const bodyEl = this.$refs.previewBody;
                const styleEl = this.$refs.previewStyle;
                const xlsxBodyEl = this.$refs.previewXlsxBody;

                if (bodyEl) {
                    bodyEl.replaceChildren();
                }
                if (styleEl) {
                    styleEl.replaceChildren();
                }
                if (xlsxBodyEl) {
                    xlsxBodyEl.replaceChildren();
                }
            },

            filtered() {
                const query = (this.q || '').toLowerCase().trim();
                const jenis = this.jenis || 'semua';

                return this.reports.filter((row) => {
                    const matchQuery = !query || String(row.nama || '').toLowerCase().includes(query);
                    const rowJenis = String(row.jenis || '').toLowerCase();
                    const matchJenis = jenis === 'semua'
                        || rowJenis === jenis
                        || (jenis === 'patroli' && rowJenis === 'patroli')
                        || (jenis === 'insiden' && rowJenis === 'insiden');

                    return matchQuery && matchJenis;
                });
            },

            paginationMeta() {
                return this.paginateItems(this.filtered());
            },

            paginated() {
                return this.paginationMeta().items;
            },
        }));
    });
}
