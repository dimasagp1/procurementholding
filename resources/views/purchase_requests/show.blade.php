<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight mb-0">
             PR #{{ $purchaseRequest->pr_number }}
        </h2>
    </x-slot>

    <style>
        /* ---- Show PR Mobile Overrides ---- */
        @media (max-width: 767.98px) {
            /* Header info: stack both columns */
            .pr-header-left, .pr-header-right {
                flex: 0 0 100% !important;
                max-width: 100% !important;
                text-align: left !important;
            }
            .pr-header-right { margin-top: 0.75rem; }
            .pr-header-right .btn { margin-top: 0.4rem; width: 100%; }

            /* Action buttons in items table — full width stack */
            .item-actions { display: flex; flex-direction: column; gap: 0.3rem; min-width: unset; }
            .item-actions .btn { width: 100% !important; }
            .item-actions form, .item-actions .d-inline { display: block !important; width: 100%; }

            /* Approval history text — allow wrapping */
            .approval-history div { white-space: normal !important; word-break: break-word; }

            /* Delivery plan list — allow wrapping */
            .delivery-plan-list div { white-space: normal !important; }

            /* Chat bubbles full width on mobile */
            .chat-bubble { max-width: 100% !important; }

            /* Card padding on show page */
            .show-card .p-6 { padding: 1rem !important; }

            /* Section header row wraps */
            .section-title-row { flex-direction: column !important; align-items: flex-start !important; gap: 0.5rem; }

            /* Approval panel stack */
            .approval-panel .row > div { margin-bottom: 0.75rem; }
        }
    </style>

    <div>

            <!-- Header Info -->
            <div class="card shadow-sm rounded-lg mb-3 show-card">
                <div class="p-6 text-gray-900">
                    <div class="row">
                        <div class="col-md-6 pr-header-left">
                            <p><strong>Requester:</strong> {{ $purchaseRequest->user->name }} ({{ $purchaseRequest->department->name }})</p>
                            <p><strong>Type:</strong> <span class="badge badge-secondary">{{ $purchaseRequest->pr_type == 'non_operational' ? 'Non - Operational' : 'Operational' }}</span></p>
                            <p><strong>Date:</strong> {{ $purchaseRequest->request_date->format('d M Y') }}</p>
                            <p><strong>Purpose:</strong> {{ $purchaseRequest->purpose }}</p>
                        </div>
                        <div class="col-md-6 pr-header-right" style="text-align:right;">
                            <p><strong>Status:</strong> <span class="badge badge-info">{{ ucfirst($purchaseRequest->status) }}</span></p>
                            
                            @if($purchaseRequest->isEditable() && (auth()->id() == $purchaseRequest->user_id || auth()->user()->hasRole('superadmin')))
                                <a href="{{ route('purchase-requests.edit', $purchaseRequest) }}" class="btn btn-warning btn-sm mt-2 mr-2">
                                    <i class="fas fa-edit"></i> Edit Request
                                </a>
                            @endif

                            @if($purchaseRequest->status === 'draft' && (auth()->id() == $purchaseRequest->user_id || auth()->user()->hasRole('superadmin')))
                                <form action="{{ route('purchase-requests.submit-draft', $purchaseRequest) }}" method="POST" class="d-inline mt-2 mr-2 form-confirm" data-message="Apakah Anda yakin ingin mengajukan Purchase Request ini?">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-paper-plane"></i> Ajukan PR
                                    </button>
                                </form>
                            @endif

                            <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="openPreviewModal('{{ route('purchase-requests.preview', $purchaseRequest) }}', '{{ route('purchase-requests.export', $purchaseRequest) }}')">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>

                            <form action="{{ route('purchase-requests.resend-notification', $purchaseRequest) }}" method="POST" class="d-inline mt-2 ml-2 form-confirm" data-message="Kirim ulang email notifikasi untuk PR ini?">
                                @csrf
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fas fa-paper-plane"></i> Kirim Ulang Email
                                </button>
                            </form>

                            @if($purchaseRequest->isDeletable() && (auth()->id() == $purchaseRequest->user_id || auth()->user()->hasRole('superadmin')))
                                <form action="{{ route('purchase-requests.destroy', $purchaseRequest) }}" method="POST" class="d-inline mt-2 ml-2 form-confirm" data-message="Apakah Anda yakin ingin menghapus Purchase Request ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Hapus Request
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @php
                $showBudgetDetails = $purchaseRequest->status !== 'draft' && (
                    Auth::user()->hasRole(['procurement', 'superadmin', 'operational_manager', 'manager_fat', 'general_manager'])
                );
            @endphp

            @if($showBudgetDetails)
            <!-- Budget Details -->
            <div class="card shadow-sm rounded-lg mb-3 show-card" id="budget-details-card" style="display: none;">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-3"><i class="fas fa-wallet mr-2 text-warning"></i>Perincian Anggaran Departemen ({{ $purchaseRequest->department->name }})</h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered text-white table-dark text-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Nama Kategori</th>
                                    <th class="text-right">Pagu Bulan Ini</th>
                                    <th class="text-right">Realisasi (Sebelum PR)</th>
                                    <th class="text-right text-success">Estimasi PR Ini</th>
                                    <th class="text-right text-warning">Aktual PR Ini</th>
                                    <th class="text-right">Sisa Pagu Setelah PR</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="budget-details-tbody">
                                <tr>
                                    <td colspan="7" class="text-center py-3">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> Memuat rincian anggaran...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Items -->
            <div class="card shadow-sm rounded-lg mb-3 show-card">
                <div class="p-6 text-gray-900">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 section-title-row">
                        <h3 class="text-lg font-medium mb-0">Items List</h3>
                        @if($purchaseRequest->hasRejectedItems() && (Auth::id() == $purchaseRequest->user_id || Auth::user()->hasRole('superadmin')))
                            <form action="{{ route('purchase-requests.revise-item', $purchaseRequest->items->whereIn('status', ['rejected_om', 'rejected_gm', 'rejected_proc'])->first()) }}" method="POST" class="form-confirm" data-message="Revise all rejected items? They will be moved to a new PR for revision.">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm shadow-sm">
                                    <i class="fas fa-sync-alt mr-1"></i> Revise All Rejected Items
                                </button>
                            </form>
                        @endif
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-stack">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Keterangan</th>
                                    <th>Qty/UOM</th>
                                    @if(Auth::user()->hasRole(['procurement', 'superadmin', 'operational_manager', 'manager_fat', 'general_manager']))
                                        <th>Harga</th>
                                    @endif
                                    <th>Due Date</th>
                                    <th>Rencana Kedatangan</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseRequest->items as $item)
                                <tr>
                                    <td data-label="Item">
                                        <strong>{{ $item->item_name }}</strong>
                                        @if($item->purpose)
                                            <br><span class="badge badge-info text-xs mt-1" style="background-color: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #60a5fa; font-weight: normal; font-size: 0.7rem;"><i class="fas fa-bullseye mr-1"></i>{{ $item->purpose }}</span>
                                        @endif
                                        @if($item->attachment)
                                            <br><a href="{{ asset('storage/' . $item->attachment) }}" 
                                                   class="text-blue-600 preview-attachment" 
                                                   data-url="{{ asset('storage/' . $item->attachment) }}"
                                                   data-filename="{{ basename($item->attachment) }}">
                                                <i class="fas fa-search-plus"></i> View Attachment
                                            </a>
                                        @endif
                                        @if($item->reject_reason)
                                            <div class="text-danger mt-1">
                                                <strong>Rejected:</strong> {{ $item->reject_reason }}
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Keterangan">{{ $item->description ?? '-' }}</td>
                                    <td data-label="Qty/UOM">
                                        {{ $item->quantity }} {{ $item->uom }}
                                        @php
                                            $receivedQty = $item->received_quantity;
                                            $remainingQty = $item->quantity - $receivedQty;
                                        @endphp
                                        @if($receivedQty > 0)
                                            <br><small class="text-success font-weight-bold">Diterima: {{ $receivedQty }}</small>
                                            @if($remainingQty > 0)
                                                <br><small class="text-warning font-weight-bold">Sisa: {{ $remainingQty }}</small>
                                            @endif
                                            @if($item->deliveries->isNotEmpty())
                                                <div class="mt-2 text-left p-1 rounded" style="background-color: rgba(255,255,255,0.05); font-size: 0.7rem;">
                                                    @foreach($item->deliveries as $del)
                                                        @if($del->notes || $del->attachment_path)
                                                            <div class="mb-1 pb-1 {{ !$loop->last ? 'border-bottom border-secondary' : '' }}">
                                                                <span class="text-info">{{ $del->delivery_date->format('d/m/Y') }} ({{ $del->received_quantity }}):</span><br>
                                                                @if($del->notes)
                                                                    <span class="text-muted"><i class="fas fa-comment-alt"></i> {{ $del->notes }}</span><br>
                                                                @endif
                                                                @if($del->attachment_path)
                                                                    <a href="{{ asset('storage/' . $del->attachment_path) }}" class="text-blue-400 preview-attachment" data-url="{{ asset('storage/' . $del->attachment_path) }}" data-filename="{{ basename($del->attachment_path) }}">
                                                                        <i class="fas fa-paperclip"></i> View File
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    @if(Auth::user()->hasRole(['procurement', 'superadmin', 'operational_manager', 'manager_fat', 'general_manager']))
                                        <td data-label="Harga">
                                            @if($item->status === 'pending_estimate' && (Auth::user()->hasRole('procurement') || Auth::user()->hasRole('superadmin')))
                                                <div class="form-group mb-0">
                                                    <div class="input-group input-group-sm" style="min-width: 140px; max-width: 180px;">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text bg-secondary text-white" style="font-size: 0.75rem; border-color: rgba(255,255,255,0.1);">Rp</span>
                                                        </div>
                                                        <input type="number" step="0.01" name="estimates[{{ $item->id }}][estimated_price]" class="form-control form-control-sm estimate-price-input text-white" placeholder="Harga Estimasi" value="{{ $item->estimated_price ?: '' }}" required data-id="{{ $item->id }}" data-qty="{{ $item->quantity }}" data-purpose="{{ $item->purpose }}" form="estimates-submit-form" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); font-size: 0.75rem;">
                                                    </div>
                                                    <div class="text-xs text-muted mt-1 total-estimate-display" id="total-estimate-{{ $item->id }}" style="font-size: 0.7rem;">
                                                        Total: Rp {{ number_format($item->quantity * ($item->estimated_price ?: 0), 0, ',', '.') }}
                                                    </div>
                                                    <div class="mt-1 smart-budget-alert" id="smart-budget-alert-{{ $item->id }}" style="min-width: 140px;"></div>
                                                </div>
                                            @elseif($item->status === 'pending_estimate')
                                                <span class="text-warning text-xs font-italic"><i class="fas fa-hourglass-half mr-1"></i> Menunggu Estimasi</span>
                                                <div class="mt-1 smart-budget-alert" id="smart-budget-alert-{{ $item->id }}" style="min-width: 140px;"></div>
                                            @else
                                                <div class="text-xs">
                                                    <span class="text-gray-400">Est:</span> <strong>Rp {{ number_format($item->estimated_price, 0, ',', '.') }}</strong>
                                                    <br><span class="text-gray-500">(Total: Rp {{ number_format($item->total_price, 0, ',', '.') }})</span>
                                                    <div class="mt-1 smart-budget-alert" id="smart-budget-alert-{{ $item->id }}" style="min-width: 140px;"></div>
                                                    @if(in_array($item->status, ['ordered', 'delivered', 'completed']))
                                                        <div class="mt-1 border-top border-secondary pt-1">
                                                            <span class="text-success">Aktual:</span> <strong>Rp {{ number_format($item->actual_price, 0, ',', '.') }}</strong>
                                                            <br><span class="text-success small">(Total: Rp {{ number_format($item->actual_total_price, 0, ',', '.') }})</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                    <td data-label="Due Date">{{ $item->due_date ?? '-' }}</td>
                                    <td data-label="Rencana Tiba">
                                        @if($item->deliveryPlans->isNotEmpty())
                                            <div class="mb-2" style="min-width: 130px;">
                                                @foreach($item->deliveryPlans as $index => $plan)
                                                    <div class="text-xs mb-1 pb-1 border-bottom border-secondary" style="{{ !$plan->is_active ? 'opacity: 0.6;' : '' }}">
                                                        @if(!$plan->is_active)
                                                            <span class="text-danger" style="text-decoration: line-through;"><i class="far fa-calendar-times"></i> {{ $plan->planned_date->format('d/m/Y') }} (Batal)</span><br>
                                                            <strong style="text-decoration: line-through;">QTY: {{ (float)$plan->planned_quantity }} {{ $item->uom }}</strong>
                                                        @else
                                                            <span class="text-info"><i class="far fa-calendar-alt"></i> {{ $plan->planned_date->format('d/m/Y') }}</span>
                                                            @if($plan->is_rescheduled)
                                                                <span class="badge badge-warning ml-1" style="font-size: 0.6rem; padding: 2px 4px;">Reschedule</span>
                                                            @endif
                                                            <br>
                                                            <strong>QTY: {{ (float)$plan->planned_quantity }} {{ $item->uom }}</strong>
                                                        @endif
                                                        @if($plan->notes)
                                                            <div class="text-muted mt-1" style="font-size: 0.7rem; font-style: italic;"><i class="fas fa-info-circle"></i> {{ $plan->notes }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                            @php
                                                $isProc = Auth::user()->hasRole('procurement');
                                                $isSuperadmin = Auth::user()->hasRole('superadmin');
                                            @endphp
                                            @if($isProc || $isSuperadmin)
                                                <button type="button" class="btn btn-warning btn-xs w-100 mt-1" style="font-size: 0.7rem;" data-toggle="modal" data-target="#rescheduleModal-{{ $item->id }}">
                                                    <i class="fas fa-edit"></i> Reschedule
                                                </button>
                                            @endif
                                        @else
                                            <span class="text-muted text-xs">-</span>
                                        @endif
                                    </td>
                                    <td data-label="Status" class="approval-history">
                                        @php
                                            $isFatPr = $purchaseRequest->pr_type === 'non_operational';
                                            $statusLabels = [
                                                'pending'       => 'Pending',
                                                'approved_om'   => $isFatPr ? 'Approved FAT' : 'Approved OM',
                                                'rejected_om'   => $isFatPr ? 'Rejected FAT' : 'Rejected OM',
                                                'approved_gm'   => 'Approved GM',
                                                'rejected_gm'   => 'Rejected GM',
                                                'approved_proc' => 'Approved Proc',
                                                'rejected_proc' => 'Rejected Proc',
                                                'ordered'       => 'Ordered',
                                                'delivered'     => 'Delivered',
                                                'completed'     => 'Completed',
                                            ];
                                            $statusLabel = $statusLabels[$item->status] ?? ucfirst(str_replace('_', ' ', $item->status));
                                            $badgeColor = $item->status === 'pending' ? 'warning'
                                                : (in_array($item->status, ['rejected_om','rejected_gm','rejected_proc']) ? 'danger' : 'success');
                                        @endphp
                                        <span class="badge badge-{{ $badgeColor }}">
                                            {{ $statusLabel }}
                                        </span>
                                        @if($item->revision_count > 0)
                                            <span class="badge badge-info ml-1" title="Item has been revised {{ $item->revision_count }} times">Revised ({{ $item->revision_count }})</span>
                                        @endif
                                        <div class="small mt-2" style="font-size: 0.7rem; line-height: 1.2;">
                                            <div class="text-muted">Created: {{ $item->created_at->format('d/m/y H:i') }}</div>
                                            
                                            @php
                                                $omApproval = $item->approvals->whereIn('approver_role', ['operational_manager', 'manager_fat'])->whereIn('status', ['approved', 'rejected'])->sortByDesc('created_at')->first();
                                                $gmApproval = $item->approvals->where('approver_role', 'general_manager')->whereIn('status', ['approved', 'rejected'])->sortByDesc('created_at')->first();
                                                $procApproval = $item->approvals->where('approver_role', 'procurement')->where('status', 'approved')->first();
                                                $validationNotes = $item->approvals
                                                    ->where('status', 'pending')
                                                    ->filter(function($approval) {
                                                        return !empty($approval->notes);
                                                    })
                                                    ->sortByDesc('created_at');
                                            @endphp

                                             @if($omApproval) 
                                                <div class="{{ $omApproval->status == 'approved' ? 'text-success' : 'text-danger' }}">
                                                    {{ $omApproval->approver_role == 'manager_fat' ? 'FAT' : 'OM' }}: {{ $omApproval->status == 'approved' ? 'Approve' : 'Reject' }} ({{ $omApproval->approved_at->format('d/m/y H:i') }})
                                                    @if($omApproval->purchase_request_id != $purchaseRequest->id) <span class="text-muted small font-italic">(Revised)</span> @endif
                                                </div>                                                 @if($omApproval->notes)
                                                    <div class="text-muted">Catatan {{ $omApproval->approver_role == 'manager_fat' ? 'FAT' : 'OM' }}: {{ $omApproval->notes }}</div>
                                                @endif
                                            @endif
                                            @if($gmApproval) 
                                                <div class="{{ $gmApproval->status == 'approved' ? 'text-success' : 'text-danger' }}">
                                                    GM: {{ $gmApproval->status == 'approved' ? 'Approve' : 'Reject' }} ({{ $gmApproval->approved_at->format('d/m/y H:i') }})
                                                    @if($gmApproval->purchase_request_id != $purchaseRequest->id) <span class="text-muted small font-italic">(Revised)</span> @endif
                                                </div> 
                                                @if($gmApproval->notes)
                                                    <div class="text-muted">Catatan GM: {{ $gmApproval->notes }}</div>
                                                @endif
                                            @endif
                                            @if($procApproval) 
                                                <div class="text-success">
                                                    Proc: Approve ({{ $procApproval->approved_at->format('d/m/y H:i') }})
                                                    @if($procApproval->purchase_request_id != $purchaseRequest->id) <span class="text-muted small font-italic">(Revised)</span> @endif
                                                </div> 
                                                @if($procApproval->notes)
                                                    <div class="text-muted">Catatan Proc: {{ $procApproval->notes }}</div>
                                                @endif
                                            @endif
                                            
                                            @if($item->rejected_at) <div class="text-danger">Rejected: {{ $item->rejected_at->format('d/m/y H:i') }}</div> @endif
                                            @if($item->processed_at) <div class="text-info">Ready to Process: {{ $item->processed_at->format('d/m/y H:i') }}</div> @endif
                                            @if($item->ordered_at) <div class="text-primary">Ordered: {{ $item->ordered_at->format('d/m/y H:i') }}</div> @endif
                                            @if($item->delivered_at) <div class="text-primary">Delivered: {{ $item->delivered_at->format('d/m/y H:i') }}</div> @endif
                                            @if($item->completed_at) <div class="text-success">Completed: {{ $item->completed_at->format('d/m/y H:i') }}</div> @endif

                                            @if($validationNotes->isNotEmpty())
                                                <div class="mt-3 chat-notes-container">
                                                    <div class="text-xs text-muted mb-2 border-bottom pb-1"><i class="fas fa-comments mr-1"></i> Validation Notes</div>
                                                    @foreach($validationNotes as $note)
                                                        <div class="chat-bubble {{ $note->approver_id == Auth::id() ? 'chat-right' : 'chat-left' }}">
                                                            <div class="chat-header">
                                                                <span class="chat-name">{{ $note->approver->name ?? strtoupper(str_replace('_', ' ', $note->approver_role)) }}</span>
                                                                <span class="chat-time">{{ $note->created_at->format('d/m H:i') }}</span>
                                                            </div>
                                                            <div class="chat-body">
                                                                {{ $note->notes }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="td-actions item-actions" style="min-width: 120px;">
                                        <!-- Approval/Rejection Actions -->
                                        @php
                                            $canApprove = false;
                                            $canSendNote = false;
                                             $role = Auth::user()->getRoleNames()->first();
                                             $isOm = Auth::user()->hasRole('operational_manager');
                                             $isFat = Auth::user()->hasRole('manager_fat');
                                             $isGm = Auth::user()->hasRole('general_manager');
                                             $isProc = Auth::user()->hasRole('procurement');
                                             $isSuperadmin = Auth::user()->hasRole('superadmin');
                                            
                                             if ($purchaseRequest->status !== 'draft') {
                                                if ($isOm && $item->status == 'pending' && $purchaseRequest->pr_type === 'operational') { $canApprove = true; $canSendNote = true; }
                                                if ($isFat && $item->status == 'pending' && $purchaseRequest->pr_type === 'non_operational') { $canApprove = true; $canSendNote = true; }
                                                if ($isGm && $item->status == 'approved_om') { $canApprove = true; $canSendNote = true; }
                                                if ($isProc && $item->status == 'approved_gm') { $canApprove = true; $canSendNote = true; }
                                                if ($isSuperadmin && in_array($item->status, ['pending', 'approved_om', 'approved_gm'])) { $canApprove = true; $canSendNote = true; }
                                                if ($purchaseRequest->user_id == Auth::id()) { $canSendNote = true; }
                                             }
                                        @endphp


                                        @if($canApprove)
                                            <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#approveModal-{{ $item->id }}">
                                                Approve
                                            </button>
                                            <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#rejectModal-{{ $item->id }}">
                                                Reject
                                            </button>
                                        @endif
                                        @if($canSendNote)
                                            <button type="button" class="btn btn-primary btn-xs mt-1" data-toggle="modal" data-target="#noteModal-{{ $item->id }}">
                                                Send Note / Reply
                                            </button>
                                        @endif

                                        @if(($isProc || $isSuperadmin) && in_array($item->status, ['approved_proc', 'ordered', 'delivered', 'completed']))
                                            <form action="{{ route('purchase-requests.update-item-status', $item) }}" method="POST" class="mt-1">
                                                @csrf
                                                <select name="status" class="form-control form-control-sm" data-original-value="{{ $item->status }}" onchange="if(this.value === 'ordered'){ this.value = this.dataset.originalValue; $('#orderModal-{{ $item->id }}').modal('show'); } else { this.form.submit(); }">
                                                    <option value="approved_proc" {{ in_array($item->status, ['approved_proc']) ? 'selected' : '' }}>
                                                        Ready to Process {{ $item->processed_at ? '(' . $item->processed_at->format('d/m H:i') . ')' : '' }}
                                                    </option>
                                                    <option value="ordered" {{ $item->status == 'ordered' ? 'selected' : '' }}>
                                                        Ordered {{ $item->ordered_at ? '(' . $item->ordered_at->format('d/m H:i') . ')' : '' }}
                                                    </option>
                                                    <option value="delivered" {{ $item->status == 'delivered' ? 'selected' : '' }}>
                                                        Delivered {{ $item->delivered_at ? '(' . $item->delivered_at->format('d/m H:i') . ')' : '' }}
                                                    </option>
                                                    <option value="completed" {{ $item->status == 'completed' ? 'selected' : '' }}>
                                                        Completed {{ $item->completed_at ? '(' . $item->completed_at->format('d/m H:i') . ')' : '' }}
                                                    </option>
                                                </select>
                                            </form>
                                            
                                            @if(in_array($item->status, ['ordered', 'delivered']) && ($isProc || $isSuperadmin))
                                                @if(!$item->po_number)
                                                    <button type="button" class="btn btn-warning btn-xs mt-2 w-100" data-toggle="modal" data-target="#orderModal-{{ $item->id }}">
                                                        <i class="fas fa-file-invoice"></i> Input PO & Rencana
                                                    </button>
                                                @endif
                                                @if($remainingQty > 0 && $item->deliveryPlans->where('is_active', true)->isNotEmpty())
                                                <button type="button" class="btn btn-primary btn-xs mt-2 w-100" data-toggle="modal" data-target="#deliveryModal-{{ $item->id }}">
                                                    <i class="fas fa-truck-loading"></i> Input Kedatangan Real
                                                </button>
                                                @endif
                                            @endif
                                        @endif

                                        @if($item->deliveries->isNotEmpty())
                                            <button type="button" class="btn btn-info btn-xs mt-1 w-100" data-toggle="modal" data-target="#historyModal-{{ $item->id }}">
                                                <i class="fas fa-history"></i> Riwayat ({{ $item->deliveries->count() }})
                                            </button>
                                        @endif

                                        @if($item->po_number)
                                            <div class="mt-2 text-info text-xs font-weight-bold d-flex align-items-center justify-content-between flex-wrap">
                                                <span>PO: {{ $item->po_number }}</span>
                                                @if(($isProc || $isSuperadmin) && in_array($item->status, ['ordered', 'delivered', 'completed']))
                                                    <button type="button" class="btn btn-outline-warning btn-xs ml-1" data-toggle="modal" data-target="#syncOdooModal-{{ $item->id }}" title="Kirim/Sync ulang ke Odoo">
                                                        <i class="fas fa-sync-alt"></i> Kirim ke Odoo
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                        @if($item->deliveryPlans->isNotEmpty())
                                            <button type="button" class="btn btn-outline-info btn-xs mt-1 w-100" data-toggle="modal" data-target="#planModal-{{ $item->id }}">
                                                <i class="fas fa-calendar-alt"></i> Detail Rencana ({{ $item->deliveryPlans->where('is_active', true)->count() }})
                                            </button>

                                            <!-- View Plan Modal -->
                                            <div class="modal fade" id="planModal-{{ $item->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                                                        <div class="modal-header border-bottom-0">
                                                            <h5 class="modal-title">Rencana Kedatangan: {{ $item->item_name }}</h5>
                                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <div class="modal-body text-left">
                                                            <p class="mb-2"><strong>NO. PO:</strong> {{ $item->po_number }}</p>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-dark table-bordered text-sm">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Tanggal</th>
                                                                            <th>QTY</th>
                                                                            <th>Catatan</th>
                                                                            <th>File</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($item->deliveryPlans as $plan)
                                                                        <tr style="{{ !$plan->is_active ? 'opacity: 0.5; text-decoration: line-through;' : '' }}">
                                                                            <td>
                                                                                {{ $plan->planned_date->format('d/m/Y') }}
                                                                                @if($plan->is_rescheduled && $plan->is_active)
                                                                                    <br><span class="badge badge-warning" style="font-size:0.6rem;">Reschedule</span>
                                                                                @endif
                                                                            </td>
                                                                            <td>{{ $plan->planned_quantity }}</td>
                                                                            <td>{{ $plan->notes ?? '-' }}</td>
                                                                            <td class="text-center">
                                                                                @if($plan->attachment_path)
                                                                                    <a href="{{ Storage::url($plan->attachment_path) }}" target="_blank" class="text-info"><i class="fas fa-paperclip"></i></a>
                                                                                @else
                                                                                    -
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                         <!-- Revision Action moved to bulk button above table -->
                                    </td>
                                </tr>

                                @endforeach
                        </table>
                    </div>

                    @if($purchaseRequest->items->where('status', 'pending_estimate')->isNotEmpty() && (Auth::user()->hasRole('procurement') || Auth::user()->hasRole('superadmin')))
                        <div class="mt-4 p-3 rounded d-flex justify-content-between align-items-center flex-wrap" style="background-color: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); gap: 10px;">
                            <div class="text-sm text-gray-300">
                                <i class="fas fa-info-circle text-warning mr-1"></i>
                                Masukkan estimasi harga satuan untuk setiap item di atas, lalu klik tombol di sebelah kanan untuk mengirim ke Manager.
                            </div>
                            <div>
                                <form id="estimates-submit-form" action="{{ route('purchase-requests.save-estimates', $purchaseRequest) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm font-weight-bold shadow-sm">
                                        <i class="fas fa-paper-plane mr-1"></i> Kirim Estimasi ke Manager
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    
    <!-- Modals moved outside table to prevent HTML form stripping -->
    @foreach($purchaseRequest->items as $item)
        @php
            $isProc = Auth::user()->hasRole('procurement');
            $isSuperadmin = Auth::user()->hasRole('superadmin');
            $remainingQty = $item->quantity - $item->received_quantity;
        @endphp

        <!-- Reschedule Modal -->
        <div class="modal fade" id="rescheduleModal-{{ $item->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form action="{{ route('purchase-requests.update-delivery-plans', $item) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                        <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <h5 class="modal-title">Reschedule Kedatangan: {{ $item->item_name }}</h5>
                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body text-left">
                            <p class="mb-3 text-info">Total kuantitas harus sama dengan pesanan ({{ $item->quantity }} {{ $item->uom }}).</p>
                            
                            <div class="form-group border border-secondary p-3 rounded mt-3 position-relative">
                                <label class="text-gray-300 mb-2 font-weight-bold">Ubah Jumlah Rencana Kedatangan</label>
                                <div class="d-flex gap-2 mb-3">
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill btn-plan-count" data-count="1" data-target="#reschedule-container-{{ $item->id }}">1 X</button>
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill btn-plan-count" data-count="2" data-target="#reschedule-container-{{ $item->id }}">2 X</button>
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill btn-plan-count" data-count="3" data-target="#reschedule-container-{{ $item->id }}">3 X</button>
                                </div>
                                
                                <div id="reschedule-container-{{ $item->id }}">
                                    @foreach($item->deliveryPlans->where('is_active', true) as $index => $plan)
                                        <div class="border border-warning p-3 rounded mb-3 position-relative" style="background-color: rgba(255, 193, 7, 0.05); z-index: 10;">
                                            <span class="badge badge-warning position-absolute" style="top: -10px; left: 10px; font-size: 0.85rem;">Kedatangan Saat Ini {{ $loop->iteration }}</span>
                                            
                                            <div class="form-group mt-2">
                                                <label class="text-gray-300 text-sm">Tanggal Rencana Kedatangan *</label>
                                                <input type="date" name="planned_dates[]" class="form-control form-control-sm" required value="{{ $plan->planned_date->format('Y-m-d') }}" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                            </div>
                                            <div class="form-group">
                                                <label class="text-gray-300 text-sm">QTY *</label>
                                                <input type="number" step="0.01" name="planned_quantities[]" class="form-control form-control-sm" required value="{{ $plan->planned_quantity }}" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                            </div>
                                            <div class="form-group mb-0">
                                                <label class="text-gray-300 text-sm">Catatan (Opsional)</label>
                                                <input type="text" name="planned_notes[]" class="form-control form-control-sm" value="{{ $plan->notes }}" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                            </div>
                                            <div class="form-group mt-2 mb-0">
                                                <label class="text-gray-300 text-sm">Attachment Baru (Opsional)</label>
                                                <input type="file" name="planned_attachments[]" class="form-control-file text-sm" accept="image/*,.pdf">
                                                @if($plan->attachment_path)
                                                    <small class="text-info mt-1 d-block"><i class="fas fa-paperclip"></i> Ada file sebelumnya. Upload baru untuk mengganti.</small>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning">Simpan Reschedule</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

<!-- Order Plan Modal -->
                                <div class="modal fade" id="orderModal-{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <form action="{{ route('purchase-requests.update-item-status', $item) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="status" value="ordered">
                                            <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                                                <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                    <h5 class="modal-title">Process Order: {{ $item->item_name }} (Qty: {{ $item->quantity }} {{ $item->uom }})</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body text-left">
                                                    <!-- Banner Alert -->
                                                    <div id="modal-budget-alert-{{ $item->id }}" class="alert alert-success py-2 mb-3 align-items-center" style="display: flex; gap: 8px;">
                                                        <i class="fas fa-check-circle"></i>
                                                        <span id="modal-budget-alert-text-{{ $item->id }}">Semua anggaran kategori tersedia. Total Pengeluaran PO: Rp 0</span>
                                                    </div>

                                                    <!-- Pagu Info Card -->
                                                    <div class="p-3 mb-3 rounded" style="background-color: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-6">
                                                                <span class="text-gray-400 text-sm">Kategori Anggaran (Purpose)</span><br>
                                                                <span class="text-white font-weight-bold">{{ $item->purpose ?? '-' }}</span>
                                                            </div>
                                                            <div class="col-md-6 text-md-right mt-2 mt-md-0">
                                                                <span class="text-gray-400 text-sm">📁 Pagu Tersedia</span><br>
                                                                <span class="text-info font-weight-bold" id="modal-pagu-tersedia-{{ $item->id }}">Memuat...</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="text-gray-300">NO. PO (Kosongkan untuk generate otomatis dari Odoo)</label>
                                                        <input type="text" name="po_number" class="form-control" value="{{ old('po_number', $item->po_number) }}" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="text-gray-300">Nama Vendor</label>
                                                        <input type="text" name="vendor_name" class="form-control" placeholder="Default Vendor" value="{{ old('vendor_name') }}" list="odoo-vendors-list" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="text-gray-300">Harga Satuan Aktual *</label>
                                                        <input type="number" step="0.01" name="actual_price" class="form-control" required 
                                                               value="{{ old('actual_price', $item->actual_price ?? $item->estimated_price) }}" 
                                                               data-item-id="{{ $item->id }}" 
                                                               data-qty="{{ $item->quantity }}" 
                                                               data-purpose="{{ $item->purpose }}" 
                                                               data-dept-id="{{ $purchaseRequest->department_id }}" 
                                                               data-date="{{ $purchaseRequest->request_date->format('Y-m-d') }}"
                                                               style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                    </div>

                                                    <!-- Budget Calculation Row -->
                                                    <div class="form-row mt-3 mb-3 p-3 rounded" style="background-color: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); margin-left: 0; margin-right: 0;">
                                                        <div class="col-md-4 mb-2 mb-md-0">
                                                            <span class="text-gray-400 text-xs">Total Harga Aktual</span><br>
                                                            <span class="text-white font-weight-bold" id="modal-total-harga-{{ $item->id }}">Rp 0</span>
                                                        </div>
                                                        <div class="col-md-4 mb-2 mb-md-0">
                                                            <span class="text-gray-400 text-xs">Sisa Pagu</span><br>
                                                            <span class="font-weight-bold" id="modal-sisa-pagu-{{ $item->id }}" style="color: #f59e0b;">Rp -</span>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <span class="text-gray-400 text-xs">Status Anggaran</span><br>
                                                            <span id="modal-status-badge-{{ $item->id }}" class="badge badge-success py-1 px-2" style="font-size: 0.85rem;"><i class="fas fa-check-circle mr-1"></i> Aman</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group border border-secondary p-3 rounded mt-3 position-relative">
                                                        <label class="text-gray-300 mb-2 font-weight-bold">Jumlah Rencana Kedatangan</label>
                                                        <div class="d-flex gap-2 mb-3">
                                                            <button type="button" class="btn btn-outline-info btn-sm flex-fill btn-plan-count" data-count="1" data-target="#plans-container-{{ $item->id }}">1 X</button>
                                                            <button type="button" class="btn btn-outline-info btn-sm flex-fill btn-plan-count" data-count="2" data-target="#plans-container-{{ $item->id }}">2 X</button>
                                                            <button type="button" class="btn btn-outline-info btn-sm flex-fill btn-plan-count" data-count="3" data-target="#plans-container-{{ $item->id }}">3 X</button>
                                                        </div>
                                                        
                                                        <div id="plans-container-{{ $item->id }}">
                                                            <!-- Will be populated by JS -->
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan PO & Rencana</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>


                                <!-- Approve Modal -->
                                <div class="modal fade" id="approveModal-{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('purchase-requests.approve-item', $item) }}" method="POST">
                                            @csrf
                                            <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                                                <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                    <h5 class="modal-title">Approve Item: {{ $item->item_name }}</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label class="text-gray-300">Notes (Optional)</label>
                                                        <textarea name="notes" class="form-control" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-success">Confirm Approve</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal-{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('purchase-requests.reject-item', $item) }}" method="POST">
                                            @csrf
                                            <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                                                <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                    <h5 class="modal-title">Reject Item: {{ $item->item_name }}</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label class="text-gray-300">Catatan Validasi / Reason for Rejection *</label>
                                                        <textarea name="reject_reason" class="form-control" required style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Send Note Modal -->
                                <div class="modal fade" id="noteModal-{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('purchase-requests.send-note', $item) }}" method="POST">
                                            @csrf
                                            <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                                                <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                    <h5 class="modal-title">Kirim Catatan Validasi: {{ $item->item_name }}</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label class="text-gray-300">Notes ke requester *</label>
                                                        <textarea name="notes" class="form-control" required style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Kirim Note</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Delivery Modal -->
                                <div class="modal fade" id="deliveryModal-{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('purchase-requests.store-delivery', $item) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                                                <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                    <h5 class="modal-title">Input Kedatangan: {{ $item->item_name }}</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-info py-2" style="background-color: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.5); color: #93c5fd;">
                                                        Pesanan: <strong>{{ $item->quantity }}</strong> | Sudah Datang: <strong>{{ $item->received_quantity }}</strong> | Sisa: <strong>{{ $item->quantity - $item->received_quantity }}</strong>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="text-gray-300">Pilih Rencana Kedatangan *</label>
                                                        <select name="delivery_plan_id" class="form-control plan-select-{{ $item->id }}" required style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                            <option value="">-- Pilih Rencana --</option>
                                                            @foreach($item->deliveryPlans->where('is_active', true) as $plan)
                                                                <option value="{{ $plan->id }}" data-qty="{{ $plan->planned_quantity }}" data-date="{{ $plan->planned_date->format('Y-m-d') }}">
                                                                    {{ $plan->planned_date->format('d M Y') }} (Qty: {{ $plan->planned_quantity }} {{ $item->uom }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="col-md-6 form-group">
                                                            <label class="text-gray-300">Jumlah Datang Saat Ini *</label>
                                                            <input type="number" step="0.01" name="received_quantity" id="qty_input_{{ $item->id }}" class="form-control" required style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                        </div>
                                                        <div class="col-md-6 form-group">
                                                            <label class="text-gray-300">Tanggal Kedatangan *</label>
                                                            <input type="date" name="delivery_date" id="date_input_{{ $item->id }}" class="form-control" required style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                        </div>
                                                    </div>
                                                    
                                                    <script>
                                                        document.addEventListener('DOMContentLoaded', function() {
                                                            const select = document.querySelector('.plan-select-{{ $item->id }}');
                                                            const qtyInput = document.getElementById('qty_input_{{ $item->id }}');
                                                            const dateInput = document.getElementById('date_input_{{ $item->id }}');
                                                            
                                                            select.addEventListener('change', function() {
                                                                const option = this.options[this.selectedIndex];
                                                                if(this.value) {
                                                                    qtyInput.value = option.getAttribute('data-qty');
                                                                    dateInput.value = option.getAttribute('data-date');
                                                                } else {
                                                                    qtyInput.value = '';
                                                                    dateInput.value = '';
                                                                }
                                                            });
                                                        });
                                                    </script>
                                                    <div class="form-group">
                                                        <label class="text-gray-300">Catatan / Bukti Terima (Opsional)</label>
                                                        <textarea name="notes" class="form-control mb-2" placeholder="No Resi, Nama Kurir, dsb" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;"></textarea>
                                                        <input type="file" name="delivery_attachment" class="form-control-file text-sm" accept="image/*,.pdf" style="color: #d1d5db;">
                                                    </div>
                                                </div>
                                                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Riwayat</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- History Modal -->
                                <div class="modal fade" id="historyModal-{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                                            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                <h5 class="modal-title">Riwayat Kedatangan: {{ $item->item_name }}</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body p-0">
                                                @if($item->deliveries->isEmpty())
                                                    <div class="p-4 text-center text-muted">Belum ada riwayat kedatangan.</div>
                                                @else
                                                    <ul class="list-group list-group-flush" style="background-color: transparent;">
                                                        @foreach($item->deliveries as $delivery)
                                                            <li class="list-group-item" style="background-color: transparent; border-color: rgba(255,255,255,0.1);">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <strong class="text-success">+{{ $delivery->received_quantity }} {{ $item->uom }}</strong>
                                                                    <small class="text-muted">{{ $delivery->delivery_date->format('d M Y') }}</small>
                                                                </div>
                                                                @if($delivery->notes)
                                                                    <div class="mt-1 small text-gray-400"><i class="fas fa-info-circle mr-1"></i>{{ $delivery->notes }}</div>
                                                                @endif
                                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                                    <div class="small text-muted"><i class="fas fa-user mr-1"></i>Dicatat oleh: {{ $delivery->receiver->name ?? 'System' }}</div>
                                                                    @if($isProc || $isSuperadmin)
                                                                        <div class="btn-group">
                                                                            <button type="button" class="btn btn-warning btn-xs" data-toggle="modal" data-target="#editDeliveryModal-{{ $delivery->id }}" data-dismiss="modal" title="Edit Riwayat"><i class="fas fa-edit"></i></button>
                                                                            <form action="{{ route('purchase-requests.destroy-delivery', $delivery) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('Hapus riwayat ini?');">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="btn btn-danger btn-xs" title="Hapus Riwayat"><i class="fas fa-trash"></i></button>
                                                                            </form>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Delivery Modals -->
                                @foreach($item->deliveries as $delivery)
                                <div class="modal fade" id="editDeliveryModal-{{ $delivery->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('purchase-requests.update-delivery', $delivery) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                                                <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                    <h5 class="modal-title">Edit Kedatangan: {{ $item->item_name }}</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    @php
                                                        $otherDeliveriesTotal = $item->deliveries->where('id', '!=', $delivery->id)->sum('received_quantity');
                                                        $maxAllowed = $item->quantity - $otherDeliveriesTotal;
                                                    @endphp
                                                    <div class="form-group">
                                                        <label class="text-gray-300">Jumlah Datang *</label>
                                                        <input type="number" step="0.01" name="received_quantity" class="form-control" required min="0.01" max="{{ $maxAllowed }}" value="{{ $delivery->received_quantity }}" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="text-gray-300">Tanggal Kedatangan *</label>
                                                        <input type="date" name="delivery_date" class="form-control" required value="{{ $delivery->delivery_date->format('Y-m-d') }}" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="text-gray-300">Catatan / Bukti Terima (Opsional)</label>
                                                        <textarea name="notes" class="form-control mb-2" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">{{ $delivery->notes }}</textarea>
                                                        <input type="file" name="delivery_attachment" class="form-control-file text-sm" accept="image/*,.pdf" style="color: #d1d5db;">
                                                        @if($delivery->attachment_path)
                                                            <small class="text-info mt-1 d-block"><i class="fas fa-paperclip"></i> File saat ini: {{ basename($delivery->attachment_path) }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @endforeach

                                <!-- Sync Odoo Modal -->
                                <div class="modal fade" id="syncOdooModal-{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('purchase-requests.sync-to-odoo', $item) }}" method="POST">
                                            @csrf
                                            <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                                                <div class="modal-header border-bottom-0">
                                                    <h5 class="modal-title"><i class="fas fa-sync-alt mr-2 text-warning"></i>Kirim ke Odoo: {{ $item->item_name }}</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body text-left">
                                                    <p class="text-sm text-gray-300">
                                                        Kirim data pemesanan item ini ke Odoo ERP Anda. Ini akan membuat Purchase Order baru di Odoo.
                                                    </p>
                                                    <div class="form-group mt-3">
                                                        <label class="text-gray-300 text-sm">Nama Vendor *</label>
                                                        <input type="text" name="vendor_name" class="form-control" required placeholder="Masukkan Nama Vendor" list="odoo-vendors-list" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                                        <small class="text-muted text-xs">Jika vendor belum ada di Odoo, sistem akan otomatis membuatnya.</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top-0 pt-0">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-paper-plane mr-1"></i> Kirim Sekarang
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                    @endforeach

<style>
        @media (max-width: 768px) {
            .mobile-modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem) !important;
            }

            .mobile-modal-content {
                max-height: calc(100vh - 1rem);
            }

            .mobile-modal-body {
                max-height: calc(100vh - 140px);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .mobile-safe-footer {
                padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));
            }

            .pr-preview-body {
                width: 100% !important;
                height: calc(100vh - 220px) !important;
            }

            @supports (-webkit-touch-callout: none) {
                .mobile-modal-content {
                    max-height: -webkit-fill-available;
                }

                .mobile-modal-body {
                    max-height: calc(100dvh - 140px);
                }

                .pr-preview-body {
                    height: calc(100dvh - 220px) !important;
                }
            }
        }

        /* Chat Notes Styles */
        .chat-notes-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 250px;
            overflow-y: auto;
            padding-right: 5px;
        }
        
        .chat-bubble {
            max-width: 85%;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            position: relative;
            line-height: 1.3;
        }

        .chat-left {
            align-self: flex-start;
            background-color: #2c313c; /* Dark Grey for others */
            color: #e0e6ed;
            border-bottom-left-radius: 2px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .chat-right {
            align-self: flex-end;
            background-color: #1e3a8a; /* Blue Primary tinted for self */
            color: #ffffff;
            border-bottom-right-radius: 2px;
            border: 1px solid rgba(37,99,235,0.3);
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3px;
        }

        .chat-name {
            font-weight: 600;
            font-size: 0.65rem;
            color: inherit;
            opacity: 0.8;
        }

        .chat-time {
            font-size: 0.6rem;
            color: inherit;
            opacity: 0.6;
            margin-left: 8px;
        }

        .chat-body {
            word-wrap: break-word;
        }
    </style>

    <!-- Preview Modal -->
    <div class="modal fade" id="attachmentPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable mobile-modal-dialog" role="document">
            <div class="modal-content mobile-modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewFilename">File Preview</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0 mobile-modal-body" id="previewBody">
                    <!-- Preview content will be injected here -->
                    <div class="text-center p-5 loading-spinner">
                        <i class="fas fa-spinner fa-spin fa-3x"></i>
                    </div>
                </div>
                <div class="modal-footer mobile-safe-footer">
                    <a href="#" id="downloadLink" class="btn btn-primary" download>
                        <i class="fas fa-download"></i> Download Original
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <datalist id="odoo-vendors-list"></datalist>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const requestDate = "{{ $purchaseRequest->request_date->format('Y-m-d') }}";
            const budgetDetailsCard = document.getElementById('budget-details-card');

            const allItems = [
                @foreach($purchaseRequest->items as $item)
                {
                    id: "{{ $item->id }}",
                    purpose: {!! json_encode($item->purpose) !!},
                    qty: {{ (float) $item->quantity }},
                    estimated_price: {{ (float) ($item->estimated_price ?: 0) }},
                    status: "{{ $item->status }}"
                },
                @endforeach
            ];

            function getPayloadItems() {
                const items = [];
                // First, add all static items
                @foreach($purchaseRequest->items as $item)
                    @if($item->status !== 'pending_estimate' && $item->purpose)
                        items.push({
                            purpose: {!! json_encode($item->purpose) !!},
                            amount: {{ (float) ($item->quantity * $item->estimated_price) }},
                            actual_amount: {{ (float) ($item->actual_total_price ?? 0) }},
                            is_committed: {{ in_array($item->status, ['ordered', 'delivered', 'completed']) ? 'true' : 'false' }},
                            is_uncommitted_active: {{ in_array($item->status, ['pending', 'approved_om', 'approved_gm', 'approved_proc']) ? 'true' : 'false' }}
                        });
                    @endif
                @endforeach

                // Then, add all estimate inputs
                document.querySelectorAll('.estimate-price-input').forEach(input => {
                    const price = parseFloat(input.value) || 0;
                    const qty = parseFloat(input.dataset.qty) || 0;
                    const purpose = input.dataset.purpose;
                    
                    if (purpose) {
                        items.push({
                            purpose: purpose,
                            amount: qty * price,
                            actual_amount: 0,
                            is_committed: false,
                            is_uncommitted_active: true // treat as active uncommitted since it is being submitted
                        });
                    }
                });

                return items;
            }

            function refreshBudgetDetails() {
                const payloadItems = getPayloadItems();
                if (payloadItems.length === 0 || !budgetDetailsCard) return;

                const budgetDetailsTbody = document.getElementById('budget-details-tbody');

                fetch('/api/internal/check-budget', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        request_date: requestDate,
                        department_id: {{ $purchaseRequest->department_id }},
                        reference: "{{ $purchaseRequest->pr_number }}",
                        items: payloadItems
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.results) {
                        budgetDetailsCard.style.display = 'block';
                        let html = '';
                        
                        // Let's compute group amounts to show requested amount per purpose
                        const groupedAmounts = {};
                        const groupedActualAmounts = {};
                        const groupedCommittedAmounts = {};
                        const groupedUncommittedActiveAmounts = {};
                        payloadItems.forEach(item => {
                            groupedAmounts[item.purpose] = (groupedAmounts[item.purpose] || 0) + item.amount;
                            groupedActualAmounts[item.purpose] = (groupedActualAmounts[item.purpose] || 0) + item.actual_amount;
                            if (item.is_committed) {
                                groupedCommittedAmounts[item.purpose] = (groupedCommittedAmounts[item.purpose] || 0) + item.actual_amount;
                            } else if (item.is_uncommitted_active) {
                                groupedUncommittedActiveAmounts[item.purpose] = (groupedUncommittedActiveAmounts[item.purpose] || 0) + item.amount;
                            }
                        });

                        Object.keys(data.results).forEach(purpose => {
                            const result = data.results[purpose];
                            const requested = groupedAmounts[purpose] || 0;
                            const actual = groupedActualAmounts[purpose] || 0;
                            const actualCommitted = groupedCommittedAmounts[purpose] || 0;
                            const estimatedUncommitted = groupedUncommittedActiveAmounts[purpose] || 0;
                            
                            const limit = result.budget_limit !== null ? parseFloat(result.budget_limit) : null;
                            const usageRaw = result.current_usage !== null ? parseFloat(result.current_usage) : null;
                            
                            // Realisasi sebelum PR: usage dari Finance dikurangi pengeluaran aktual dari PR ini yang sudah tercommit
                            const usage = usageRaw !== null ? (usageRaw - actualCommitted) : null;
                            
                            // Sisa pagu: limit - usageRaw - estimatedUncommitted
                            const remaining = limit !== null && usageRaw !== null ? (limit - usageRaw - estimatedUncommitted) : null;
                            
                            const limitStr = limit !== null ? formatIDR(limit) : '-';
                            const usageStr = usage !== null ? formatIDR(usage) : '-';
                            const remainingStr = remaining !== null ? formatIDR(remaining) : '-';
                            
                            // Split columns
                            const estPR = requested;
                            const actPR = actualCommitted;
                            
                            const estPRStr = estPR > 0 ? formatIDR(estPR) : '-';
                            let actPRStr = actPR > 0 ? formatIDR(actPR) : '-';
                            if (actPR > 0 && result.recorded_expense_amount !== null && Math.abs(parseFloat(result.recorded_expense_amount) - actPR) > 0.01) {
                                actPRStr = `${formatIDR(actPR)}<br><span class="badge badge-warning text-xs mt-1" title="Direvisi oleh Finance"><i class="fas fa-edit mr-1"></i>Rev: ${formatIDR(result.recorded_expense_amount)}</span>`;
                            }
                            
                            let statusBadge = '<span class="badge badge-secondary">-</span>';
                            if (remaining !== null) {
                                if (remaining >= 0) {
                                    statusBadge = '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Aman</span>';
                                } else {
                                    statusBadge = '<span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i> Over Budget</span>';
                                }
                            }

                            html += `
                                <tr>
                                    <td><strong>${purpose}</strong></td>
                                    <td class="text-right text-info font-weight-bold">${limitStr}</td>
                                    <td class="text-right text-muted">${usageStr}</td>
                                    <td class="text-right text-success font-weight-bold">${estPRStr}</td>
                                    <td class="text-right text-white font-weight-bold">${actPRStr}</td>
                                    <td class="text-right ${remaining < 0 ? 'text-danger font-weight-bold' : 'text-warning font-weight-bold'}">${remainingStr}</td>
                                    <td class="text-center">${statusBadge}</td>
                                </tr>
                            `;
                        });
                        
                        budgetDetailsTbody.innerHTML = html;

                        // Now, update the smart budget alert in each item's row (under the price/input)
                        allItems.forEach(item => {
                            const alertDiv = document.getElementById(`smart-budget-alert-${item.id}`);
                            if (!alertDiv) return;

                            const purpose = item.purpose;
                            if (!purpose) {
                                alertDiv.innerHTML = '';
                                return;
                            }

                            // If there is an input field for this item, calculate its live total
                            const input = document.querySelector(`.estimate-price-input[data-id="${item.id}"]`);
                            let currentPrice = item.estimated_price;
                            if (input) {
                                currentPrice = parseFloat(input.value) || 0;
                            }

                            if (!data.results || !data.results[purpose]) {
                                alertDiv.innerHTML = `<span class="badge badge-secondary text-xs" style="font-size: 0.65rem; padding: 2px 4px;"><i class="fas fa-info-circle mr-1"></i> Pagu tidak dikonfigurasi</span>`;
                                return;
                            }

                            const result = data.results[purpose];
                            const limit = result.budget_limit !== null ? parseFloat(result.budget_limit) : null;
                            const usageRaw = result.current_usage !== null ? parseFloat(result.current_usage) : null;

                            // Calculate remaining budget based on the live payload (includes modified values of inputs)
                            const estimatedUncommitted = payloadItems
                                .filter(it => it.purpose === purpose && it.is_uncommitted_active)
                                .reduce((sum, it) => sum + it.amount, 0);

                            const remaining = limit !== null && usageRaw !== null ? (limit - usageRaw - estimatedUncommitted) : null;

                            if (remaining !== null) {
                                if (remaining >= 0) {
                                    alertDiv.innerHTML = `<span class="badge badge-success text-xs" style="font-size: 0.65rem; padding: 2px 4px;"><i class="fas fa-check-circle mr-1"></i> Pagu Aman (Sisa: ${formatIDR(remaining)})</span>`;
                                } else {
                                    alertDiv.innerHTML = `<span class="badge badge-danger text-xs" style="font-size: 0.65rem; padding: 2px 4px; white-space: normal; display: inline-block; text-align: left;"><i class="fas fa-exclamation-triangle mr-1"></i> Over Budget (Kurang: ${formatIDR(Math.abs(remaining))})</span>`;
                                }
                            } else {
                                alertDiv.innerHTML = `<span class="badge badge-secondary text-xs" style="font-size: 0.65rem; padding: 2px 4px;"><i class="fas fa-info-circle mr-1"></i> Pagu tidak dikonfigurasi</span>`;
                            }
                        });

                    } else {
                        budgetDetailsCard.style.display = 'block';
                        budgetDetailsTbody.innerHTML = `
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fas fa-info-circle mr-1"></i> ${data.message || 'Informasi pagu tidak dikonfigurasi untuk kategori ini.'}
                                </td>
                            </tr>
                        `;
                    }
                })
                .catch(error => {
                    budgetDetailsCard.style.display = 'block';
                    budgetDetailsTbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-danger">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Gagal memuat rincian anggaran.
                            </td>
                        </tr>
                    `;
                });
            }

            // Run initial load
            refreshBudgetDetails();

            // Listen for input changes on estimates
            $(document).on('input keyup', '.estimate-price-input', function() {
                const input = this;
                const itemId = input.dataset.id;
                const qty = parseFloat(input.dataset.qty) || 0;
                const price = parseFloat(input.value) || 0;
                const total = qty * price;
                
                $(`#total-estimate-${itemId}`).text('Total: ' + formatIDR(total));
                refreshBudgetDetails();
            });

            function formatIDR(num) {
                return 'Rp ' + parseFloat(num).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            }

            $('[id^="approveModal-"], [id^="rejectModal-"], [id^="noteModal-"], [id^="deliveryModal-"], [id^="historyModal-"], [id^="editDeliveryModal-"], [id^="orderModal-"], [id^="planModal-"], [id^="syncOdooModal-"], #attachmentPreviewModal, #prPreviewModal').each(function() {
                if (this.parentNode !== document.body) {
                    document.body.appendChild(this);
                }
            });

            // Delivery Plan UI Logic (using jQuery event delegation)
            $(document).on('click', '.btn-plan-count', function() {
                const count = parseInt($(this).data('count'));
                const containerId = $(this).data('target');
                const $container = $(containerId);
                
                // Highlight active button
                $(this).siblings('.btn-plan-count').removeClass('btn-info text-white').addClass('btn-outline-info');
                $(this).removeClass('btn-outline-info').addClass('btn-info text-white');
                
                let html = '';
                const labels = ['Pertama', 'Kedua', 'Ketiga'];
                
                for(let i=0; i<count; i++) {
                    html += `
                    <div class="border border-info p-3 rounded mb-3 position-relative" style="background-color: rgba(23, 162, 184, 0.05); z-index: 10;">
                        <span class="badge badge-info position-absolute" style="top: -10px; left: 10px; font-size: 0.85rem;">Kedatangan ${labels[i]}</span>
                        <div class="form-group mt-2">
                            <label class="text-gray-300 text-sm">Tanggal Rencana Kedatangan *</label>
                            <input type="date" name="planned_dates[]" class="form-control form-control-sm" required style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                        </div>
                        <div class="form-group">
                            <label class="text-gray-300 text-sm">QTY *</label>
                            <input type="number" step="0.01" name="planned_quantities[]" class="form-control form-control-sm" required style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                        </div>
                        <div class="form-group mb-0">
                            <label class="text-gray-300 text-sm">Catatan (Opsional)</label>
                            <input type="text" name="planned_notes[]" class="form-control form-control-sm" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                        </div>
                        <div class="form-group mt-2 mb-0">
                            <label class="text-gray-300 text-sm">Attachment (Opsional)</label>
                            <input type="file" name="planned_attachments[]" class="form-control-file text-sm" accept="image/*,.pdf">
                        </div>
                    </div>
                    `;
                }
                $container.html(html);
            });

            $(document).on('shown.bs.modal', '.modal', function () {
                $(this).css('padding-right', '0');
                $('body').addClass('modal-open');
            });

            $('.preview-attachment').on('click', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                const filename = $(this).data('filename');
                const extension = filename.split('.').pop().toLowerCase();
                
                $('#previewFilename').text(filename);
                $('#downloadLink').attr('href', url);
                $('#previewBody').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-3x"></i></div>');
                
                const $attachmentModal = $('#attachmentPreviewModal');
                if (!$attachmentModal.parent().is('body')) {
                    $attachmentModal.appendTo('body');
                }

                $attachmentModal.modal('show');
                
                let content = '';
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
                    content = `<div class="text-center p-3"><img src="${url}" class="img-fluid rounded shadow" style="max-height: 80vh;"></div>`;
                } else if (extension === 'pdf') {
                    content = `<iframe src="${url}#view=FitH" width="100%" height="600px" style="border: none;"></iframe>`;
                } else {
                    content = `
                        <div class="text-center p-5">
                            <i class="fas fa-file-alt fa-5x mb-3 text-muted"></i>
                            <h4>Format tidak mendukung pratinjau langsung</h4>
                            <p>Silakan unduh file untuk melihat kontennya.</p>
                            <a href="${url}" class="btn btn-lg btn-primary mt-2" download>Unduh Sekarang</a>
                        </div>`;
                }
                
                // Small delay for smooth transition
                setTimeout(() => {
                    $('#previewBody').html(content);
                }, 300);
            });

            // PR Preview Modal Functions
            window.openPreviewModal = function(previewUrl, downloadUrl) {
                $('#prPreviewFrame').attr('src', previewUrl);
                $('#downloadPdfLink').attr('href', downloadUrl);

                const $prModal = $('#prPreviewModal');
                if (!$prModal.parent().is('body')) {
                    $prModal.appendTo('body');
                }

                $prModal.modal('show');
            };

            window.printPreview = function() {
                const iframe = document.getElementById('prPreviewFrame');
                iframe.contentWindow.print();
            };

            // Order Modal Budget Checking Logic
            $(document).on('shown.bs.modal', '[id^="orderModal-"]', function() {
                const modal = $(this);
                const priceInput = modal.find('input[name="actual_price"]');
                checkModalBudget(priceInput);
            });

            $(document).on('input keyup', '[id^="orderModal-"] input[name="actual_price"]', function() {
                checkModalBudget($(this));
            });

            function checkModalBudget(input) {
                const itemId = input.data('item-id');
                const qty = parseFloat(input.data('qty')) || 0;
                const price = parseFloat(input.val()) || 0;
                const purpose = input.data('purpose');
                const deptId = input.data('dept-id');
                const date = input.data('date');
                
                const totalActual = qty * price;
                
                // Update total actual price in UI
                $(`#modal-total-harga-${itemId}`).text(formatIDR(totalActual));
                
                if (!purpose) return;
                
                // Call internal check budget
                fetch('/api/internal/check-budget', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        request_date: date,
                        department_id: deptId,
                        purpose: purpose,
                        requested_amount: totalActual
                    })
                })
                .then(response => response.json())
                .then(data => {
                    const alertDiv = $(`#modal-budget-alert-${itemId}`);
                    const alertText = $(`#modal-budget-alert-text-${itemId}`);
                    const paguSpan = $(`#modal-pagu-tersedia-${itemId}`);
                    const sisaSpan = $(`#modal-sisa-pagu-${itemId}`);
                    const badgeSpan = $(`#modal-status-badge-${itemId}`);
                    
                    const limit = data.budget_limit !== null ? parseFloat(data.budget_limit) : 0;
                    const usage = data.current_usage !== null ? parseFloat(data.current_usage) : 0;
                    const remaining = data.remaining_budget !== null ? parseFloat(data.remaining_budget) : 0;
                    
                    const isAllowed = data.is_allowed !== false;
                    
                    // Show Pagu Tersedia (Budget limit - current usage before this item's actual amount)
                    const paguTersedia = limit - usage;
                    paguSpan.text(formatIDR(paguTersedia));
                    
                    // Show Sisa Pagu (paguTersedia - totalActual)
                    sisaSpan.text(formatIDR(remaining));
                    
                    if (remaining < 0) {
                        sisaSpan.removeClass('text-warning text-success').addClass('text-danger');
                    } else {
                        sisaSpan.removeClass('text-danger').addClass('text-warning');
                    }
                    
                    // Update Status Badge and Alert Banner
                    if (isAllowed) {
                        // Safe
                        badgeSpan.removeClass('badge-danger').addClass('badge-success')
                            .html('<i class="fas fa-check-circle mr-1"></i> Aman');
                        
                        alertDiv.removeClass('alert-danger').addClass('alert-success');
                        alertText.text(`Semua anggaran kategori tersedia. Total Pengeluaran PO: ${formatIDR(totalActual)}`);
                        alertDiv.find('i').removeClass('fa-exclamation-triangle').addClass('fa-check-circle');
                    } else {
                        // Over Budget (Soft Warning - Opsi 1)
                        badgeSpan.removeClass('badge-success').addClass('badge-danger')
                            .html('<i class="fas fa-exclamation-triangle mr-1"></i> Over Budget');
                        
                        alertDiv.removeClass('alert-success').addClass('alert-danger');
                        alertText.text(`Peringatan: Total Pengeluaran PO (${formatIDR(totalActual)}) melebihi sisa anggaran!`);
                        alertDiv.find('i').removeClass('fa-check-circle').addClass('fa-exclamation-triangle');
                    }
                })
                .catch(error => {
                    console.error('Error fetching budget status in modal:', error);
                });
            }

            // Fetch Odoo vendors asynchronously
            fetch('{{ route("api.odoo.vendors") }}')
                .then(res => res.json())
                .then(response => {
                    if (response.success && response.data) {
                        const datalist = document.getElementById('odoo-vendors-list');
                        if (datalist) {
                            let html = '';
                            response.data.forEach(vendor => {
                                html += `<option value="${vendor.name}">`;
                            });
                            datalist.innerHTML = html;
                        }
                    }
                })
                .catch(err => console.error('Failed to fetch Odoo vendors:', err));
        });
    </script>

    <!-- PR Preview Modal -->
    <div class="modal fade" id="prPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable mobile-modal-dialog" role="document" style="max-width: 210mm; margin: 1.75rem auto;">
            <div class="modal-content mobile-modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Nota PR</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0 mobile-modal-body pr-preview-body" style="width: 210mm; height: 297mm; overflow: auto;">
                    <iframe id="prPreviewFrame" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
                <div class="modal-footer mobile-safe-footer">
                    <a href="#" id="downloadPdfLink" class="btn btn-success">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
