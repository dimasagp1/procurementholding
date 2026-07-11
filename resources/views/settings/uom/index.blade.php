<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:0.5rem;">
            <h2 class="font-semibold text-xl leading-tight mb-0">
                {{ __('UOM Management') }} - {{ $company ? $company->name : 'All Companies' }}
            </h2>
            @if($company)
                <a href="{{ route('uoms.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add UOM
                </a>
            @else
                <span class="badge badge-warning text-sm p-2"><i class="fas fa-exclamation-triangle mr-1"></i> Switch company to add UOM</span>
            @endif
        </div>
    </x-slot>

    <div>
        <div class="card shadow-sm rounded-lg">
            <div class="card-body p-3">
                <form action="{{ route('uoms.index') }}" method="GET" class="row mb-3 filter-row">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Search UOM name/description..." value="{{ request('search') }}">
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
                                <th>Description</th>
                                @if(!$company)
                                    <th>Company</th>
                                @endif
                                <th style="width: 150px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($uoms as $uom)
                            <tr>
                                <td data-label="#">{{ ($uoms->currentPage() - 1) * $uoms->perPage() + $loop->iteration }}</td>
                                <td data-label="Name"><strong>{{ $uom->name }}</strong></td>
                                <td data-label="Description">{{ $uom->description }}</td>
                                @if(!$company)
                                    <td data-label="Company"><span class="badge badge-secondary">{{ $uom->company ? $uom->company->name : 'Global/None' }}</span></td>
                                @endif
                                <td class="td-actions">
                                    <a href="{{ route('uoms.edit', $uom) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('uoms.destroy', $uom) }}" method="POST" class="d-inline form-confirm" data-message="Are you sure you want to delete this UOM?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ !$company ? 5 : 4 }}" class="text-center">No UOM found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $uoms->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
