<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="row">
                            {{-- Full Name --}}
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Email --}}
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Employee ID --}}
                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label">Employee ID</label>
                                <input type="text" class="form-control @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" value="{{ old('employee_id') }}" required>
                                @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Company --}}
                            <div class="col-md-6 mb-3">
                                <label for="company_id" class="form-label">Company</label>
                                @if($isCompanyLocked)
                                    {{-- Non-holding user: company dikunci --}}
                                    <input type="text" class="form-control" value="{{ $companies->first()->name ?? '-' }} ({{ $companies->first()->code ?? '' }})" readonly disabled>
                                    <input type="hidden" name="company_id" value="{{ $lockedCompanyId }}">
                                    <small class="text-muted"><i class="fas fa-lock mr-1"></i> Company dikunci sesuai akun Anda.</small>
                                @else
                                    {{-- Holding user: bisa pilih semua company --}}
                                    <select class="form-control @error('company_id') is-invalid @enderror" id="company_id" name="company_id">
                                        <option value="">-- Pilih Company (opsional untuk role holding) --</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }} ({{ $company->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">Kosongkan jika user adalah holding-level (Superadmin / Procurement Holding).</small>
                                @endif
                            </div>

                            {{-- Role --}}
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-control @error('role') is-invalid @enderror" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}"
                                            {{ old('role') == $role->name ? 'selected' : '' }}
                                            data-is-holding="{{ in_array($role->name, ['superadmin', 'procurement_holding']) ? '1' : '0' }}">
                                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Department --}}
                            <div class="col-md-6 mb-3" id="department_wrapper">
                                <label for="department_id" class="form-label">
                                    Department
                                    <span id="dept_required_badge" class="badge badge-secondary ml-1" style="font-size:0.7rem;">Opsional</span>
                                </label>
                                <select class="form-control @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                                    <option value="">-- Pilih Department --</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }} ({{ $dept->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted" id="dept_hint">Pilih department sesuai company.</small>
                            </div>

                            {{-- Position --}}
                            <div class="col-md-6 mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control @error('position') is-invalid @enderror" id="position" name="position" value="{{ old('position') }}">
                                @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Phone --}}
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Create User</button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const isCompanyLocked = @json($isCompanyLocked);
        const companySelect   = document.getElementById('company_id');
        const deptSelect      = document.getElementById('department_id');
        const roleSelect      = document.getElementById('role');
        const deptHint        = document.getElementById('dept_hint');
        const deptBadge       = document.getElementById('dept_required_badge');

        // Fetch departments ketika company berubah (hanya untuk holding user)
        if (!isCompanyLocked && companySelect) {
            companySelect.addEventListener('change', function () {
                const companyId = this.value;
                loadDepartments(companyId, null);
            });
        }

        // Ubah hint/badge department berdasarkan role yang dipilih
        if (roleSelect) {
            roleSelect.addEventListener('change', function () {
                const isHolding = this.options[this.selectedIndex]?.dataset.isHolding === '1';
                if (isHolding) {
                    deptBadge.textContent = 'Opsional';
                    deptBadge.className = 'badge badge-secondary ml-1';
                    deptHint.textContent = 'Role holding-level tidak wajib memiliki department.';
                    // Kosongkan jika perlu
                    if (!isCompanyLocked && companySelect) {
                        // reset company juga opsional
                    }
                } else {
                    deptBadge.textContent = 'Opsional';
                    deptBadge.className = 'badge badge-secondary ml-1';
                    deptHint.textContent = 'Pilih department sesuai company.';
                }
            });
        }

        function loadDepartments(companyId, selectedId) {
            // Reset options
            deptSelect.innerHTML = '<option value="">-- Memuat... --</option>';

            if (!companyId) {
                deptSelect.innerHTML = '<option value="">-- Pilih Department --</option>';
                return;
            }

            fetch(`/api/companies/${companyId}/departments`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(res => res.json())
            .then(data => {
                deptSelect.innerHTML = '<option value="">-- Pilih Department --</option>';
                data.forEach(dept => {
                    const opt = document.createElement('option');
                    opt.value = dept.id;
                    opt.textContent = `${dept.name} (${dept.code})`;
                    if (selectedId && dept.id == selectedId) opt.selected = true;
                    deptSelect.appendChild(opt);
                });
            })
            .catch(() => {
                deptSelect.innerHTML = '<option value="">-- Gagal memuat department --</option>';
            });
        }

        // Auto-load jika ada old('company_id')
        @if(old('company_id') && !$isCompanyLocked)
            loadDepartments('{{ old('company_id') }}', '{{ old('department_id') }}');
        @endif
    })();
    </script>
    @endpush
</x-app-layout>
