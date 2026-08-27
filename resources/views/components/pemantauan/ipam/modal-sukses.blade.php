<x-ui.modal-success title="Laporan Berhasil Disimpan!">
    <x-slot name="description">
        <p><span x-text="bulan"></span> <span x-text="tahun"></span>, <span x-text="units.length"></span> unit IPAM</p>
        <p>Total minggu: <span x-text="totalMinggu()"></span></p>
    </x-slot>
    <x-slot name="actions">
        <x-ui.button class="w-full bg-blue-600 hover:bg-blue-700 text-white" type="button" x-on:click="goBackToList()">
            Kembali ke Daftar
        </x-ui.button>
        <x-ui.button variant="outline" class="w-full" type="button" x-on:click="showSuccess = false">
            Tutup
        </x-ui.button>
    </x-slot>
</x-ui.modal-success>
