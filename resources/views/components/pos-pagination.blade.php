<div class="p-2 border-top bg-light-lt">
    @if ($paginator->hasPages())
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-secondary small fw-medium">
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            </div>
            
            <div class="d-flex gap-1">
                @if ($paginator->onFirstPage())
                    <button class="btn btn-sm btn-icon btn-white disabled" disabled title="Previous">
                        <span class="material-symbols-outlined" style="font-size: 18px;">chevron_left</span>
                    </button>
                @else
                    <button class="btn btn-sm btn-icon btn-white shadow-sm" wire:click="previousPage" title="Previous">
                        <span class="material-symbols-outlined" style="font-size: 18px;">chevron_left</span>
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button class="btn btn-sm btn-icon btn-white shadow-sm" wire:click="nextPage" title="Next">
                        <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
                    </button>
                @else
                    <button class="btn btn-sm btn-icon btn-white disabled" disabled title="Next">
                        <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
