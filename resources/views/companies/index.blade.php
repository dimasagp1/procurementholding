<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:0.5rem;">
            <h2 class="font-semibold text-xl leading-tight mb-0">
                {{ __('Companies') }}
            </h2>
            <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Company
            </a>
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
                                <th>Integrasi</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($companies as $company)
                            <tr>
                                <td data-label="ID">{{ $company->id }}</td>
                                <td data-label="Code"><strong>{{ $company->code }}</strong></td>
                                <td data-label="Name">{{ $company->name }}</td>
                                <td data-label="Integrasi">
                                    @if($company->connect_odoo)
                                        <span class="badge badge-warning" title="Odoo API connected"><i class="bi bi-plug-fill"></i> Odoo</span>
                                    @endif
                                    @if($company->connect_finance)
                                        <span class="badge badge-success" title="Finance API connected"><i class="bi bi-bank2"></i> Finance</span>
                                    @endif
                                    @if(!$company->connect_odoo && !$company->connect_finance)
                                        <span class="text-muted small">–</span>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    @if($company->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="td-actions">
                                    @if($company->connect_finance)
                                    <a href="{{ route('companies.budget', $company) }}" class="btn btn-success btn-xs" title="Manage Finance Budget">
                                        <i class="fas fa-coins"></i>
                                    </a>
                                    @endif
                                    @if($company->connect_odoo)
                                    <a href="{{ route('companies.vendors', $company) }}" class="btn btn-info btn-xs" title="Manage Odoo Vendors">
                                        <i class="fas fa-address-book"></i>
                                    </a>
                                    @endif
                                    <a href="{{ route('companies.edit', $company) }}" class="btn btn-warning btn-xs" title="Edit Company">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form action="{{ route('companies.destroy', $company) }}" method="POST" class="d-inline form-confirm" data-message="Are you sure you want to delete this company?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-xs" title="Delete Company">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $companies->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
