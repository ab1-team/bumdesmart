<div>
    <div class="card">
        <div class="card-body p-0">
            <div class="coa-tree p-4">
                <ul class="tree">
                    @foreach ($coa as $l1)
                        <li x-data="{ open: true }">
                            <div class="tree-item level-1" @click="open = !open">
                                <span class="tree-icon material-symbols-outlined"
                                    x-text="open ? 'expand_more' : 'chevron_right'"></span>
                                <span class="badge bg-primary-lt me-2">{{ $l1->kode }}</span>
                                <span class="fw-bold">{{ $l1->nama }}</span>
                            </div>
                            <ul x-show="open" x-transition>
                                @foreach ($l1->akunLevel2 as $l2)
                                    <li x-data="{ open: true }">
                                        <div class="tree-item level-2" @click="open = !open">
                                            <span class="tree-icon material-symbols-outlined"
                                                x-text="open ? 'expand_more' : 'chevron_right'"></span>
                                            <span class="badge bg-azure-lt me-2">{{ $l2->kode }}</span>
                                            <span>{{ $l2->nama }}</span>
                                        </div>
                                        <ul x-show="open" x-transition>
                                            @foreach ($l2->akunLevel3 as $l3)
                                                <li x-data="{ open: true }">
                                                    <div class="tree-item level-3" @click="open = !open">
                                                        <span class="tree-icon material-symbols-outlined"
                                                            x-text="open ? 'expand_more' : 'chevron_right'"></span>
                                                        <span class="badge bg-indigo-lt me-2">{{ $l3->kode }}</span>
                                                        <span>{{ $l3->nama }}</span>
                                                    </div>
                                                    <ul x-show="open" x-transition>
                                                        @foreach ($l3->accounts as $acc)
                                                            <li>
                                                                <div class="tree-item level-4">
                                                                    <span class="tree-dot">•</span>
                                                                    <span
                                                                        class="badge bg-muted-lt me-2">{{ $acc->kode }}</span>
                                                                    <span class="text-muted">{{ $acc->nama }}</span>
                                                                    <span
                                                                        class="ms-auto d-flex gap-1 align-items-center">
                                                                        @if ($acc->no_rek_bank)
                                                                            <button
                                                                                wire:click="toggleDefault({{ $acc->id }}, 'transfer')"
                                                                                class="btn btn-icon btn-sm {{ $acc->is_default_transfer ? 'btn-primary' : 'btn-outline-primary' }}"
                                                                                title="Set Default Transfer">
                                                                                <span class="material-symbols-outlined"
                                                                                    style="font-size: 16px;">account_balance</span>
                                                                            </button>
                                                                            <button
                                                                                wire:click="toggleDefault({{ $acc->id }}, 'qris')"
                                                                                class="btn btn-icon btn-sm {{ $acc->is_default_qris ? 'btn-success' : 'btn-outline-success' }}"
                                                                                title="Set Default QRIS">
                                                                                <span class="material-symbols-outlined"
                                                                                    style="font-size: 16px;">qr_code_2</span>
                                                                            </button>
                                                                        @endif
                                                                        <span
                                                                            class="badge {{ $acc->jenis_mutasi == 'debit' ? 'bg-green-lt' : 'bg-red-lt' }} small"
                                                                            style="font-size: 10px;">
                                                                            {{ strtoupper($acc->jenis_mutasi) }}
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <style>
        .coa-tree ul {
            list-style: none;
            padding-left: 1.5rem;
            margin: 0;
            position: relative;
        }

        .coa-tree ul ul::before {
            content: "";
            position: absolute;
            left: 0.75rem;
            top: 0;
            bottom: 0;
            border-left: 1px dashed #cbd5e1;
        }

        .tree-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 2px;
        }

        .tree-item:hover {
            background: #f1f5f9;
        }

        .tree-icon {
            font-size: 18px;
            margin-right: 0.5rem;
            color: #64748b;
            user-select: none;
        }

        .tree-dot {
            margin-right: 0.75rem;
            margin-left: 0.25rem;
            color: #94a3b8;
        }

        .level-1 {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-top: 10px;
        }

        .level-2 {
            font-weight: 500;
        }

        .level-3 {
            color: #334155;
        }

        .level-4 {
            cursor: default;
            padding-left: 1.25rem;
        }

        .level-4:hover {
            background: #f8fafc;
        }

        .badge {
            font-family: 'Courier New', Courier, monospace;
            min-width: 60px;
            text-align: center;
        }
    </style>
</div>
