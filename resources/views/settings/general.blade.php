<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('General Settings') }}
        </h2>
    </x-slot>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm rounded-lg">
                <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                    <h3 class="card-title text-lg font-medium"><i class="fas fa-cogs mr-2 text-primary"></i> System Configuration</h3>
                </div>
                <form action="{{ route('settings.update-general') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body px-4 pb-4">
                        <div class="form-group">
                            <label for="app_name">System Name</label>
                            <input type="text" name="app_name" class="form-control @error('app_name') is-invalid @enderror" id="app_name" value="{{ old('app_name', $settings['app_name']) }}" required>
                            @error('app_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="app_logo">System Logo</label>
                            @if($settings['app_logo'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['app_logo']) }}" alt="Logo" style="height: 50px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="app_logo" class="custom-file-input" id="app_logo">
                                    <label class="custom-file-label" for="app_logo">Choose file</label>
                                </div>
                            </div>
                            <small class="text-muted">Max size: 2MB. Format: JPG, PNG, SVG.</small>
                        </div>

                        <div class="form-group">
                            <label for="export_logo">Export Logo (for PDF/Preview)</label>
                            @if(isset($settings['export_logo']) && $settings['export_logo'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['export_logo']) }}" alt="Export Logo" style="height: 50px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="export_logo" class="custom-file-input" id="export_logo">
                                    <label class="custom-file-label" for="export_logo">Choose file</label>
                                </div>
                            </div>
                            <small class="text-muted">Max size: 2MB. Format: JPG, PNG, SVG. Used specifically for PR Export.</small>
                        </div>

                        <div class="form-group">
                            <label for="app_favicon">Favicon</label>
                            @if($settings['app_favicon'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['app_favicon']) }}" alt="Favicon" style="height: 32px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="app_favicon" class="custom-file-input" id="app_favicon">
                                    <label class="custom-file-label" for="app_favicon">Choose file</label>
                                </div>
                            </div>
                            <small class="text-muted">Max size: 1MB. Format: ICO, PNG.</small>
                        </div>

                        <hr>
                        <h5 class="mb-3">Digital Signatures (for Export)</h5>
                        
                        <div class="form-group">
                            <label for="signature_om">Operational Manager Signature</label>
                            @if($settings['signature_om'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['signature_om']) }}" alt="OM Signature" style="height: 60px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="signature_om" class="custom-file-input" id="signature_om">
                                    <label class="custom-file-label" for="signature_om">Choose Signature Image</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="signature_gm">General Manager Signature</label>
                            @if($settings['signature_gm'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['signature_gm']) }}" alt="GM Signature" style="height: 60px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="signature_gm" class="custom-file-input" id="signature_gm">
                                    <label class="custom-file-label" for="signature_gm">Choose Signature Image</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="signature_proc">Procurement Signature</label>
                            @if($settings['signature_proc'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['signature_proc']) }}" alt="Procurement Signature" style="height: 60px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="signature_proc" class="custom-file-input" id="signature_proc">
                                    <label class="custom-file-label" for="signature_proc">Choose Signature Image</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>

            {{-- Odoo Configuration Card --}}
            <div class="card shadow-sm rounded-lg mt-4">
                <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                    <h3 class="card-title text-lg font-medium"><i class="fas fa-plug mr-2 text-primary"></i> Odoo ERP Integration</h3>
                </div>
                <form action="{{ route('settings.update-odoo-credentials') }}" method="POST">
                    @csrf
                    <div class="card-body px-4 pb-4">
                        <div class="form-group">
                            <label for="odoo_url">Odoo Instance URL</label>
                            <input type="url" name="odoo_url" class="form-control" id="odoo_url" value="{{ old('odoo_url', $settings['odoo_url']) }}" placeholder="https://your-domain.odoo.com" required>
                            <small class="text-muted">Domain utama Odoo ERP Anda (sertakan https://).</small>
                        </div>
                        <div class="form-group">
                            <label for="odoo_db">Odoo Database Name</label>
                            <input type="text" name="odoo_db" class="form-control" id="odoo_db" value="{{ old('odoo_db', $settings['odoo_db']) }}" required>
                            <small class="text-muted">Nama database Odoo (biasanya subdomain depan Odoo Online).</small>
                        </div>
                        <div class="form-group">
                            <label for="odoo_username">Odoo Email / Username</label>
                            <input type="text" name="odoo_username" class="form-control" id="odoo_username" value="{{ old('odoo_username', $settings['odoo_username']) }}" required>
                            <small class="text-muted">Email admin/pengguna dengan hak akses modul Purchase di Odoo.</small>
                        </div>
                        <div class="form-group">
                            <label for="odoo_password">Odoo Password / API Key</label>
                            <div class="input-group">
                                <input type="password" name="odoo_password" class="form-control" id="odoo_password" value="{{ old('odoo_password', $settings['odoo_password']) }}" required style="border-right: none;">
                                <div class="input-group-append">
                                    <span class="input-group-text" id="toggle-odoo-password" style="cursor: pointer; background-color: transparent; border-left: none; color: #a0aec0;">
                                        <i class="fas fa-eye" id="toggle-odoo-password-icon"></i>
                                    </span>
                                </div>
                            </div>
                            <small class="text-muted">Disarankan menggunakan <strong>API Key</strong> yang digenerate dari profil keamanan Odoo.</small>
                        </div>

                        <div id="odoo-api-result" class="mt-3" style="display:none;">
                            <div id="odoo-api-result-inner" class="alert mb-0 py-2 px-3 text-sm"></div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <button type="button" id="btn-test-odoo-api" class="btn btn-warning">
                            <i class="fas fa-satellite-dish mr-1"></i> Test Koneksi Odoo
                        </button>
                        <button type="submit" class="btn btn-primary">Save Odoo Settings</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                    <h3 class="card-title text-lg font-medium"><i class="fas fa-info-circle mr-2 text-info"></i> Information</h3>
                </div>
                <div class="card-body p-4">
                    <p>These settings will affect the entire application including:</p>
                    <ul>
                        <li>Sidebar Brand Name</li>
                        <li>Browser Tab Title</li>
                        <li>Login Page visuals</li>
                    </ul>
                </div>
            </div>

            {{-- Finance API Connection Test Card --}}
            <div class="card shadow-sm rounded-lg mt-4">
                <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                    <h3 class="card-title text-lg font-medium">
                        <i class="fas fa-plug mr-2 text-warning"></i> Finance API Connection
                    </h3>
                </div>
                <div class="card-body p-4">
                    <p class="text-sm text-muted mb-3">Test koneksi ke sistem Finance API dari environment saat ini.</p>

                    <div class="mb-3">
                        <small class="text-muted d-block"><i class="fas fa-link mr-1"></i> <strong>URL:</strong></small>
                        <code class="text-xs" style="word-break:break-all;">{{ env('FINANCE_API_URL', '<em>Belum diset</em>') }}</code>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block"><i class="fas fa-key mr-1"></i> <strong>API Key:</strong></small>
                        <code class="text-xs">{{ env('PROCUREMENT_API_KEY') ? '••••••' . substr(env('PROCUREMENT_API_KEY'), -4) : '<em>Belum diset</em>' }}</code>
                    </div>

                    <button type="button" id="btn-test-finance-api" class="btn btn-warning btn-block">
                        <i class="fas fa-satellite-dish mr-1"></i> Test Koneksi
                    </button>

                    <div id="finance-api-result" class="mt-3" style="display:none;">
                        <div id="finance-api-result-inner" class="alert mb-0 py-2 px-3"></div>
                    </div>
                </div>
            </div>


        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function () {
            if (typeof bsCustomFileInput !== 'undefined') {
                bsCustomFileInput.init();
            }

            // Toggle Password Visibility
            $('#toggle-odoo-password').on('click', function () {
                const passwordInput = $('#odoo_password');
                const icon = $('#toggle-odoo-password-icon');
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // Odoo API Connection Test
            document.getElementById('btn-test-odoo-api').addEventListener('click', function () {
                const btn = this;
                const resultContainer = document.getElementById('odoo-api-result');
                const resultInner = document.getElementById('odoo-api-result-inner');

                const odooUrl = document.getElementById('odoo_url').value;
                const odooDb = document.getElementById('odoo_db').value;
                const odooUsername = document.getElementById('odoo_username').value;
                const odooPassword = document.getElementById('odoo_password').value;

                if (!odooUrl || !odooDb || !odooUsername || !odooPassword) {
                    resultContainer.style.display = 'block';
                    resultInner.className = 'alert alert-danger mb-0 py-2 px-3';
                    resultInner.innerHTML = '<strong><i class="fas fa-exclamation-triangle"></i> Peringatan:</strong> Harap lengkapi semua field Odoo sebelum melakukan tes koneksi.';
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menghubungkan ke Odoo...';
                resultContainer.style.display = 'none';

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name="_token"]')?.value;

                fetch('{{ route("settings.test-odoo-api") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        odoo_url: odooUrl,
                        odoo_db: odooDb,
                        odoo_username: odooUsername,
                        odoo_password: odooPassword
                    })
                })
                .then(res => res.json())
                .then(data => {
                    resultContainer.style.display = 'block';
                    const latencyInfo = data.latency_ms ? `<br><small>Latency: <strong>${data.latency_ms} ms</strong></small>` : '';
                    if (data.success) {
                        resultInner.className = 'alert alert-success mb-0 py-2 px-3';
                        resultInner.innerHTML = `
                            <strong><i class="fas fa-check-circle"></i> Berhasil Terhubung!</strong><br>
                            <small>${data.message}</small>
                            ${latencyInfo}
                        `;
                    } else {
                        resultInner.className = 'alert alert-danger mb-0 py-2 px-3';
                        resultInner.innerHTML = `
                            <strong><i class="fas fa-times-circle"></i> Koneksi Gagal</strong><br>
                            <small>${data.message}</small>
                            ${latencyInfo}
                        `;
                    }
                })
                .catch(err => {
                    resultContainer.style.display = 'block';
                    resultInner.className = 'alert alert-danger mb-0 py-2 px-3';
                    resultInner.innerHTML = '<strong><i class="fas fa-times-circle"></i> Error:</strong> ' + err.message;
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-satellite-dish mr-1"></i> Test Koneksi Odoo';
                });
            });

            // Finance API Connection Test
            document.getElementById('btn-test-finance-api').addEventListener('click', function () {
                const btn = this;
                const resultContainer = document.getElementById('finance-api-result');
                const resultInner = document.getElementById('finance-api-result-inner');

                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menghubungkan...';
                resultContainer.style.display = 'none';

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name="_token"]')?.value;

                // Set AJAX timeout 12 detik
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 12000);

                fetch('{{ route("settings.test-finance-api") }}', {
                    method: 'POST',
                    signal: controller.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    clearTimeout(timeoutId);
                    resultContainer.style.display = 'block';
                    const urlInfo = data.url_tried ? `<br><small>URL: <code>${data.url_tried}</code></small>` : '';
                    const latencyInfo = data.latency_ms ? `<br><small>Latency: <strong>${data.latency_ms} ms</strong></small>` : '';
                    if (data.success) {
                        resultInner.className = 'alert alert-success mb-0 py-2 px-3';
                        resultInner.innerHTML = `
                            <strong><i class="fas fa-check-circle"></i> Berhasil Terhubung!</strong><br>
                            <small>HTTP Status: <strong>${data.http_status}</strong></small>
                            ${latencyInfo}
                            ${urlInfo}
                            <br><small>Response: <code>${JSON.stringify(data.response_preview)}</code></small>
                        `;
                    } else {
                        resultInner.className = 'alert alert-danger mb-0 py-2 px-3';
                        resultInner.innerHTML = `
                            <strong><i class="fas fa-times-circle"></i> Koneksi Gagal</strong><br>
                            <small>${data.message}</small>
                            ${latencyInfo}
                            ${urlInfo}
                        `;
                    }
                })
                .catch(err => {
                    clearTimeout(timeoutId);
                    resultContainer.style.display = 'block';
                    resultInner.className = 'alert alert-danger mb-0 py-2 px-3';
                    const msg = err.name === 'AbortError' ? 'Timeout: Server tidak merespons dalam 12 detik.' : err.message;
                    resultInner.innerHTML = '<strong><i class="fas fa-times-circle"></i> Error:</strong> ' + msg;
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-satellite-dish mr-1"></i> Test Koneksi';
                });
            });
        });

    </script>
    @endpush
</x-app-layout>
