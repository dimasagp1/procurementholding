<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:0.5rem;">
            <h2 class="font-semibold text-xl leading-tight mb-0">
                {{ __('User Management') }}
            </h2>
            @can('create users')
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New User
            </a>
            @endcan
        </div>
    </x-slot>

    <div>

        {{-- ============================================================
             SECTION: HOLDING / SUPERADMIN
             ============================================================ --}}
        <div class="card shadow-sm rounded-lg mb-4">
            <div class="card-header d-flex align-items-center" style="background: linear-gradient(90deg, rgba(99,102,241,0.15), transparent); border-bottom: 1px solid rgba(99,102,241,0.25);">
                <i class="fas fa-crown text-warning mr-2"></i>
                <strong class="text-white">Holding / Superadmin</strong>
                <span class="badge badge-warning ml-2">{{ $holdingUsers->count() }} user</span>
            </div>
            <div class="card-body p-2">
                @if($holdingUsers->isEmpty())
                    <p class="text-muted text-center small py-3 mb-0">Tidak ada user dengan role holding.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-stack mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Employee ID</th>
                                <th>Company</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th title="Tanda Tangan">TTD</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($holdingUsers as $user)
                            <tr>
                                <td data-label="Name"><strong>{{ $user->name }}</strong></td>
                                <td data-label="Email"><small>{{ $user->email }}</small></td>
                                <td data-label="Employee ID">{{ $user->employee_id ?? '-' }}</td>
                                <td data-label="Company"><small class="text-muted">{{ $user->company->name ?? '–' }}</small></td>
                                <td data-label="Department"><small>{{ $user->department->name ?? '–' }}</small></td>
                                <td data-label="Role">
                                    @foreach($user->roles as $role)
                                        <span class="badge badge-warning">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                                    @endforeach
                                </td>
                                <td data-label="TTD" class="text-center">
                                    @if($user->signature_path)
                                        <span class="text-success" title="Tanda tangan tersedia"><i class="fas fa-check-circle"></i></span>
                                    @else
                                        <span class="text-danger" title="Belum upload tanda tangan"><i class="fas fa-times-circle"></i></span>
                                    @endif
                                </td>
                                <td class="td-actions">
                                    @can('edit users')
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-xs" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('delete users')
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline form-confirm" data-message="Hapus user ini?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-xs" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- ============================================================
             SECTION: PER COMPANY
             ============================================================ --}}
        @foreach($companies as $company)
        @php $companyUsers = $company->users; @endphp
        <div class="card shadow-sm rounded-lg mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="background: linear-gradient(90deg, rgba(16,185,129,0.10), transparent); border-bottom: 1px solid rgba(16,185,129,0.20);">
                <div class="d-flex align-items-center" style="gap:0.5rem;">
                    <i class="fas fa-building text-success mr-1"></i>
                    <strong class="text-white">{{ $company->name }}</strong>
                    <span class="badge badge-secondary ml-1">{{ $company->code }}</span>
                    @if($company->connect_odoo)
                        <span class="badge badge-warning" title="Odoo connected"><i class="bi bi-plug-fill"></i></span>
                    @endif
                    @if($company->connect_finance)
                        <span class="badge badge-success" title="Finance connected"><i class="bi bi-bank2"></i></span>
                    @endif
                </div>
                <span class="badge badge-info">{{ $companyUsers->count() }} user</span>
            </div>
            <div class="card-body p-2">
                @if($companyUsers->isEmpty())
                    <p class="text-muted text-center small py-3 mb-0">Belum ada user di perusahaan ini.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-stack mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Employee ID</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th title="Tanda Tangan">TTD</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($companyUsers as $user)
                            <tr>
                                <td data-label="Name"><strong>{{ $user->name }}</strong></td>
                                <td data-label="Email"><small>{{ $user->email }}</small></td>
                                <td data-label="Employee ID">{{ $user->employee_id ?? '-' }}</td>
                                <td data-label="Department"><small>{{ $user->department->name ?? '–' }}</small></td>
                                <td data-label="Role">
                                    @foreach($user->roles as $role)
                                        <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                                    @endforeach
                                </td>
                                <td data-label="TTD" class="text-center">
                                    @if($user->signature_path)
                                        <span class="text-success" title="Tanda tangan tersedia"><i class="fas fa-check-circle"></i></span>
                                    @else
                                        <span class="text-danger" title="Belum upload tanda tangan"><i class="fas fa-times-circle"></i></span>
                                    @endif
                                </td>
                                <td class="td-actions">
                                    @can('edit users')
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-xs" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('delete users')
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline form-confirm" data-message="Hapus user ini?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-xs" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
        @endforeach

    </div>
</x-app-layout>
