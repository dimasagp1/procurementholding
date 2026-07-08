<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:0.5rem;">
            <h2 class="font-semibold text-xl leading-tight mb-0">
                {{ $title ?? __('Rejected Deliveries / Retur Vendor') }}
            </h2>
        </div>
    </x-slot>

    <style>
        @media (max-width: 767.98px) {
            .filter-form .col-md-9, .filter-form .col-md-3 {
                flex: 0 0 100%; max-width: 100%;
            }
        }
        
        .badge-purple {
            background-color: #6f42c1;
            color: #fff;
        }
    </style>

    <div>
        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <ul class="mb-0 pl-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card shadow-sm rounded-lg">
            <div class="card-body p-3">
                <!-- Search Filter Form -->
                <div class="card mb-4 shadow-sm" style="background-color: rgba(255,255,255,0.02)">
                    <div class="card-body py-2">
                        <form action="{{ request()->url() }}" method="GET" class="row filter-form align-items-end mb-0">
                            <div class="col-md-9 col-12 mb-2">
                                <label for="search" class="form-label font-weight-bold small text-uppercase opacity-75">Cari</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-transparent border-right-0 text-muted">
                                            <i class="fas fa-search"></i>
                                        </span>
                                    </div>
                                    <input type="text" name="search" id="search" class="form-control border-left-0" placeholder="Cari No PR, Nama Item, No PO..." value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-2">
                                <div class="d-flex" style="gap:6px;">
                                    <button type="submit" class="btn btn-primary btn-sm flex-fill">Cari</button>
                                    <a href="{{ request()->url() }}" class="btn btn-secondary btn-sm" title="Reset">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-borderless text-sm table-stack">
                        <thead>
                            <tr>
                                <th>PR & PO Number</th>
                                <th>Item Details</th>
                                <th>Requester & Dept</th>
                                <th>Tgl Ditolak</th>
                                <th>Kuantitas Retur</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deliveries as $del)
                                @php
                                    $item = $del->prItem;
                                    $pr = $item->purchaseRequest;
                                    $unresolved = $del->unresolved_rejected_quantity;
                                    $totalReceived = $del->returReceipts->sum('received_quantity');
                                    
                                    // Permissions
                                    $user = Auth::user();
                                    $isProc = $user->hasRole('procurement');
                                    $isProcHolding = $user->hasRole('procurement_holding');
                                    $isSuperadmin = $user->hasRole('superadmin');
                                    $canManage = $isProc || $isProcHolding || $isSuperadmin;
                                @endphp
                                <tr>
                                    <td data-label="PR & PO No.">
                                        <strong>PR: <a href="{{ route('purchase-requests.show', $pr) }}" class="text-info">{{ $pr->pr_number }}</a></strong>
                                        @if($item->po_number)
                                            <div class="small text-muted mt-1"><i class="fas fa-file-invoice mr-1"></i>PO: {{ $item->po_number }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Item Details">
                                        <span class="font-weight-bold text-white">{{ $item->item_name }}</span>
                                        @if($item->description)
                                            <div class="small text-muted text-truncate" style="max-width: 250px;">{{ $item->description }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Requester & Dept">
                                        <div>{{ $pr->user->name }}</div>
                                        <small class="badge badge-secondary mt-1">{{ $pr->department->code }}</small>
                                    </td>
                                    <td data-label="Tgl Ditolak">
                                        <div>{{ $del->delivery_date->format('d M Y') }}</div>
                                        <small class="text-muted"><i class="far fa-user mr-1"></i>{{ $del->receiver->name ?? 'System' }}</small>
                                    </td>
                                    <td data-label="Kuantitas Retur">
                                        <div>Ditolak: <strong class="text-danger">{{ $del->rejected_quantity }} {{ $item->uom }}</strong></div>
                                        <div class="small text-success mt-1">Diterima Pengganti: {{ $totalReceived }} {{ $item->uom }}</div>
                                    </td>
                                    <td data-label="Status">
                                        @if($unresolved <= 0)
                                            <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Terpenuhi</span>
                                        @else
                                            <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i> Sisa Retur: {{ $unresolved }} {{ $item->uom }}</span>
                                        @endif
                                    </td>
                                    <td class="td-actions text-right">
                                        <button type="button" class="btn btn-info btn-xs" data-toggle="collapse" data-target="#retur-details-{{ $del->id }}" aria-expanded="false" title="Detail Penerimaan Retur">
                                            <i class="fas fa-eye"></i> Riwayat ({{ $del->returReceipts->count() }})
                                        </button>
                                        
                                        @if($unresolved > 0 && $canManage)
                                            <button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#receiveReturModal-{{ $del->id }}" title="Terima Barang Pengganti">
                                                <i class="fas fa-truck-loading"></i> Terima Retur
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                
                                <!-- Collapse Panel for Return Receipts History -->
                                <tr class="tr-expand">
                                    <td colspan="7" class="p-0" style="border: none;">
                                        <div id="retur-details-{{ $del->id }}" class="collapse">
                                            <div class="detail-panel-inner rounded shadow border-0" style="background-color: rgba(0, 0, 0, 0.18); margin: 0.5rem 0; padding: 15px;">
                                                <div class="row mb-3 pb-3 border-bottom border-secondary">
                                                    <div class="col-md-12">
                                                        <h6 class="text-white font-weight-bold"><i class="fas fa-exclamation-triangle text-danger mr-1"></i> Detail Penolakan Awal</h6>
                                                        <p class="text-danger small mb-0 mt-1">
                                                            <strong>Alasan Ditolak:</strong> {{ $del->rejection_reason ?? 'Tidak ada alasan dicantumkan.' }}
                                                        </p>
                                                        @if($del->notes)
                                                            <p class="text-muted small mb-0 mt-1"><strong>Catatan Kedatangan:</strong> {{ $del->notes }}</p>
                                                        @endif
                                                        @if($del->attachment_path)
                                                            <div class="mt-2">
                                                                <a href="{{ Storage::url($del->attachment_path) }}" target="_blank" class="btn btn-outline-info btn-xs"><i class="fas fa-paperclip mr-1"></i> Lihat Bukti Original</a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <h6 class="text-white font-weight-bold mb-2"><i class="fas fa-history text-info mr-1"></i> Log Penerimaan Pengganti (Retur)</h6>
                                                @if($del->returReceipts->isEmpty())
                                                    <p class="text-muted text-center py-2 mb-0 small">Belum ada barang pengganti yang diterima untuk kasus ini.</p>
                                                @else
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-dark table-bordered text-xs mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>Tanggal Terima</th>
                                                                    <th>Kuantitas Diterima</th>
                                                                    <th>Kuantitas Ditolak Lagi</th>
                                                                    <th>Penerima</th>
                                                                    <th>Catatan</th>
                                                                    <th class="text-center">Lampiran</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($del->returReceipts as $receipt)
                                                                    <tr>
                                                                        <td>{{ $receipt->delivery_date->format('d/m/Y') }}</td>
                                                                        <td class="text-success font-weight-bold">+ {{ $receipt->received_quantity }} {{ $item->uom }}</td>
                                                                        <td class="{{ $receipt->rejected_quantity > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                                                            {{ $receipt->rejected_quantity > 0 ? $receipt->rejected_quantity . ' ' . $item->uom : '-' }}
                                                                            @if($receipt->rejection_reason)
                                                                                <br><small class="text-danger">({{ $receipt->rejection_reason }})</small>
                                                                            @endif
                                                                        </td>
                                                                        <td>{{ $receipt->receiver->name ?? 'System' }}</td>
                                                                        <td>{{ $receipt->notes ?? '-' }}</td>
                                                                        <td class="text-center">
                                                                            @if($receipt->attachment_path)
                                                                                <a href="{{ Storage::url($receipt->attachment_path) }}" target="_blank" class="text-info"><i class="fas fa-paperclip"></i></a>
                                                                            @else
                                                                                -
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal input receipt of returned goods -->
                                @if($unresolved > 0 && $canManage)
                                    <div class="modal fade" id="receiveReturModal-{{ $del->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <form action="{{ route('purchase-requests.deliveries.store-retur-receipt', $del) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                                                    <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                        <h5 class="modal-title"><i class="fas fa-truck-loading mr-2 text-primary"></i>Penerimaan Retur: {{ $item->item_name }}</h5>
                                                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-info py-2" style="background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.5); color: #93c5fd;">
                                                            Total Ditolak Awal: <strong>{{ $del->rejected_quantity }} {{ $item->uom }}</strong> <br>
                                                            Sudah Diganti: <strong>{{ $totalReceived }} {{ $item->uom }}</strong> <br>
                                                            Sisa yang Harus Diganti: <strong>{{ $unresolved }} {{ $item->uom }}</strong>
                                                        </div>
                                                        
                                                        <div class="form-row">
                                                            <div class="col-md-6 form-group">
                                                                <label class="text-gray-300">Jumlah Diterima (Bagus) *</label>
                                                                <input type="number" step="0.01" name="received_quantity" class="form-control" required min="0" max="{{ $unresolved }}" value="{{ $unresolved }}" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                            </div>
                                                            <div class="col-md-6 form-group">
                                                                <label class="text-gray-300">Jumlah Ditolak Lagi (Jika Ada)</label>
                                                                <input type="number" step="0.01" name="rejected_quantity" value="0" min="0" class="form-control" required style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label class="text-gray-300">Tanggal Kedatangan *</label>
                                                            <input type="date" name="delivery_date" class="form-control" required value="{{ date('Y-m-d') }}" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label class="text-gray-300">Alasan Penolakan Baru (Wajib jika ada barang ditolak lagi)</label>
                                                            <textarea name="rejection_reason" class="form-control mb-2" placeholder="Tulis alasan jika barang pengganti ditolak lagi..." style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;"></textarea>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label class="text-gray-300">Catatan Penerimaan (Opsional)</label>
                                                            <textarea name="notes" class="form-control mb-2" placeholder="Contoh: Pengganti retur tanggal {{ $del->delivery_date->format('d M Y') }}" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;"></textarea>
                                                            <input type="file" name="delivery_attachment" class="form-control-file text-sm" accept="image/*,.pdf" style="color: #d1d5db;">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Simpan Penerimaan</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Tidak ditemukan riwayat kedatangan barang yang ditolak.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $deliveries->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
