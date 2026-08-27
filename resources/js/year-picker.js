export function registerYearPicker() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('yearPicker', (config = {}) => ({
            value: '',
            open: false,
            min: Number(config.min ?? 2000),
            max: Number(config.max ?? 2100),
            viewStart: Number(config.min ?? 2000),

            init() {
                this.$watch('value', () => {
                    if (! this.open) {
                        this.syncViewToValue();
                    }
                });
            },

            syncViewToValue() {
                const parsed = Number(this.value);
                const year = Number.isFinite(parsed)
                    ? this.clampYear(parsed)
                    : new Date().getFullYear();

                this.viewStart = Math.floor((year - this.min) / 12) * 12 + this.min;

                if (this.viewStart + 11 > this.max) {
                    this.viewStart = Math.max(this.min, this.max - 11);
                }
            },

            clampYear(year) {
                return Math.min(this.max, Math.max(this.min, year));
            },

            get years() {
                const list = [];

                for (let year = this.viewStart; year < this.viewStart + 12 && year <= this.max; year += 1) {
                    if (year >= this.min) {
                        list.push(year);
                    }
                }

                return list;
            },

            get viewLabel() {
                const end = Math.min(this.viewStart + 11, this.max);

                return `${this.viewStart} – ${end}`;
            },

            get currentYear() {
                return new Date().getFullYear();
            },

            prevPage() {
                this.viewStart = Math.max(this.min, this.viewStart - 12);
            },

            nextPage() {
                if (this.viewStart + 12 <= this.max) {
                    this.viewStart += 12;
                }
            },

            canPrev() {
                return this.viewStart > this.min;
            },

            canNext() {
                return this.viewStart + 12 <= this.max;
            },

            selectYear(year) {
                this.value = String(year);
                this.open = false;
            },

            toggle() {
                if (this.open) {
                    this.open = false;

                    return;
                }

                this.syncViewToValue();
                this.open = true;
            },

            yearButtonClass(year) {
                const selected = String(year) === String(this.value);
                const isCurrent = year === this.currentYear;

                if (selected) {
                    return 'bg-blue-600 text-white hover:bg-blue-700';
                }

                if (isCurrent) {
                    return 'bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100';
                }

                return 'text-gray-700 hover:bg-gray-100';
            },
        }));
    });
}
