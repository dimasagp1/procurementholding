<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label">Employee ID</label>
                                <input type="text" class="form-control @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}" required>
                                @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                             <div class="col-md-6 mb-3">
                                <label for="company_id" class="form-label">Company</label>
                                <select class="form-control @error('company_id') is-invalid @enderror" id="company_id" name="company_id" required>
                                    <option value="">Select Company</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }} ({{ $company->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label">Department</label>
                                <select class="form-control @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }} ({{ $dept->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-control @error('role') is-invalid @enderror" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ old('role', $user->getRoleNames()->first()) == $role->name ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control @error('position') is-invalid @enderror" id="position" name="position" value="{{ old('position', $user->position) }}">
                                @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                 <label for="phone" class="form-label">Phone</label>
                                 <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                                 @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Signature Upload --}}
                            <div class="col-md-12">
                                <hr class="my-4">
                                <h5 class="mb-3"><i class="fas fa-signature mr-2 text-info"></i> Tanda Tangan</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="signature_file" class="form-label">Upload Tanda Tangan <small class="text-muted">(PNG/JPG, maks. 2MB)</small></label>
                                <input type="file" class="form-control @error('signature_file') is-invalid @enderror" id="signature_file" name="signature_file" accept="image/png,image/jpeg,image/jpg">
                                @error('signature_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Upload file baru untuk mengganti tanda tangan yang ada.</small>
                            </div>

                            <div class="col-md-6 mb-3 d-flex align-items-center">
                                @if($user->signature_path)
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                                        <p class="text-muted small mb-2">Tanda tangan saat ini:</p>
                                        <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Signature" style="max-height: 80px; max-width: 200px; object-fit: contain; background: white; padding: 4px; border-radius: 4px;">
                                        <div class="mt-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="clear_signature" name="clear_signature" value="1">
                                                <label class="form-check-label text-danger small" for="clear_signature">
                                                    <i class="fas fa-trash-alt mr-1"></i> Hapus tanda tangan
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-muted small"><i class="fas fa-exclamation-circle mr-1"></i> Belum ada tanda tangan.</div>
                                @endif
                            </div>

                            <div class="col-md-12">
                                <hr class="my-4">
                                <h5 class="mb-3">Change Password</h5>
                                <p class="text-muted small mb-3">Leave blank if you don't want to change the password.</p>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update User</button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
