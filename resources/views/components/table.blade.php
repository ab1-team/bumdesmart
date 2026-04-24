@props(['headers', 'results', 'sortColumn' => null, 'sortDirection' => 'desc'])

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th wire:click="setSortBy('{{ $header['key'] }}')" style="cursor: pointer;">
                        @if ($header['sortable'] ?? true)
                            <div class="d-flex align-items-center">
                                <span>
                                    {!! $header['label'] !!}
                                </span>
                                @if ($sortColumn === $header['key'])
                                    <span class="material-symbols-outlined">
                                        {{ $sortDirection === 'asc' ? 'arrow_drop_up' : 'arrow_drop_down' }}
                                    </span>
                                @endif
                            </div>
                        @else
                            {!! $header['label'] !!}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
<div class="mt-3">
    {{ $results->links() }}
</div>
