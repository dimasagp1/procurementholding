<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Company') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('companies.store') }}">
                        @csrf

                        {{-- General Information --}}
                        <h5 class="text-primary mb-3">General Information</h5>
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">Company Code</label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required maxlength="10" placeholder="e.g. HERB">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Company Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. PT. Herbatech Innopharma Industry">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" {{ old('is_active', 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Integration Flags --}}
                        <h5 class="text-secondary mb-3 mt-4">Integration Settings</h5>
                        <div class="row mb-2">
                            <div class="col-md-6 mb-2">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="connect_odoo" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" value="1"
                                        id="connect_odoo" name="connect_odoo"
                                        {{ old('connect_odoo', 0) ? 'checked' : '' }}
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
                                        {{ old('connect_finance', 0) ? 'checked' : '' }}
                                        onchange="toggleSection('finance-section', this.checked)">
                                    <label class="form-check-label fw-semibold text-success" for="connect_finance">
                                        <i class="bi bi-bank2 me-1"></i> Connect to Finance API
                                    </label>
                                </div>
                                <small class="text-muted">Aktifkan untuk sinkronisasi expense ke Finance.</small>
                            </div>
                        </div>

                        {{-- Odoo API Credentials --}}
                        <div id="odoo-section" style="{{ old('connect_odoo', 0) ? '' : 'display:none;' }}" class="mt-3">
                            <hr>
                            <h5 class="text-warning mb-3 mt-2">Odoo API Configuration</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="odoo_url" class="form-label">Odoo Connection URL</label>
                                    <input type="url" class="form-control @error('odoo_url') is-invalid @enderror" id="odoo_url" name="odoo_url" value="{{ old('odoo_url') }}" placeholder="https://odoo.example.com">
                                    @error('odoo_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="odoo_db" class="form-label">Odoo DB Name</label>
                                    <input type="text" class="form-control @error('odoo_db') is-invalid @enderror" id="odoo_db" name="odoo_db" value="{{ old('odoo_db') }}" placeholder="db_herbatech">
                                    @error('odoo_db') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="odoo_username" class="form-label">Odoo Username/Email</label>
                                    <input type="text" class="form-control @error('odoo_username') is-invalid @enderror" id="odoo_username" name="odoo_username" value="{{ old('odoo_username') }}" placeholder="admin">
                                    @error('odoo_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="odoo_password" class="form-label">Odoo Password/API Key</label>
                                    <input type="password" class="form-control @error('odoo_password') is-invalid @enderror" id="odoo_password" name="odoo_password" placeholder="••••••••">
                                    @error('odoo_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="odoo_company_id" class="form-label">Odoo Company ID</label>
                                    <input type="number" class="form-control @error('odoo_company_id') is-invalid @enderror" id="odoo_company_id" name="odoo_company_id" value="{{ old('odoo_company_id') }}" placeholder="1">
                                    @error('odoo_company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 mt-2">
                                    <button type="button" class="btn btn-outline-warning btn-sm" id="btn-test-odoo">
                                        <i class="bi bi-cpu me-1"></i> Test Odoo Connection
                                    </button>
                                    <span id="test-odoo-result" class="ms-2 small"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Finance API Credentials --}}
                        <div id="finance-section" style="{{ old('connect_finance', 0) ? '' : 'display:none;' }}" class="mt-3">
                            <hr>
                            <h5 class="text-success mb-3 mt-2">Finance API Configuration</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="finance_api_url" class="form-label">Finance API Base URL</label>
                                    <input type="url" class="form-control @error('finance_api_url') is-invalid @enderror" id="finance_api_url" name="finance_api_url" value="{{ old('finance_api_url') }}" placeholder="https://finance.example.com/api">
                                    @error('finance_api_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="finance_api_key" class="form-label">Finance API Token / Secret</label>
                                    <input type="text" class="form-control @error('finance_api_key') is-invalid @enderror" id="finance_api_key" name="finance_api_key" value="{{ old('finance_api_key') }}" placeholder="api_key_xxxxxxxx">
                                    @error('finance_api_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 mt-2">
                                    <button type="button" class="btn btn-outline-success btn-sm" id="btn-test-finance">
                                        <i class="bi bi-bank2 me-1"></i> Test Finance Connection
                                    </button>
                                    <span id="test-finance-result" class="ms-2 small"></span>
                                </div>
                            </div>
                        </div>

                        {{-- ═══════════════════════════════════════════════════════ --}}
                        {{-- SECTION: Departments --}}
                        {{-- ═══════════════════════════════════════════════════════ --}}
                        <hr class="my-4">
                        <h5 class="text-info mb-1"><i class="bi bi-diagram-3-fill me-2"></i>Departments <small class="text-muted fw-normal">(opsional)</small></h5>
                        <p class="text-muted small mb-3">Tambahkan departments untuk perusahaan ini sekarang, atau bisa ditambahkan nanti.</p>

                        <div id="departments-container">
                            {{-- Baris department dari old() jika ada validasi error --}}
                            @if(old('departments'))
                                @foreach(old('departments') as $i => $dept)
                                <div class="row dept-row mb-2 align-items-center">
                                    <div class="col-md-2">
                                        <input type="text" class="form-control form-control-sm @error('departments.'.$i.'.code') is-invalid @enderror"
                                            name="departments[{{ $i }}][code]" placeholder="Kode" maxlength="10"
                                            value="{{ $dept['code'] ?? '' }}">
                                        @error('departments.'.$i.'.code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control form-control-sm @error('departments.'.$i.'.name') is-invalid @enderror"
                                            name="departments[{{ $i }}][name]" placeholder="Nama Department"
                                            value="{{ $dept['name'] ?? '' }}">
                                        @error('departments.'.$i.'.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control form-control-sm"
                                            name="departments[{{ $i }}][manager]" placeholder="Nama Manager (opsional)"
                                            value="{{ $dept['manager'] ?? '' }}">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-dept">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                {{-- 1 baris kosong default --}}
                                <div class="row dept-row mb-2 align-items-center">
                                    <div class="col-md-2">
                                        <input type="text" class="form-control form-control-sm" name="departments[0][code]" placeholder="Kode" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control form-control-sm" name="departments[0][name]" placeholder="Nama Department">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control form-control-sm" name="departments[0][manager]" placeholder="Nama Manager (opsional)">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-dept">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <button type="button" class="btn btn-outline-info btn-sm mt-1" id="btn-add-dept">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Department
                        </button>

                        {{-- ═══════════════════════════════════════════════════════ --}}
                        {{-- SECTION: Company Admin --}}
                        {{-- ═══════════════════════════════════════════════════════ --}}
                        <hr class="my-4">
                        <h5 class="text-warning mb-1"><i class="bi bi-person-gear me-2"></i>Company Admin <small class="text-muted fw-normal">(opsional)</small></h5>
                        <p class="text-muted small mb-3">Buat akun admin untuk mengelola users dan departemen di perusahaan ini. Bisa dilewati dan dibuat nanti.</p>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap Admin</label>
                                <input type="text" class="form-control @error('admin_name') is-invalid @enderror"
                                    name="admin_name" value="{{ old('admin_name') }}" placeholder="e.g. Andi Wijaya">
                                @error('admin_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Admin</label>
                                <input type="email" class="form-control @error('admin_email') is-invalid @enderror"
                                    name="admin_email" value="{{ old('admin_email') }}" placeholder="admin@perusahaan.com">
                                @error('admin_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee ID</label>
                                <input type="text" class="form-control @error('admin_employee_id') is-invalid @enderror"
                                    name="admin_employee_id" value="{{ old('admin_employee_id') }}" placeholder="ADM-001">
                                @error('admin_employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <small class="text-muted">(min 8 karakter)</small></label>
                                <input type="password" class="form-control @error('admin_password') is-invalid @enderror"
                                    name="admin_password" placeholder="••••••••">
                                @error('admin_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Posisi / Jabatan</label>
                                <input type="text" class="form-control"
                                    name="admin_position" value="{{ old('admin_position') }}" placeholder="Company Administrator">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" class="form-control"
                                    name="admin_phone" value="{{ old('admin_phone') }}" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-building-add me-1"></i> Create Company
                            </button>
                            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Cancel</a>
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

                    resultSpan.className = 'ms-2 small text-info';
                    resultSpan.innerHTML = '<div class="spinner-border spinner-border-sm text-info me-1" role="status" style="width: 1rem; height: 1rem;"></div> Testing...';
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
                            resultSpan.className = 'ms-2 small text-success fw-bold';
                            resultSpan.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> ${data.message} (${data.latency_ms}ms)`;
                        } else {
                            resultSpan.className = 'ms-2 small text-danger';
                            resultSpan.innerHTML = `<i class="bi bi-x-circle-fill me-1"></i> ${data.message}`;
                        }
                    })
                    .catch(err => {
                        btnTestOdoo.disabled = false;
                        resultSpan.className = 'ms-2 small text-danger';
                        resultSpan.innerHTML = `<i class="bi bi-x-circle-fill me-1"></i> Error: ${err.message}`;
                    });
                });
            }

            const btnTestFinance = document.getElementById('btn-test-finance');
            if (btnTestFinance) {
                btnTestFinance.addEventListener('click', function() {
                    const url = document.getElementById('finance_api_url').value;
                    const key = document.getElementById('finance_api_key').value;
                    const resultSpan = document.getElementById('test-finance-result');

                    resultSpan.className = 'ms-2 small text-info';
                    resultSpan.innerHTML = '<div class="spinner-border spinner-border-sm text-info me-1" role="status" style="width: 1rem; height: 1rem;"></div> Testing...';
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
                            resultSpan.className = 'ms-2 small text-success fw-bold';
                            resultSpan.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> ${data.message} (${data.latency_ms}ms)`;
                        } else {
                            resultSpan.className = 'ms-2 small text-danger';
                            resultSpan.innerHTML = `<i class="bi bi-x-circle-fill me-1"></i> ${data.message}`;
                        }
                    })
                    .catch(err => {
                        btnTestFinance.disabled = false;
                        resultSpan.className = 'ms-2 small text-danger';
                        resultSpan.innerHTML = `<i class="bi bi-x-circle-fill me-1"></i> Error: ${err.message}`;
                    });
                });
            }
            // ─── Dynamic Department Rows ───────────────────────────────────────
            let deptIndex = {{ old('departments') ? count(old('departments')) : 1 }};

            document.getElementById('btn-add-dept').addEventListener('click', function () {
                const container = document.getElementById('departments-container');
                const row = document.createElement('div');
                row.className = 'row dept-row mb-2 align-items-center';
                row.innerHTML = `
                    <div class="col-md-2">
                        <input type="text" class="form-control form-control-sm" name="departments[${deptIndex}][code]" placeholder="Kode" maxlength="10">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" name="departments[${deptIndex}][name]" placeholder="Nama Department">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" name="departments[${deptIndex}][manager]" placeholder="Nama Manager (opsional)">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-dept">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>`;
                container.appendChild(row);
                deptIndex++;
                bindRemoveButtons();
            });

            function bindRemoveButtons() {
                document.querySelectorAll('.btn-remove-dept').forEach(btn => {
                    btn.onclick = function () {
                        const rows = document.querySelectorAll('.dept-row');
                        if (rows.length > 1) {
                            this.closest('.dept-row').remove();
                        } else {
                            // Kosongkan saja jika tinggal 1 baris
                            this.closest('.dept-row').querySelectorAll('input').forEach(i => i.value = '');
                        }
                    };
                });
            }

            bindRemoveButtons();
        });
    </script>
    @endpush
</x-app-layout>

