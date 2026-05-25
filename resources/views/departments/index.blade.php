<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:0.5rem;">
            <h2 class="font-semibold text-xl leading-tight mb-0">
                {{ __('Departments') }}
            </h2>
            @can('create departments')
            <a href="{{ route('departments.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Department
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
                                <th>Code</th>
                                <th>Name</th>
                                <th>Manager</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departments as $dept)
                            <tr>
                                <td data-label="ID">{{ $dept->id }}</td>
                                <td data-label="Code"><strong>{{ $dept->code }}</strong></td>
                                <td data-label="Name">{{ $dept->name }}</td>
                                <td data-label="Manager">{{ $dept->manager ?? '-' }}</td>
                                <td data-label="Status">
                                    @if($dept->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="td-actions">
                                    @can('edit departments')
                                    <a href="{{ route('departments.edit', $dept) }}" class="btn btn-warning btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('delete departments')
                                    <form action="{{ route('departments.destroy', $dept) }}" method="POST" class="d-inline form-confirm" data-message="Are you sure? This might affect associated users and PRs.">
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
                    {{ $departments->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
