<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reports') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Statistical Summary -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-info" style="border-radius: 15px; overflow: hidden; position: relative;">
                        <div class="card-body p-4 text-white">
                            <h3 class="display-4 font-weight-bold mb-0" style="font-size: 2.5rem; z-index: 2; position: relative;">{{ $stats['total_pr'] }}</h3>
                            <p class="mb-0 mt-2 text-white-50 font-weight-bold" style="letter-spacing: 1px; z-index: 2; position: relative; text-transform: uppercase; font-size: 0.85rem;">Total PR</p>
                        </div>
                        <i class="fas fa-file-invoice" style="position: absolute; right: -20px; bottom: -20px; font-size: 100px; color: rgba(255,255,255,0.15); z-index: 1;"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-warning" style="border-radius: 15px; overflow: hidden; position: relative;">
                        <div class="card-body p-4 text-white">
                            <h3 class="display-4 font-weight-bold mb-0" style="font-size: 2.5rem; z-index: 2; position: relative; color: white;">{{ $stats['pending_pr'] }}</h3>
                            <p class="mb-0 mt-2 text-white-50 font-weight-bold" style="letter-spacing: 1px; z-index: 2; position: relative; text-transform: uppercase; font-size: 0.85rem; color: rgba(255,255,255,0.7) !important;">Pending Review</p>
                        </div>
                        <i class="fas fa-clock" style="position: absolute; right: -20px; bottom: -20px; font-size: 100px; color: rgba(255,255,255,0.2); z-index: 1;"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-primary" style="border-radius: 15px; overflow: hidden; position: relative;">
                        <div class="card-body p-4 text-white">
                            <h3 class="display-4 font-weight-bold mb-0" style="font-size: 2.5rem; z-index: 2; position: relative;">{{ $stats['approved_pr'] }}</h3>
                            <p class="mb-0 mt-2 text-white-50 font-weight-bold" style="letter-spacing: 1px; z-index: 2; position: relative; text-transform: uppercase; font-size: 0.85rem;">Approved/Processing</p>
                        </div>
                        <i class="fas fa-check-circle" style="position: absolute; right: -20px; bottom: -20px; font-size: 100px; color: rgba(255,255,255,0.15); z-index: 1;"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-success" style="border-radius: 15px; overflow: hidden; position: relative;">
                        <div class="card-body p-4 text-white">
                            <h3 class="display-4 font-weight-bold mb-0" style="font-size: 2.5rem; z-index: 2; position: relative;">{{ $stats['completed_pr'] }}</h3>
                            <p class="mb-0 mt-2 text-white-50 font-weight-bold" style="letter-spacing: 1px; z-index: 2; position: relative; text-transform: uppercase; font-size: 0.85rem;">Completed</p>
                        </div>
                        <i class="fas fa-flag-checkered" style="position: absolute; right: -20px; bottom: -20px; font-size: 100px; color: rgba(255,255,255,0.15); z-index: 1;"></i>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <h3 class="text-lg font-medium mb-4"><i class="fas fa-file-excel mr-2 text-success"></i> Export Purchase Request Data</h3>
                    
                    <form id="filterForm" action="{{ route('reports.index') }}" method="GET">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved_om" {{ request('status') == 'approved_om' ? 'selected' : '' }}>Approved by OM</option>
                                    <option value="approved_gm" {{ request('status') == 'approved_gm' ? 'selected' : '' }}>Approved by GM</option>
                                    <option value="approved_proc" {{ request('status') == 'approved_proc' ? 'selected' : '' }}>Approved by Procurement</option>
                                    <option value="ordered" {{ request('status') == 'ordered' ? 'selected' : '' }}>Ordered</option>
                                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-3 mb-3 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-filter mr-1"></i> Filter
                                </button>
                                <button type="submit" formaction="{{ route('reports.export') }}" class="btn btn-success flex-fill">
                                    <i class="fas fa-file-excel mr-1"></i> Export
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm rounded-lg mt-4">
                <div class="card-body p-4">
                    <h3 class="text-lg font-medium mb-4"><i class="fas fa-list mr-2 text-info"></i> Report Data List</h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless">
                            <thead>
                                <tr>
                                    <th>PR Number</th>
                                    <th>Date</th>
                                    <th>Requester</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($prs as $pr)
                                <tr>
                                    <td>{{ $pr->pr_number }}</td>
                                    <td>{{ $pr->request_date?->format('d M Y') ?? 'N/A' }}</td>
                                    <td>{{ $pr->user->name }}</td>
                                    <td>{{ $pr->department->code }}</td>
                                    <td>
                                        @php
                                            $statusLabel = $pr->approval_status;
                                            $badgeClass = match($statusLabel) {
                                                'Draft' => 'badge-secondary',
                                                'Pending' => 'badge-warning',
                                                'Revision Required' => 'badge-danger',
                                                'Approved (OM)', 'Approved (GM)', 'Approved (Proc)' => 'badge-info',
                                                'Ordered' => 'badge-primary',
                                                'Delivered' => 'badge-teal',
                                                'Completed' => 'badge-success',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('purchase-requests.show', $pr) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No data found matching the filters.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
