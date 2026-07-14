<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Company Integration Settings') }} - {{ $company->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('settings.my-company.update') }}">
                        @csrf
                        @method('PUT')

                        {{-- Integration Flags --}}
                        <h5 class="text-secondary mb-3">Integration Settings</h5>
                        <div class="row mb-2">
                            <div class="col-md-6 mb-2">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="connect_odoo" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" value="1"
                                        id="connect_odoo" name="connect_odoo"
                                        {{ old('connect_odoo', $company->connect_odoo) ? 'checked' : '' }}
                                        onchange="toggleSection('odoo-section', this.checked)">
                                    <label class="form-check-label fw-semibold text-warning" for="connect_odoo">
                                        <i class="bi bi-plug-fill me-1"></i> Connect to Odoo API
                                    </label>
                                </div>
                                <small class="text-muted">Aktifkan untuk sinkronisasi vendor dan PO ke Odoo.</small>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="connect_finance" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" value="1"
                                        id="connect_finance" name="connect_finance"
                                        {{ old('connect_finance', $company->connect_finance) ? 'checked' : '' }}
                                        onchange="toggleSection('finance-section', this.checked)">
                                    <label class="form-check-label fw-semibold text-success" for="connect_finance">
                                        <i class="bi bi-bank2 me-1"></i> Connect to Finance API
                                    </label>
                                </div>
                                <small class="text-muted">Aktifkan untuk sinkronisasi expense ke Finance.</small>
                            </div>
                        </div>

                        {{-- Odoo API Credentials --}}
                        <div id="odoo-section" style="{{ old('connect_odoo', $company->connect_odoo) ? '' : 'display:none;' }}" class="mt-3">
                            <hr>
                            <h5 class="text-warning mb-3 mt-2">Odoo API Configuration</h5>
                            <p class="text-muted small mb-3">Configure unique Odoo credentials for your company.</p>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="odoo_url" class="form-label">Odoo Connection URL</label>
                                    <input type="url" class="form-control @error('odoo_url') is-invalid @enderror" id="odoo_url" name="odoo_url" value="{{ old('odoo_url', $company->odoo_url) }}" placeholder="https://odoo.example.com">
                                    @error('odoo_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="odoo_db" class="form-label">Odoo DB Name</label>
                                    <input type="text" class="form-control @error('odoo_db') is-invalid @enderror" id="odoo_db" name="odoo_db" value="{{ old('odoo_db', $company->odoo_db) }}" placeholder="db_herbatech">
                                    @error('odoo_db') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="odoo_username" class="form-label">Odoo Username/Email</label>
                                    <input type="text" class="form-control @error('odoo_username') is-invalid @enderror" id="odoo_username" name="odoo_username" value="{{ old('odoo_username', $company->odoo_username) }}" placeholder="admin">
                                    @error('odoo_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="odoo_password" class="form-label">Odoo Password/API Key</label>
                                    <input type="password" class="form-control @error('odoo_password') is-invalid @enderror" id="odoo_password" name="odoo_password" placeholder="••••••••">
                                    <small class="text-muted">Leave blank to keep existing password.</small>
                                    @error('odoo_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="odoo_company_id" class="form-label">Odoo Company ID</label>
                                    <input type="number" class="form-control @error('odoo_company_id') is-invalid @enderror" id="odoo_company_id" name="odoo_company_id" value="{{ old('odoo_company_id', $company->odoo_company_id) }}" placeholder="1">
                                    @error('odoo_company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 mt-2">
                                    <button type="button" class="btn btn-outline-warning btn-sm" id="btn-test-odoo">
                                        <i class="fas fa-microchip mr-1"></i> Test Odoo Connection
                                    </button>
                                    <span id="test-odoo-result" class="ml-2 small"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Finance API Credentials --}}
                        <div id="finance-section" style="{{ old('connect_finance', $company->connect_finance) ? '' : 'display:none;' }}" class="mt-3">
                            <hr>
                            <h5 class="text-success mb-3 mt-2">Finance API Configuration</h5>
                            <p class="text-muted small mb-3">Configure unique Finance API parameters for your company.</p>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="finance_api_url" class="form-label">Finance API Base URL</label>
                                    <input type="url" class="form-control @error('finance_api_url') is-invalid @enderror" id="finance_api_url" name="finance_api_url" value="{{ old('finance_api_url', $company->finance_api_url) }}" placeholder="https://finance.example.com/api">
                                    @error('finance_api_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="finance_api_key" class="form-label">Finance API Token / Secret</label>
                                    <input type="text" class="form-control @error('finance_api_key') is-invalid @enderror" id="finance_api_key" name="finance_api_key" value="{{ old('finance_api_key', $company->finance_api_key) }}" placeholder="api_key_xxxxxxxx">
                                    @error('finance_api_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 mt-2">
                                    <button type="button" class="btn btn-outline-success btn-sm" id="btn-test-finance">
                                        <i class="fas fa-university mr-1"></i> Test Finance Connection
                                    </button>
                                    <span id="test-finance-result" class="ml-2 small"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleSection(sectionId, show) {
            const el = document.getElementById(sectionId);
            if (el) {
                el.style.display = show ? '' : 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const btnTestOdoo = document.getElementById('btn-test-odoo');
            if (btnTestOdoo) {
                btnTestOdoo.addEventListener('click', function() {
                    const url = document.getElementById('odoo_url').value;
                    const db = document.getElementById('odoo_db').value;
                    const username = document.getElementById('odoo_username').value;
                    const password = document.getElementById('odoo_password').value;
                    const resultSpan = document.getElementById('test-odoo-result');

                    resultSpan.className = 'ml-2 small text-info';
                    resultSpan.innerHTML = '<div class="spinner-border spinner-border-sm text-info mr-1" role="status" style="width: 1rem; height: 1rem;"></div> Testing...';
                    btnTestOdoo.disabled = true;

                    fetch("{{ route('companies.test-odoo') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ odoo_url: url, odoo_db: db, odoo_username: username, odoo_password: password })
                    })
                    .then(res => res.json())
                    .then(data => {
                        btnTestOdoo.disabled = false;
                        if (data.success) {
                            resultSpan.className = 'ml-2 small text-success font-weight-bold';
                            resultSpan.innerHTML = `<i class="fas fa-check-circle mr-1"></i> ${data.message} (${data.latency_ms}ms)`;
                        } else {
                            resultSpan.className = 'ml-2 small text-danger';
                            resultSpan.innerHTML = `<i class="fas fa-times-circle mr-1"></i> ${data.message}`;
                        }
                    })
                    .catch(err => {
                        btnTestOdoo.disabled = false;
                        resultSpan.className = 'ml-2 small text-danger';
                        resultSpan.innerHTML = `<i class="fas fa-times-circle mr-1"></i> Error: ${err.message}`;
                    });
                });
            }

            const btnTestFinance = document.getElementById('btn-test-finance');
            if (btnTestFinance) {
                btnTestFinance.addEventListener('click', function() {
                    const url = document.getElementById('finance_api_url').value;
                    const key = document.getElementById('finance_api_key').value;
                    const resultSpan = document.getElementById('test-finance-result');

                    resultSpan.className = 'ml-2 small text-info';
                    resultSpan.innerHTML = '<div class="spinner-border spinner-border-sm text-info mr-1" role="status" style="width: 1rem; height: 1rem;"></div> Testing...';
                    btnTestFinance.disabled = true;

                    fetch("{{ route('companies.test-finance') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ finance_api_url: url, finance_api_key: key })
                    })
                    .then(res => res.json())
                    .then(data => {
                        btnTestFinance.disabled = false;
                        if (data.success) {
                            resultSpan.className = 'ml-2 small text-success font-weight-bold';
                            resultSpan.innerHTML = `<i class="fas fa-check-circle mr-1"></i> ${data.message} (${data.latency_ms}ms)`;
                        } else {
                            resultSpan.className = 'ml-2 small text-danger';
                            resultSpan.innerHTML = `<i class="fas fa-times-circle mr-1"></i> ${data.message}`;
                        }
                    })
                    .catch(err => {
                        btnTestFinance.disabled = false;
                        resultSpan.className = 'ml-2 small text-danger';
                        resultSpan.innerHTML = `<i class="fas fa-times-circle mr-1"></i> Error: ${err.message}`;
                    });
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
