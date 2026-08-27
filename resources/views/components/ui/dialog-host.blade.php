<div x-data>
    <div class="fixed inset-0 z-[60]" x-show="$store.uiDialog.alert.open" x-cloak
        x-on:keydown.escape.window="$store.uiDialog.closeAlert()">
        <div class="fixed inset-0 bg-black/40" x-on:click="$store.uiDialog.closeAlert()"></div>
        <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xl" x-on:click.stop>
                <h3 class="text-lg font-semibold text-gray-900" x-text="$store.uiDialog.alert.title"></h3>
                <p class="mt-2 text-sm text-gray-600 whitespace-pre-line" x-text="$store.uiDialog.alert.message"></p>
                <div class="mt-4 flex justify-end">
                    <x-ui.button type="button" x-on:click="$store.uiDialog.closeAlert()">Oke</x-ui.button>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 z-[60]" x-show="$store.uiDialog.confirm.open" x-cloak
        x-on:keydown.escape.window="$store.uiDialog.cancelConfirm()">
        <div class="fixed inset-0 bg-black/40" x-on:click="$store.uiDialog.cancelConfirm()"></div>
        <div class="fixed left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-xl" x-on:click.stop>
                <h3 class="text-lg font-semibold text-gray-900" x-text="$store.uiDialog.confirm.title"></h3>
                <p class="mt-2 text-sm text-gray-600 whitespace-pre-line" x-text="$store.uiDialog.confirm.message"></p>
                <div class="mt-4 flex justify-end gap-2">
                    <x-ui.button type="button" variant="outline" x-on:click="$store.uiDialog.cancelConfirm()">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="button"
                        x-bind:class="$store.uiDialog.confirm.destructive
                            ? 'bg-red-600 text-white hover:bg-red-700'
                            : 'bg-blue-600 text-white hover:bg-blue-700'"
                        x-on:click="$store.uiDialog.acceptConfirm()"
                        x-text="$store.uiDialog.confirm.confirmLabel">
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
