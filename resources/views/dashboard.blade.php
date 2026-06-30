<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-weight-bold tracking-tight mb-0">
                {{ __('Overview') }}
            </h2>
            <div class="d-none d-md-block text-muted text-sm">
                <i class="far fa-calendar-alt mr-1"></i> {{ \Carbon\Carbon::now()->format('l, d F Y') }}
            </div>
        </div>
    </x-slot>

    <style>
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.3) !important;
        }
        .stat-icon-wrapper {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        .stat-card:hover .stat-icon-wrapper {
            transform: scale(1.1);
        }
        .border-primary-accent { border-left: 4px solid #3b82f6 !important; }
        .text-primary-accent { color: #3b82f6 !important; }
        .bg-primary-soft { background-color: rgba(59, 130, 246, 0.15) !important; }

        .border-warning-accent { border-left: 4px solid #f59e0b !important; }
        .text-warning-accent { color: #f59e0b !important; }
        .bg-warning-soft { background-color: rgba(245, 158, 11, 0.15) !important; }

        .border-success-accent { border-left: 4px solid #10b981 !important; }
        .text-success-accent { color: #10b981 !important; }
        .bg-success-soft { background-color: rgba(16, 185, 129, 0.15) !important; }

        .border-danger-accent { border-left: 4px solid #ef4444 !important; }
        .text-danger-accent { color: #ef4444 !important; }
        .bg-danger-soft { background-color: rgba(239, 68, 68, 0.15) !important; }

        .border-info-accent { border-left: 4px solid #06b6d4 !important; }
        .text-info-accent { color: #06b6d4 !important; }
        .bg-info-soft { background-color: rgba(6, 182, 212, 0.15) !important; }
        
        .border-purple-accent { border-left: 4px solid #8b5cf6 !important; }
        .text-purple-accent { color: #8b5cf6 !important; }
        .bg-purple-soft { background-color: rgba(139, 92, 246, 0.15) !important; }
        
        .tracking-wider { letter-spacing: 0.05em; }

        /* --- Dashboard Mobile --- */
        @media (max-width: 767.98px) {
            /* Stat cards 2-column grid on mobile */
            .row .col-lg-3.col-sm-6 { flex: 0 0 50%; max-width: 50%; padding: 0.3rem !important; }
            .stat-card .card-body { padding: 0.75rem !important; }
            .stat-icon-wrapper { width: 40px !important; height: 40px !important; }
            .stat-card h5 { font-size: 1.4rem !important; }
            .stat-card p { font-size: 0.72rem !important; }

            /* Ongoing monitoring table compact */
            .monitoring-table th, .monitoring-table td { font-size: 0.72rem !important; }

            /* Hide date column on small screens */
            .hide-mobile { display: none !important; }

            /* Recent PR section stacks nicely */
            .recent-pr-row { flex-direction: column !important; }
        }
    </style>

    <!-- Small boxes (Stat box) -->
    <div class="row">
        @if(Auth::user()->hasRole('superadmin'))
            <div class="col-lg-3 col-sm-6">
                <div class="card stat-card border-0 shadow-sm border-primary-accent mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">Total PR</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['total_pr'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-primary-soft">
                                <i class="fas fa-file-invoice-dollar text-primary-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('purchase-requests.index') }}" class="text-sm font-weight-medium text-primary-accent mt-3 d-inline-block" style="text-decoration: none;">View All <i class="fas fa-arrow-right ml-1 text-xs"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-sm-6">
                <div class="card stat-card border-0 shadow-sm border-warning-accent mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">Pending</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['pending_pr'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-warning-soft">
                                <i class="fas fa-clock text-warning-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('purchase-requests.index', ['status' => 'pending']) }}" class="text-sm font-weight-medium text-warning-accent mt-3 d-inline-block" style="text-decoration: none;">Review Now <i class="fas fa-arrow-right ml-1 text-xs"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-sm-6">
                <div class="card stat-card border-0 shadow-sm border-success-accent mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">Total Users</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['total_users'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-success-soft">
                                <i class="fas fa-users text-success-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('users.index') }}" class="text-sm font-weight-medium text-success-accent mt-3 d-inline-block" style="text-decoration: none;">Manage Users <i class="fas fa-arrow-right ml-1 text-xs"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-sm-6">
                <div class="card stat-card border-0 shadow-sm border-purple-accent mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">Departments</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['total_departments'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-purple-soft">
                                <i class="fas fa-building text-purple-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('departments.index') }}" class="text-sm font-weight-medium text-purple-accent mt-3 d-inline-block" style="text-decoration: none;">Manage Depts <i class="fas fa-arrow-right ml-1 text-xs"></i></a>
                    </div>
                </div>
            </div>

        @elseif(Auth::user()->hasRole('user'))
             <div class="col-lg-3 col-sm-6">
                <div class="card stat-card border-0 shadow-sm border-primary-accent mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">My Requests</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['my_pr'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-primary-soft">
                                <i class="fas fa-file-alt text-primary-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
             <div class="col-lg-3 col-sm-6">
                <div class="card stat-card border-0 shadow-sm border-warning-accent mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">Pending</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['pending_pr'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-warning-soft">
                                <i class="fas fa-hourglass-half text-warning-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
             <div class="col-lg-3 col-sm-6">
                <div class="card stat-card border-0 shadow-sm border-success-accent mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">Approved</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['approved_pr'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-success-soft">
                                <i class="fas fa-check-circle text-success-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
             <div class="col-lg-3 col-sm-6">
                <div class="card stat-card border-0 shadow-sm border-danger-accent mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">Rejected</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['rejected_pr'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-danger-soft">
                                <i class="fas fa-times-circle text-danger-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
             <!-- Managers / Procurement -->
             <div class="col-lg-4 col-md-4 mb-4">
                <div class="card stat-card border-0 shadow-sm border-warning-accent h-100">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">PR to Review</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['pr_to_review'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-warning-soft">
                                <i class="fas fa-clipboard-check text-warning-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('purchase-requests.approvals') }}" class="text-sm font-weight-medium text-warning-accent mt-3 d-inline-block" style="text-decoration: none;">Review Now <i class="fas fa-arrow-right ml-1 text-xs"></i></a>
                    </div>
                </div>
            </div>
            
             <div class="col-lg-4 col-md-4 mb-4">
                <div class="card stat-card border-0 shadow-sm border-primary-accent h-100">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">Total PR in System</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['total_pr'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-primary-soft">
                                <i class="fas fa-list text-primary-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                        <a href="{{ route('purchase-requests.index') }}" class="text-sm font-weight-medium text-primary-accent mt-3 d-inline-block" style="text-decoration: none;">View All <i class="fas fa-arrow-right ml-1 text-xs"></i></a>
                    </div>
                </div>
            </div>
            
             <div class="col-lg-4 col-md-4 mb-4">
                <div class="card stat-card border-0 shadow-sm border-success-accent h-100">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-uppercase text-xs font-weight-bold mb-1 text-muted tracking-wider">Approved Today</p>
                                <h3 class="mb-0 font-weight-bolder text-white">{{ $stats['approved_today'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center bg-success-soft">
                                <i class="fas fa-calendar-check text-success-accent" style="font-size: 1.3rem;"></i>
                            </div>
                        </div>
                        <div class="mt-3 text-sm d-inline-block" style="opacity: 0; pointer-events: none;">&nbsp;</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-xl-5 col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 bg-transparent py-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold tracking-tight">Status Distribution</h5>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7 col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 bg-transparent py-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold tracking-tight">Monthly PR Trends</h5>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ongoing Items Monitoring Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header border-0 bg-transparent py-3" style="display:flex; flex-wrap:wrap; align-items:center; gap:0.5rem;">
                    <h5 class="mb-0 font-weight-bold tracking-tight" style="color: #f8fafc; flex: 1 1 auto;">
                        <i class="fas fa-truck mr-2 text-info"></i> Monitoring Kedatangan Item PR
                    </h5>
                    <div class="d-flex align-items-center" style="flex-wrap:wrap; gap:0.5rem; width:100%;">
                        <div class="input-group input-group-sm flex-fill" style="min-width:160px; max-width:280px;">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-right: none; color: #94a3b8;">
                                    <i class="fas fa-search" style="font-size: 0.75rem;"></i>
                                </span>
                            </div>
                            <input type="text" id="monitoringSearch" class="form-control form-control-sm"
                                placeholder="Cari item, PR, dept..."
                                style="background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-left: none; color: #f8fafc;"
                                oninput="filterMonitoringTable(this.value)">
                        </div>
                        @if(Auth::user()->hasAnyRole(['superadmin', 'procurement']))
                        <a href="{{ route('dashboard.export-ongoing') }}" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm flex-shrink-0">
                            <i class="fas fa-file-excel mr-1"></i> <span class="d-none d-sm-inline">Export Excel</span><span class="d-sm-none">Export</span>
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless text-sm mb-0 table-stack" id="monitoringTable">
                            <thead class="text-uppercase" style="opacity: 0.8; font-size: 0.75rem;">
                                <tr>
                                    <th class="pl-4 sortable-col" data-col="0" style="cursor:pointer; user-select:none;">Item Name <i class="fas fa-sort ml-1 sort-icon" style="opacity:0.4;"></i></th>
                                    <th class="sortable-col" data-col="1" style="cursor:pointer; user-select:none;">No. PR <i class="fas fa-sort ml-1 sort-icon" style="opacity:0.4;"></i></th>
                                    <th class="sortable-col" data-col="2" style="cursor:pointer; user-select:none;">Dept. <i class="fas fa-sort ml-1 sort-icon" style="opacity:0.4;"></i></th>
                                    <th class="sortable-col" data-col="3" style="cursor:pointer; user-select:none;">Tgl. Request <i class="fas fa-sort ml-1 sort-icon" style="opacity:0.4;"></i></th>
                                    <th class="sortable-col" data-col="4" style="cursor:pointer; user-select:none;">Rencana Tiba <i class="fas fa-sort ml-1 sort-icon" style="opacity:0.4;"></i></th>
                                    <th class="sortable-col" data-col="5" style="cursor:pointer; user-select:none;">Status <i class="fas fa-sort ml-1 sort-icon" style="opacity:0.4;"></i></th>
                                    <th>QTY Masuk</th>
                                    <th>Sisa (OTS)</th>
                                    <th class="pr-4">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ongoingItems as $item)
                                    @php
                                        $qtyIncoming = (float) $item->received_quantity;
                                        $ots = (float) $item->quantity - $qtyIncoming;
                                        $isCleared = $ots <= 0 && $qtyIncoming > 0;
                                        $prNumber = $item->purchaseRequest->pr_number ?? '-';
                                        $prDept = $item->purchaseRequest->department->code ?? '-';
                                        $activePlans = $item->deliveryPlans->where('is_active', true)->sortBy(fn($p) => $p->planned_date->timestamp)->values();
                                        $earliestPlan = $activePlans->first();
                                        $confirmSortVal = $earliestPlan ? $earliestPlan->planned_date->format('Y-m-d') : '9999-99-99';
                                    @endphp
                                    <tr style="{{ $isCleared ? 'background-color: rgba(16, 185, 129, 0.05);' : '' }}">
                                        <td data-label="Item" class="pl-4 font-weight-bold text-white">{{ $item->item_name }}</td>
                                        <td data-label="No. PR">
                                            <a href="{{ route('purchase-requests.show', $item->purchaseRequest) }}" class="text-primary-accent" style="text-decoration: none;">
                                                {{ $prNumber }}
                                            </a>
                                        </td>
                                        <td data-label="Dept.">{{ $prDept }}</td>
                                        <td data-label="Tgl. Request" data-sort="{{ $item->purchaseRequest->request_date->format('Y-m-d') }}">{{ $item->purchaseRequest->request_date->format('d M Y') }}</td>
                                        <td data-label="Rencana Tiba" data-sort="{{ $confirmSortVal }}">
                                            @if($activePlans->isNotEmpty())
                                                <div style="text-align:right;">
                                                @foreach($activePlans as $plan)
                                                    <div class="mb-1">
                                                        <span class="text-info" style="font-size:0.8rem;">{{ $plan->planned_date->format('d/m/Y') }}</span>
                                                        @if($plan->is_rescheduled)
                                                            <span class="badge badge-warning" style="font-size: 0.55rem; padding: 2px 4px;">R</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td data-label="Status">
                                            <span class="badge badge-{{ $item->status == 'pending' ? 'warning' : (str_contains($item->status, 'rejected') ? 'danger' : 'success') }}">
                                                {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                            </span>
                                        </td>
                                        <td data-label="QTY Masuk">
                                            @if($item->deliveries->isNotEmpty())
                                                <div style="text-align:right;">
                                                @foreach($item->deliveries as $delivery)
                                                    <div class="mb-1">
                                                        @if($delivery->received_quantity > 0)
                                                            <span class="text-success font-weight-bold" style="font-size:0.8rem;" title="Diterima">+{{ (float)$delivery->received_quantity }} {{ $item->uom }}</span>
                                                        @endif
                                                        @if($delivery->rejected_quantity > 0)
                                                            <span class="text-danger font-weight-bold" style="font-size:0.8rem;" title="Ditolak: {{ (float)$delivery->rejected_quantity }} {{ $item->uom }} (Alasan: {{ $delivery->rejection_reason }})">
                                                                -{{ (float)$delivery->rejected_quantity }} {{ $item->uom }} (Ditolak)
                                                            </span>
                                                        @endif
                                                        <span class="text-muted" style="font-size:0.72rem;"> — {{ $delivery->delivery_date->format('d/m/Y') }}</span>
                                                    </div>
                                                @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td data-label="Sisa (OTS)">
                                            @if($isCleared)
                                                <span class="badge badge-success"><i class="fas fa-check"></i> Selesai</span>
                                            @else
                                                <span class="text-warning font-weight-bold">{{ $ots }} {{ $item->uom }}</span>
                                            @endif
                                        </td>
                                        <td data-label="Keterangan">
                                            @if($item->deliveryPlans->isNotEmpty())
                                                @php
                                                    $cancelledPlans = $item->deliveryPlans->where('is_active', false);
                                                    $rescheduledPlans = $item->deliveryPlans->where('is_active', true)->where('is_rescheduled', true);
                                                    $displayText = "";
                                                    if ($cancelledPlans->isNotEmpty() && $rescheduledPlans->isNotEmpty()) {
                                                        $awal = $cancelledPlans->pluck('planned_date')->map->format('d/m/y')->implode(', ');
                                                        $baru = $rescheduledPlans->pluck('planned_date')->map->format('d/m/y')->implode(', ');
                                                        $displayText = "Reschedule ETA Awal {$awal} → {$baru}";
                                                    }
                                                    $activeNotes = $item->deliveryPlans->where('is_active', true)->pluck('notes')->filter()->implode(' | ');
                                                    if ($activeNotes) {
                                                        $displayText .= ($displayText ? " | " : "") . $activeNotes;
                                                    }
                                                @endphp
                                                @if($displayText)
                                                    <span class="text-muted" style="font-size: 0.75rem; white-space:normal;"><i class="fas fa-info-circle mr-1"></i>{{ $displayText }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="fas fa-box-open mb-3" style="font-size: 2rem; opacity: 0.5;"></i>
                                            <p class="mb-0">Tidak ada item yang sedang berjalan saat ini.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var sortState = { col: -1, asc: true };

        document.querySelectorAll('#monitoringTable .sortable-col').forEach(function(th) {
            th.addEventListener('click', function() {
                var col = parseInt(this.getAttribute('data-col'));
                if (sortState.col === col) {
                    sortState.asc = !sortState.asc;
                } else {
                    sortState.col = col;
                    sortState.asc = true;
                }

                // Update icons
                document.querySelectorAll('#monitoringTable .sortable-col .sort-icon').forEach(function(icon) {
                    icon.className = 'fas fa-sort ml-1 sort-icon';
                    icon.style.opacity = '0.4';
                });
                var activeIcon = this.querySelector('.sort-icon');
                activeIcon.className = 'fas fa-sort-' + (sortState.asc ? 'up' : 'down') + ' ml-1 sort-icon';
                activeIcon.style.opacity = '1';
                activeIcon.style.color = '#3b82f6';

                // Sort rows
                var tbody = document.querySelector('#monitoringTable tbody');
                var rows = Array.from(tbody.querySelectorAll('tr'));
                rows.sort(function(a, b) {
                    var aCell = a.cells[col];
                    var bCell = b.cells[col];
                    // Use data-sort attribute if available (for date columns)
                    var aVal = aCell ? (aCell.getAttribute('data-sort') || aCell.innerText) : '';
                    var bVal = bCell ? (bCell.getAttribute('data-sort') || bCell.innerText) : '';
                    aVal = aVal.trim().toLowerCase();
                    bVal = bVal.trim().toLowerCase();
                    if (aVal < bVal) return sortState.asc ? -1 : 1;
                    if (aVal > bVal) return sortState.asc ? 1 : -1;
                    return 0;
                });
                rows.forEach(function(row) { tbody.appendChild(row); });
            });
        });
    })();

    function filterMonitoringTable(query) {
        var q = query.trim().toLowerCase();
        var tbody = document.querySelector('#monitoringTable tbody');
        if (!tbody) return;
        var rows = tbody.querySelectorAll('tr');
        var visibleCount = 0;
        rows.forEach(function(row) {
            var text = row.innerText.toLowerCase();
            if (!q || text.indexOf(q) !== -1) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
    }
    </script>

    <!-- Recent Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 bg-transparent py-4 pb-2">
                    <h5 class="mb-0 font-weight-bold tracking-tight">Recent Purchase Requests</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                       <table class="table table-hover table-borderless text-sm mb-0 table-stack">
                            <thead>
                                <tr>
                                    <th class="pl-4">PR Number</th>
                                    <th>Date</th>
                                    <th>Requester</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th class="pr-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPRs as $pr)
                                <tr>
                                    <td data-label="PR No." class="pl-4 font-weight-medium text-white">{{ $pr->pr_number ?? 'Pending' }}</td>
                                    <td data-label="Date" class="text-muted">{{ $pr->created_at->format('d M Y') }}</td>
                                    <td data-label="Requester">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <div class="bg-primary-soft text-primary-accent rounded-circle d-flex align-items-center justify-content-center mr-2" style="width: 24px; height: 24px; font-weight: 600; font-size: 0.72rem; flex-shrink:0;">
                                                {{ substr($pr->user->name, 0, 1) }}
                                            </div>
                                            {{ $pr->user->name }}
                                        </div>
                                    </td>
                                    <td data-label="Dept.">{{ $pr->department->code }}</td>
                                    <td data-label="Status">
                                        @php
                                            $statusLabel = $pr->approval_status;
                                            $badgeClass = match($statusLabel) {
                                                'Draft' => 'badge-secondary',
                                                'Pending' => 'badge-warning',
                                                'Revision Required' => 'badge-danger',
                                                'Partial / Revision' => 'badge-warning',
                                                'Processing' => 'bg-primary-soft text-primary-accent border border-primary-accent',
                                                'Approved (OM)' => 'bg-info-soft text-info-accent border border-info-accent',
                                                'Approved (GM)' => 'bg-info-soft text-info-accent border border-info-accent',
                                                'Approved (Proc)' => 'bg-info-soft text-info-accent border border-info-accent',
                                                'Ordered' => 'bg-purple-soft text-purple-accent border border-purple-accent',
                                                'Delivered' => 'bg-success-soft text-success-accent',
                                                'Completed' => 'badge-success',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}" style="{{ str_contains($badgeClass, 'border') ? 'border-width: 1px !important;' : '' }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="td-actions">
                                        <button type="button"
                                                class="btn btn-sm btn-info btn-xs"
                                                data-toggle="collapse"
                                                data-target="#dash-pr-details-{{ $pr->id }}"
                                                aria-expanded="false"
                                                title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        @if($pr->isEditable() && (auth()->id() == $pr->user_id || auth()->user()->hasRole('superadmin')))
                                        <a href="{{ route('purchase-requests.edit', $pr) }}" class="btn btn-warning btn-xs" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
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
                                {{-- Expanded accordion row --}}
                                <tr class="tr-expand">
                                    <td colspan="6" class="p-0" style="border: none;">
                                        <div id="dash-pr-details-{{ $pr->id }}" class="collapse">
                                            <div class="detail-panel-inner rounded shadow border-0" style="background-color: rgba(0,0,0,0.18); margin: 0.5rem 0;">
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
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="far fa-folder-open mb-3" style="font-size: 2.5rem; opacity: 0.5;"></i>
                                        <p class="mb-0 font-weight-medium">No recent records found.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(count($recentPRs) > 0)
                <div class="card-footer bg-transparent border-0 text-center py-3">
                    <a href="{{ route('purchase-requests.index') }}" class="text-sm font-weight-medium text-primary-accent" style="text-decoration: none;">View Full List <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart Defaults for Modern Dark Mode
            Chart.defaults.color = '#94a3b8';
            Chart.defaults.font.family = "'Inter', sans-serif";
            
            // Status Distribution Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($chartData['status_distribution']['labels']) !!},
                    datasets: [{
                        data: {!! json_encode($chartData['status_distribution']['data']) !!},
                        backgroundColor: ['#64748b', '#f59e0b', '#ef4444', '#06b6d4', '#10b981'],
                        borderWidth: 2,
                        borderColor: '#1e293b', // Match card background
                        hoverOffset: 4
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 12,
                            boxPadding: 6,
                            usePointStyle: true,
                        }
                    }
                }
            });

            // var gradient = trendCtx.createLinearGradient(0, 0, 0, 400); ...
            // Wait we need canvas reference before creating gradient
            const trendCanvas = document.getElementById('trendChart');
            const trendCtx = trendCanvas.getContext('2d');
            
            var gradient = trendCtx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');   
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

            // Monthly Trend Chart
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartData['monthly_trends']['labels']) !!},
                    datasets: [{
                        label: 'Purchase Requests',
                        data: {!! json_encode($chartData['monthly_trends']['data']) !!},
                        borderColor: '#3b82f6', // Tailwind Blue 500
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4, // Smooth curve
                        pointBackgroundColor: '#1e293b',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#3b82f6',
                        pointHoverBorderColor: '#ffffff',
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false // Hide legend for single line
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' Requests';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.03)',
                                drawBorder: false
                            },
                            border: { display: false },
                            ticks: {
                                stepSize: 1,
                                padding: 10
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            border: { display: false },
                            ticks: {
                                padding: 10
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>
