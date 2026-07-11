<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:0.5rem;">
            <h2 class="font-semibold text-xl leading-tight mb-0">
                {{ $title ?? __('Purchase Requests') }}
            </h2>
            @can('create pr')
            @if(empty($hideCreateButton))
            <a href="{{ route('purchase-requests.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Create new PR
            </a>
            @endif
            @endcan
        </div>
    </x-slot>

    <style>
        @media (max-width: 767.98px) {
            /* Department tabs scrollable */
            .dept-tabs { overflow-x: auto; flex-wrap: nowrap !important; padding-bottom: 4px; }
            .dept-tabs .nav-item { flex-shrink: 0; }
            /* Filter form stacks vertically */
            .filter-form .col-md-5, .filter-form .col-md-3, .filter-form .col-md-4 {
                flex: 0 0 100%; max-width: 100%;
            }
            /* PR row action buttons compact */
            .pr-actions .btn-xs { padding: 0.2rem 0.4rem !important; font-size: 0.72rem !important; }
            /* Hide Date and Department columns on very small screen */
        }
    </style>

    <div>
        <div class="card shadow-sm rounded-lg">
            <div class="card-body p-3">


                    @if(!isset($title) || !str_contains(strtolower($title), 'needs revision'))
                        @if(auth()->user()->hasAnyRole(['procurement', 'superadmin']))
                        <!-- Department Tabs (scrollable on mobile) -->
                        <div class="mb-3">
                            <ul class="nav nav-pills dept-tabs p-1 rounded-lg" style="background-color: rgba(255,255,255,0.05);">
                                <li class="nav-item">
                                    <a class="nav-link {{ !request('department_id') ? 'active bg-primary' : 'text-muted' }} small py-1 px-3" href="{{ request()->fullUrlWithQuery(['department_id' => null]) }}">
                                        All
                                    </a>
                                </li>
                                @foreach($departments as $dept)
                                <li class="nav-item">
                                    <a class="nav-link {{ request('department_id') == $dept->id ? 'active bg-primary' : 'text-muted' }} small py-1 px-3" href="{{ request()->fullUrlWithQuery(['department_id' => $dept->id]) }}">
                                        {{ $dept->code }}
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Filter Form -->
                        <div class="card mb-3 shadow-sm" style="background-color: rgba(255,255,255,0.02)">
                            <div class="card-body py-2">
                                <form action="{{ request()->url() }}" method="GET" class="row filter-form align-items-end mb-0">
                                    @if(request('department_id'))
                                        <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                                    @endif
                                    
                                    <div class="col-md-5 col-12 mb-2">
                                        <label for="search" class="form-label font-weight-bold small text-uppercase opacity-75">Search</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-transparent border-right-0 text-muted">
                                                    <i class="fas fa-search"></i>
                                                </span>
                                            </div>
                                            <input type="text" name="search" id="search" class="form-control border-left-0" placeholder="PR Number, Purpose, Requester..." value="{{ request('search') }}">
                                        </div>
                                    </div>

                                     <div class="col-md-3 col-6 mb-2">
                                        <label for="status" class="form-label font-weight-bold small text-uppercase opacity-75">Status</label>
                                        <select name="status" id="status" class="form-control form-control-sm">
                                            <option value="">All Status</option>
                                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved_om" {{ request('status') == 'approved_om' ? 'selected' : '' }}>Approved (L1)</option>
                                            <option value="rejected_om" {{ request('status') == 'rejected_om' ? 'selected' : '' }}>Rejected (L1)</option>
                                            <option value="approved_gm" {{ request('status') == 'approved_gm' ? 'selected' : '' }}>Approved (GM)</option>
                                            <option value="rejected_gm" {{ request('status') == 'rejected_gm' ? 'selected' : '' }}>Rejected (GM)</option>
                                            <option value="approved_proc" {{ request('status') == 'approved_proc' ? 'selected' : '' }}>Menunggu Procurement Holding</option>
                                            <option value="rejected_proc" {{ request('status') == 'rejected_proc' ? 'selected' : '' }}>Rejected (Proc)</option>
                                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                        </select>
                                    </div>

                                    @if(auth()->user()->hasAnyRole(['superadmin', 'procurement_holding']) && isset($companies) && $companies->isNotEmpty())
                                    <div class="col-md-3 col-6 mb-2">
                                        <label for="company_id" class="form-label font-weight-bold small text-uppercase opacity-75">Company</label>
                                        <select name="company_id" id="company_id" class="form-control form-control-sm">
                                            <option value="">All Companies</option>
                                            @foreach($companies as $c)
                                                <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->code }} - {{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif

                                    <div class="col-md-4 col-6 mb-2">
                                        <label class="d-none d-md-block" style="opacity:0;">.</label>
                                        <div class="d-flex" style="gap:6px;">
                                            <a href="{{ request()->fullUrlWithQuery(['awaiting_approval' => request('awaiting_approval') ? null : 1]) }}" 
                                               class="btn btn-sm flex-fill {{ request('awaiting_approval') ? 'btn-warning' : 'btn-outline-warning' }}" 
                                               title="Awaiting my approval">
                                                <i class="fas fa-clock"></i> <span class="d-none d-md-inline">Awaiting Me</span>
                                            </a>
                                            <button type="submit" class="btn btn-primary btn-sm flex-fill">Filter</button>
                                            <a href="{{ request()->url() }}" class="btn btn-secondary btn-sm" title="Reset">
                                                <i class="fas fa-undo"></i>
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @else
                            <!-- Simple Search for Regular User -->
                            <div class="card mb-4 shadow-sm" style="background-color: rgba(255,255,255,0.02)">
                                <div class="card-body py-2">
                                    <form action="{{ request()->url() }}" method="GET" class="row align-items-end mb-0">
                                        <div class="col-md-10 mb-2">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-transparent border-right-0 text-muted">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                                <input type="text" name="search" id="search" class="form-control border-left-0" placeholder="Cari PR Number, Tujuan, atau Nama Item..." value="{{ request('search') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <button type="submit" class="btn btn-primary btn-sm btn-block">Cari</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endif



                    <div class="table-responsive">
                        <table class="table table-hover table-borderless text-sm table-stack">
                            <thead>
                                <tr>
                                    <th>PR Number</th>
                                    <th>Date</th>
                                    <th>Company</th>
                                    <th>Requester</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseRequests as $pr)
                                <tr>
                                    <td data-label="PR No."><strong>{{ $pr->pr_number }}</strong></td>
                                    <td data-label="Date">{{ $pr->request_date->format('d M Y') }}</td>
                                    <td data-label="Company">{{ $pr->company->code ?? '-' }}</td>
                                    <td data-label="Requester">{{ $pr->user->name }}</td>
                                    <td data-label="Dept.">{{ $pr->department->code }}</td>
                                    <td data-label="Status">
                                        @php
                                            $status = $pr->approval_status;
                                            $totalItems = $pr->items->count();
                                            $approvedItems = $pr->items->filter(function ($item) {
                                                return in_array($item->status, ['approved_om', 'approved_gm', 'approved_proc', 'ordered', 'delivered', 'completed']);
                                            })->count();

                                            $badgeClass = match($status) {
                                                'Draft' => 'badge-secondary',
                                                'Pending' => 'badge-warning',
                                                'Revision Required' => 'badge-danger',
                                                'Partial / Revision' => 'badge-warning',
                                                'Processing' => 'badge-info',
                                                'Approved (OM)', 'Approved (FAT)', 'Approved (GM)' => 'badge-info',
                                                'Menunggu Procurement Holding' => 'badge-warning',
                                                'Ordered' => 'badge-primary',
                                                'Delivered' => 'badge-teal',
                                                'Completed' => 'badge-success',
                                                default => 'badge-secondary'
                                            };

                                            $progressClass = 'badge-secondary';
                                            if ($totalItems > 0 && $approvedItems === $totalItems) {
                                                $progressClass = 'badge-success';
                                            } elseif ($approvedItems > 0) {
                                                $progressClass = 'badge-info';
                                            }
                                        @endphp
                                        <div>
                                            <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                                            @if($totalItems > 0)
                                                <span class="badge {{ $progressClass }} ml-1">{{ $approvedItems }}/{{ $totalItems }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="td-actions">
                                        <button type="button" class="btn btn-info btn-xs" data-toggle="collapse" data-target="#pr-details-{{ $pr->id }}" aria-expanded="false" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        @if($pr->isEditable() && (auth()->id() == $pr->user_id || auth()->user()->hasRole('superadmin')))
                                        <a href="{{ route('purchase-requests.edit', $pr) }}" class="btn btn-warning btn-xs" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endif

                                        @if($pr->status === 'draft' && (auth()->id() == $pr->user_id || auth()->user()->hasRole('superadmin')))
                                        <form action="{{ route('purchase-requests.submit-draft', $pr) }}" method="POST" class="d-inline form-confirm" data-message="Apakah Anda yakin ingin mengajukan Purchase Request ini?">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-xs" title="Ajukan PR">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                        @endif

                                        @if(auth()->id() == $pr->user_id && $pr->isDeletable())
                                        <form action="{{ route('purchase-requests.destroy', $pr) }}" method="POST" class="d-inline form-confirm" data-message="Delete this PR?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-xs" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="tr-expand">
                                    <td colspan="6" class="p-0" style="border: none;">
                                        <div id="pr-details-{{ $pr->id }}" class="collapse">
                                            <div class="detail-panel-inner rounded shadow border-0" style="background-color: rgba(0, 0, 0, 0.18); margin: 0.5rem 0;">
                                                @if($pr->items->count() > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-borderless text-xs mb-0 table-stack" style="color: #cbd5e1;">
                                                        <thead class="text-uppercase" style="opacity: 0.7; font-size: 0.7rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                            <tr>
                                                                <th class="pb-2">Item Name</th>
                                                                <th class="pb-2">Qty</th>
                                                                <th class="pb-2">Status</th>
                                                                <th class="pb-2">Due Date</th>
                                                                <th class="pb-2">Request Date</th>
                                                                <th class="pb-2 text-right">Added On</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($pr->items as $item)
                                                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                                                                <td data-label="Item" class="font-weight-bold py-2 text-white">{{ $item->item_name }}</td>
                                                                <td data-label="Qty" class="py-2">{{ $item->quantity }} {{ $item->uom }}</td>
                                                                <td data-label="Status" class="py-2">
                                                                    <span class="badge badge-{{ $item->status == 'pending' ? 'warning' : (str_contains($item->status, 'rejected') ? 'danger' : 'success') }}">
                                                                        {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                                                    </span>
                                                                </td>
                                                                <td data-label="Due Date" class="py-2">{{ $item->due_date ?? '-' }}</td>
                                                                <td data-label="Req. Date" class="py-2">{{ $pr->request_date->format('d M Y') }}</td>
                                                                <td data-label="Added" class="py-2 text-muted">{{ $item->created_at->format('d M, H:i') }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @else
                                                <p class="text-center text-muted my-3 text-sm">Belum ada item untuk PR ini.</p>
                                                @endif
                                                
                                                <div class="text-center mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.05); padding: 0.75rem;">
                                                    <a href="{{ route('purchase-requests.show', $pr) }}" class="btn btn-primary btn-block rounded-pill shadow-sm" style="font-weight: 500; max-width: 400px; margin: 0 auto;">
                                                        Lihat Detail Penuh PR <i class="fas fa-arrow-right ml-1"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No Purchase Requests found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $purchaseRequests->links() }}
                    </div>
            </div>
        </div>
    </div>
</x-app-layout>
