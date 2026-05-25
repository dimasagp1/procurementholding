<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:0.5rem;">
            <h2 class="font-semibold text-xl leading-tight mb-0">
                {{ __('Master Item Management') }}
            </h2>
            <a href="{{ route('master-items.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Add Item
            </a>
        </div>
    </x-slot>

    <div>
        <div class="card shadow-sm rounded-lg mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Bulk Import Data</h5>
                <form action="{{ route('master-items.import') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center flex-wrap" style="gap: 1rem;">
                    @csrf
                    <div>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted">Format: Excel (.xlsx/.xls) atau CSV. Pastikan kolom pertama adalah <strong>name</strong>.</small>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload"></i> Import Data
                        </button>
                        <a href="{{ route('master-items.template') }}" class="btn btn-outline-info">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm rounded-lg">
            <div class="card-body p-3">
                <form action="{{ route('master-items.index') }}" method="GET" class="row mb-3 filter-row">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search item name..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">Search</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-stack">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th>Name</th>
                                <th style="width: 150px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                            <tr>
                                <td data-label="#">{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>
                                <td data-label="Name"><strong>{{ $item->name }}</strong></td>
                                <td class="td-actions">
                                    <a href="{{ route('master-items.edit', $item) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('master-items.destroy', $item) }}" method="POST" class="d-inline form-confirm" data-message="Are you sure you want to delete this Item?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center">No Master Item found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
