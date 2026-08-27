export function registerUiDialog() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.store('uiDialog', {
            alert: {
                open: false,
                title: 'Informasi',
                message: '',
            },
            confirm: {
                open: false,
                title: 'Konfirmasi',
                message: '',
                confirmLabel: 'Ya',
                destructive: false,
            },
            _resolveAlert: null,
            _resolveConfirm: null,

            openAlert(message, options = {}) {
                this.alert.title = options.title ?? 'Informasi';
                this.alert.message = String(message ?? '');
                this.alert.open = true;

                return new Promise((resolve) => {
                    this._resolveAlert = resolve;
                });
            },

            closeAlert() {
                this.alert.open = false;

                if (this._resolveAlert) {
                    this._resolveAlert();
                    this._resolveAlert = null;
                }
            },

            openConfirm(message, options = {}) {
                if (this.confirm.open) {
                    this.cancelConfirm();
                }

                this.confirm.title = options.title ?? 'Konfirmasi';
                this.confirm.message = String(message ?? '');
                this.confirm.confirmLabel = options.confirmLabel ?? 'Ya';
                this.confirm.destructive = Boolean(options.destructive);
                this.confirm.open = true;

                return new Promise((resolve) => {
                    this._resolveConfirm = resolve;
                });
            },

            acceptConfirm() {
                this.confirm.open = false;

                if (this._resolveConfirm) {
                    this._resolveConfirm(true);
                    this._resolveConfirm = null;
                }
            },

            cancelConfirm() {
                this.confirm.open = false;

                if (this._resolveConfirm) {
                    this._resolveConfirm(false);
                    this._resolveConfirm = null;
                }
            },
        });
    });
}

export function uiAlert(message, options = {}) {
    return window.Alpine.store('uiDialog').openAlert(message, options);
}

export function uiConfirm(message, options = {}) {
    return window.Alpine.store('uiDialog').openConfirm(message, options);
}
