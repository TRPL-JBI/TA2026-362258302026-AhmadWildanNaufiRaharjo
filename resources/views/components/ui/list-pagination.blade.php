<div class="flex flex-col gap-2 border-t border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
    x-show="paginationMeta().total > 0">
    <p class="text-xs text-gray-500">
        Menampilkan <span x-text="paginationMeta().from"></span>–<span x-text="paginationMeta().to"></span>
        dari <span x-text="paginationMeta().total"></span> data
    </p>
    <div class="flex items-center gap-1">
        <button type="button"
            class="inline-flex h-9 items-center justify-center rounded-md border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-900 transition-colors hover:bg-gray-50 disabled:pointer-events-none disabled:opacity-50"
            x-bind:disabled="!paginationMeta().hasPrev"
            x-on:click="prevPage()">
            Previous
        </button>
        <button type="button" disabled
            class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border border-blue-600 bg-blue-600 px-3 text-sm font-semibold text-white"
            x-text="paginationMeta().page"></button>
        <button type="button"
            class="inline-flex h-9 items-center justify-center rounded-md border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-900 transition-colors hover:bg-gray-50 disabled:pointer-events-none disabled:opacity-50"
            x-bind:disabled="!paginationMeta().hasNext"
            x-on:click="nextPage()">
            Next
        </button>
    </div>
</div>
