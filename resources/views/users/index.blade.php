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
        <div class="card shadow-sm rounded-lg">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-stack">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Employee ID</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td data-label="ID">{{ $user->id }}</td>
                                <td data-label="Name"><strong>{{ $user->name }}</strong></td>
                                <td data-label="Email">{{ $user->email }}</td>
                                <td data-label="Employee ID">{{ $user->employee_id ?? '-' }}</td>
                                <td data-label="Department">{{ $user->department->name ?? '-' }}</td>
                                <td data-label="Role">
                                    @foreach($user->roles as $role)
                                        <span class="badge badge-info">{{ ucfirst(str_replace('_',' ',$role->name)) }}</span>
                                    @endforeach
                                </td>
                                <td data-label="Status">
                                    <span class="badge badge-success">Active</span>
                                </td>
                                <td class="td-actions">
                                    @can('edit users')
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('delete users')
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline form-confirm" data-message="Are you sure?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-xs">
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
                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
