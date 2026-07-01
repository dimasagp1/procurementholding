<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('dashboard') }}" class="brand-link">
        @if(isset($appLogo) && $appLogo)
            <img src="{{ asset('storage/' . $appLogo) }}" alt="Logo" class="brand-image rounded-lg elevation-5" style="opacity: 1; height: 40px; width: 40px; margin-top: -5px; max-height: none;">
        @else
            <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="Logo" class="brand-image rounded-lg elevation-5" style="opacity: 1; height: 40px; width: 40px; margin-top: -5px; max-height: none;">
        @endif
        <span class="brand-text font-weight-bold" style="font-size: 1.4rem; letter-spacing: 1.5px; text-transform: uppercase;">{{ $appName ?? 'PR System' }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image pt-1">
                <i class="fas fa-user-circle fa-2x text-light"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block">{{ Auth::user()->name }}</a>
                <small class="text-success">{{ ucfirst(str_replace('_', ' ', Auth::user()->getRoleNames()->first())) }}</small>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                @can('create pr')
                <li class="nav-item">
                    <a href="{{ route('purchase-requests.create') }}" class="nav-link {{ request()->routeIs('purchase-requests.create') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        <p>Create PR</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('purchase-requests.drafts') }}" class="nav-link {{ request()->routeIs('purchase-requests.drafts') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Draft PRs</p>
                    </a>
                </li>
                @endcan

                @can('view pr')
                @if(Auth::user()->hasAnyRole(['operational_manager', 'manager_fat', 'general_manager', 'superadmin']))
                <li class="nav-item">
                    <a href="{{ route('purchase-requests.approvals') }}" class="nav-link {{ request()->routeIs('purchase-requests.approvals') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-check"></i>
                        <p>Approval Queue</p>
                    </a>
                </li>
                @endif

                <li class="nav-item">
                    <a href="{{ route('purchase-requests.index') }}" class="nav-link {{ request()->routeIs('purchase-requests.index') && !request()->routeIs('purchase-requests.rejected') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-shopping-cart"></i>
                        <p>Purchase Request</p>
                    </a>
                </li>
                
                @php
                    $rejectedCount = \App\Models\PurchaseRequest::where('user_id', Auth::id())
                        ->whereHas('items', function($q) {
                            $q->whereIn('status', ['rejected_om', 'rejected_gm', 'rejected_proc']);
                        })->count();

                    // Calculate unresolved retur count
                    $returQuery = \App\Models\PrItemDelivery::where('rejected_quantity', '>', 0)
                        ->whereNull('retur_for_delivery_id');
                    
                    if (!Auth::user()->hasAnyRole(['superadmin', 'procurement', 'procurement_holding'])) {
                        $returQuery->whereHas('prItem.purchaseRequest', function($q) {
                            $q->where('user_id', Auth::id());
                        });
                    }

                    $unresolvedReturCount = $returQuery->where(function($q) {
                        $q->whereRaw('rejected_quantity > (SELECT COALESCE(SUM(children.received_quantity), 0) FROM pr_item_deliveries AS children WHERE children.retur_for_delivery_id = pr_item_deliveries.id)');
                    })->count();
                @endphp
                <li class="nav-item">
                    <a href="{{ route('purchase-requests.rejected') }}" class="nav-link {{ request()->routeIs('purchase-requests.rejected') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-exclamation-circle text-danger"></i>
                        <p>
                            Needs Revision
                            @if($rejectedCount > 0)
                                <span class="badge badge-danger right">{{ $rejectedCount }}</span>
                            @endif
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('purchase-requests.deliveries.rejected') }}" class="nav-link {{ request()->routeIs('purchase-requests.deliveries.rejected') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-undo text-warning"></i>
                        <p>
                            Kedatangan Ditolak
                            @if($unresolvedReturCount > 0)
                                <span class="badge badge-warning right">{{ $unresolvedReturCount }}</span>
                            @endif
                        </p>
                    </a>
                </li>
                @endcan

                @can('manage users')
                <li class="nav-item {{ request()->routeIs('users.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>
                            User Management
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>All Users</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('users.create') }}" class="nav-link {{ request()->routeIs('users.create') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Add User</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endcan

                @can('manage departments')
                <li class="nav-item {{ request()->routeIs('departments.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-building"></i>
                        <p>
                            Departments
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('departments.index') }}" class="nav-link {{ request()->routeIs('departments.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>All Departments</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('departments.create') }}" class="nav-link {{ request()->routeIs('departments.create') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Add Department</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endcan

                @can('view reports')
                <li class="nav-item">
                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Reports</p>
                    </a>
                </li>
                @endcan

                <li class="nav-item">
                    <a href="{{ route('settings.finance-budget') }}" class="nav-link {{ request()->routeIs('settings.finance-budget*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-coins text-success"></i>
                        <p>Finance Budget</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('staging-pagu.index') }}" class="nav-link {{ request()->routeIs('staging-pagu.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-boxes text-warning"></i>
                        <p>
                            Staging Pengeluaran
                            @php
                                try {
                                    $pendingStaging = \Illuminate\Support\Facades\DB::connection('fat_db')
                                        ->table('expense_stagings')
                                        ->where('status', 'pending')
                                        ->count();
                                } catch (\Exception $e) {
                                    $pendingStaging = 0;
                                }
                            @endphp
                            @if($pendingStaging > 0)
                                <span class="badge badge-warning right">{{ $pendingStaging }}</span>
                            @endif
                        </p>
                    </a>
                </li>


                @if(Auth::user()->hasRole('superadmin'))
                <li class="nav-header">SYSTEM</li>
                <li class="nav-item {{ request()->routeIs('settings.*') || request()->routeIs('uoms.*') || request()->routeIs('master-items.*') || request()->routeIs('purposes.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('settings.*') || request()->routeIs('uoms.*') || request()->routeIs('master-items.*') || request()->routeIs('purposes.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                            Settings
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('settings.general') }}" class="nav-link {{ request()->routeIs('settings.general') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>General Settings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('uoms.index') }}" class="nav-link {{ request()->routeIs('uoms.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>UOM Management</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('purposes.index') }}" class="nav-link {{ request()->routeIs('purposes.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Purpose Management</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('master-items.index') }}" class="nav-link {{ request()->routeIs('master-items.index') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Master Item</p>
                            </a>
                        </li>
                    </ul>

                </li>
                @endif

                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}" id="logout-form">
                        @csrf
                        <a href="#" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </a>
                    </form>
                </li>
            </ul>
        </nav>
    </div>
</aside>