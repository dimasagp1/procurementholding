<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Finance Budget Management') }}
        </h2>
    </x-slot>

    <div class="row">
        {{-- Credentials Form Card (Top, Full Width) --}}
        <div class="col-12 mb-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                    <h3 class="card-title text-lg font-medium"><i class="fas fa-plug mr-2 text-primary"></i> Kredensial API Finance</h3>
                </div>
                <form id="form-finance-credentials">
                    <div class="card-body px-4 pb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="finance_api_url" class="form-label font-medium text-sm">Finance API URL</label>
                                    <input type="url" id="finance_api_url" class="form-control" placeholder="http://fat.test/api/budget/check" value="{{ \App\Models\Setting::get('finance_api_url', env('FINANCE_API_URL')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="procurement_api_key" class="form-label font-medium text-sm">Procurement API Key</label>
                                    <div class="input-group">
                                        <input type="text" id="procurement_api_key" class="form-control" placeholder="API Key" value="{{ \App\Models\Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY')) }}" required>
                                        <div class="input-group-append">
                                            <button type="button" id="btn-generate-key" class="btn btn-outline-secondary" title="Generate Random API Key">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6 mb-2">
                                <button type="button" id="btn-test-connection" class="btn btn-warning btn-block">
                                    <i class="fas fa-satellite-dish mr-1"></i> Test Koneksi
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" id="btn-save-credentials" class="btn btn-primary btn-block">
                                    <i class="fas fa-save mr-1"></i> Simpan Kredensial
                                </button>
                            </div>
                        </div>

                        <div id="test-connection-result" class="mt-3" style="display:none;">
                            <div id="test-connection-result-inner" class="alert mb-0 py-2 px-3 text-xs shadow-sm rounded"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Finance Budget Management Card (Bottom, Full Width) --}}
        <div class="col-12">
            <div class="card shadow-sm rounded-lg">
                <div class="card-header border-bottom-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-lg font-medium mb-0">
                        <i class="fas fa-coins mr-2 text-success"></i> Finance Budget Management
                    </h3>
                    <button type="button" id="btn-refresh-finance-budget" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-sync-alt"></i> Cek Status
                    </button>
                </div>
                <div class="card-body p-4">
                    <p class="text-sm text-muted mb-4">
                        Kelola dan inisialisasi pagu anggaran bulanan di sistem Finance (FAT). Halaman ini melakukan verifikasi langsung ke server FAT untuk mengetahui apakah anggaran bulanan telah dikonfigurasi.
                    </p>

                    <div id="finance-budget-status-container" class="mb-4 p-3 bg-light rounded border">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted d-block"><i class="fas fa-info-circle mr-1"></i> <strong>Status Pagu Saat Ini:</strong></small>
                                <span id="budget-status-badge" class="badge badge-secondary mt-1" style="font-size: 14px; padding: 8px 12px;">
                                    <i class="fas fa-spinner fa-spin"></i> Menghubungkan...
                                </span>
                            </div>
                            <button type="button" id="btn-generate-finance-budget" class="btn btn-success" style="display:none;">
                                <i class="fas fa-magic mr-1"></i> Inisialisasi Pagu Anggaran (12 Bulan)
                            </button>
                        </div>
                    </div>
                    
                    <div id="finance-budget-result" class="mt-3" style="display:none;">
                        <div id="finance-budget-result-inner" class="alert mb-0 py-3 px-4 shadow-sm rounded"></div>
                    </div>

                    <div id="finance-budget-data-container" class="mt-4" style="display:none;">
                        <h4 class="font-medium text-md mb-3 border-bottom pb-2">Detail Pagu Anggaran Global</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-sm">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center" width="10%">Bulan</th>
                                        <th class="text-center" width="20%">Tahun</th>
                                        <th class="text-center" width="40%">Jumlah (Rp)</th>
                                        <th class="text-center" width="30%">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="finance-budget-data-tbody">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Memuat data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="finance-budget-detail-container" class="mt-5" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h4 class="font-medium text-md mb-0"><i class="fas fa-sitemap mr-1"></i> Detail Pagu per Departemen & Kategori</h4>
                            <div class="d-flex align-items-center" style="gap: 8px;">
                                <label for="filter-month" class="text-sm text-muted mb-0">Bulan:</label>
                                <input type="month" id="filter-month" class="form-control form-control-sm" style="width: 160px; height: 31px;" value="{{ date('Y-m') }}">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-sm">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center" width="28%">Nama Kategori</th>
                                        <th class="text-center" width="18%">Pagu (A)</th>
                                        <th class="text-center" width="18%">Realisasi FAT (B)</th>
                                        <th class="text-center" width="18%">Penggunaan PR (C)</th>
                                        <th class="text-center" width="18%">Sisa Anggaran (A - B - C)</th>
                                    </tr>
                                </thead>
                                <tbody id="finance-budget-detail-tbody">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Memuat detail anggaran...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> {{-- end col-12 --}}
    </div> {{-- end row --}}

    @push('scripts')
    <script>
        (function() {
            try {
                const badge = document.getElementById('budget-status-badge');
                const genBtn = document.getElementById('btn-generate-finance-budget');
                const budgetResult = document.getElementById('finance-budget-result');
                const budgetResultInner = document.getElementById('finance-budget-result-inner');
                const refreshBtn = document.getElementById('btn-refresh-finance-budget');

                if (!badge || !genBtn || !budgetResult || !budgetResultInner || !refreshBtn) {
                    console.error("Elemen DOM Finance Budget tidak ditemukan!");
                    return;
                }

                function fetchBudgetData() {
                    const dataContainer = document.getElementById('finance-budget-data-container');
                    const tbody = document.getElementById('finance-budget-data-tbody');
                    dataContainer.style.display = 'block';
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat data dari Finance...</td></tr>';

                    fetch('{{ route("settings.finance-budget-data") }}?t=' + new Date().getTime(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success' && data.data && data.data.length > 0) {
                            tbody.innerHTML = '';
                            data.data.forEach(item => {
                                const tr = document.createElement('tr');
                                
                                // Format number as IDR
                                const amountStr = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(item.amount);

                                tr.innerHTML = `
                                    <td class="text-center">${item.month}</td>
                                    <td class="text-center">${item.year}</td>
                                    <td class="text-right">${amountStr}</td>
                                    <td>${item.notes || '-'}</td>
                                `;
                                tbody.appendChild(tr);
                            });
                        } else {
                            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Data pagu anggaran kosong atau tidak ditemukan.</td></tr>';
                        }
                    })
                    .catch(err => {
                        console.error('Error fetching budget data:', err);
                        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger"><i class="fas fa-exclamation-circle mr-1"></i> Gagal mengambil data: ${err.message}</td></tr>`;
                    });
                }

                function fetchDetailedBudgetData() {
                    const detailContainer = document.getElementById('finance-budget-detail-container');
                    const tbody = document.getElementById('finance-budget-detail-tbody');
                    const filterMonthInput = document.getElementById('filter-month');
                    const monthVal = filterMonthInput ? filterMonthInput.value : '';

                    detailContainer.style.display = 'block';
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat detail departemen & kategori...</td></tr>';

                    fetch('{{ route("settings.finance-budget-detail") }}?month=' + monthVal + '&t=' + new Date().getTime(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success' && data.data && data.data.length > 0) {
                            tbody.innerHTML = '';
                            
                            const formatVal = (val) => {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
                            };

                            let hasData = false;
                            data.data.forEach(dept => {
                                if (dept.categories && dept.categories.length > 0) {
                                    hasData = true;
                                    
                                    // Department header row
                                    const deptRow = document.createElement('tr');
                                    deptRow.className = 'bg-light font-weight-bold';
                                    deptRow.innerHTML = `<td colspan="5" class="py-2"><i class="fas fa-building text-secondary mr-2"></i>${dept.department_name}</td>`;
                                    tbody.appendChild(deptRow);

                                    // Category rows
                                    dept.categories.forEach(cat => {
                                        const tr = document.createElement('tr');
                                        const sisaClass = cat.sisa < 0 ? 'text-danger font-weight-bold' : 'text-success font-weight-bold';
                                        
                                        tr.innerHTML = `
                                            <td class="pl-4"><i class="fas fa-caret-right text-muted mr-1"></i> ${cat.category_name}</td>
                                            <td class="text-right">${formatVal(cat.pagu)}</td>
                                            <td class="text-right text-info">${formatVal(cat.realisasi)}</td>
                                            <td class="text-right text-warning">${formatVal(cat.penggunaan)}</td>
                                            <td class="text-right ${sisaClass}">${formatVal(cat.sisa)}</td>
                                        `;
                                        tbody.appendChild(tr);
                                    });
                                }
                            });

                            if (!hasData) {
                                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada kategori anggaran untuk bulan ini.</td></tr>';
                            }
                        } else {
                            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Detail anggaran kosong atau tidak ditemukan.</td></tr>';
                        }
                    })
                    .catch(err => {
                        console.error('Error fetching detailed budget data:', err);
                        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger"><i class="fas fa-exclamation-circle mr-1"></i> Gagal mengambil detail anggaran: ${err.message}</td></tr>`;
                    });
                }

                function checkBudgetStatus() {
                    badge.className = 'badge badge-secondary mt-1';
                    badge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

                    fetch('{{ route("settings.finance-budget-status") }}?t=' + new Date().getTime(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    })
                        .then(res => {
                            if (!res.ok) {
                                return res.json().then(errData => {
                                    throw new Error(errData.message || 'HTTP status ' + res.status);
                                }).catch(e => {
                                    throw new Error('HTTP status ' + res.status);
                                });
                            }
                            return res.json();
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                if (data.has_budgets) {
                                    badge.className = 'badge badge-success mt-1';
                                    badge.innerHTML = `<i class="fas fa-check-circle mr-1"></i> Aktif (${data.months_count} Bulan) - FY ${data.fiscal_year}`;
                                    genBtn.style.display = 'none';
                                    fetchBudgetData();
                                    fetchDetailedBudgetData();
                                } else {
                                    badge.className = 'badge badge-warning mt-1';
                                    badge.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i> Belum Dibuat - FY ${data.fiscal_year}`;
                                    genBtn.style.display = 'inline-block';
                                    
                                    // Tampilkan tabel dalam keadaan kosong agar user tahu fiturnya ada
                                    const dataContainer = document.getElementById('finance-budget-data-container');
                                    const tbody = document.getElementById('finance-budget-data-tbody');
                                    dataContainer.style.display = 'block';
                                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Pagu anggaran belum diinisialisasi. Silakan klik tombol inisialisasi.</td></tr>';

                                    const detailContainer = document.getElementById('finance-budget-detail-container');
                                    const detailTbody = document.getElementById('finance-budget-detail-tbody');
                                    detailContainer.style.display = 'block';
                                    detailTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Detail anggaran belum diinisialisasi. Silakan klik tombol inisialisasi.</td></tr>';
                                }
                            } else {
                                badge.className = 'badge badge-danger mt-1';
                                badge.innerHTML = `<i class="fas fa-times-circle mr-1"></i> ${data.message || 'Error cek status'}`;
                                genBtn.style.display = 'inline-block';
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching budget status:', err);
                            badge.className = 'badge badge-danger mt-1';
                            badge.innerHTML = `<i class="fas fa-times-circle mr-1"></i> Koneksi Gagal: ${err.message}`;
                            genBtn.style.display = 'inline-block';
                        });
                }

                refreshBtn.addEventListener('click', checkBudgetStatus);

                const filterMonth = document.getElementById('filter-month');
                if (filterMonth) {
                    filterMonth.addEventListener('change', fetchDetailedBudgetData);
                }

                genBtn.addEventListener('click', function() {
                    const btn = this;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...';
                    budgetResult.style.display = 'none';

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]')?.value;

                    fetch('{{ route("settings.finance-budget-generate") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    })
                    .then(res => res.json())
                    .then(data => {
                        budgetResult.style.display = 'block';
                        if (data.status === 'success') {
                            budgetResultInner.className = 'alert alert-success mb-0 py-3 px-4 shadow-sm rounded';
                            budgetResultInner.innerHTML = `<strong><i class="fas fa-check-circle mr-1"></i> Berhasil!</strong><br><small>${data.message}</small>`;
                            checkBudgetStatus();
                        } else {
                            budgetResultInner.className = 'alert alert-danger mb-0 py-3 px-4 shadow-sm rounded';
                            budgetResultInner.innerHTML = `<strong><i class="fas fa-times-circle mr-1"></i> Gagal!</strong><br><small>${data.message}</small>`;
                        }
                    })
                    .catch(err => {
                        budgetResult.style.display = 'block';
                        budgetResultInner.className = 'alert alert-danger mb-0 py-3 px-4 shadow-sm rounded';
                        budgetResultInner.innerHTML = `<strong><i class="fas fa-times-circle mr-1"></i> Error:</strong> ${err.message}`;
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-magic mr-1"></i> Inisialisasi Pagu Anggaran (12 Bulan)';
                    });
                });

                // Auto jalankan saat pertama load
                checkBudgetStatus();

                // Generate API Key
                document.getElementById('btn-generate-key').addEventListener('click', function() {
                    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                    let result = 'FAT_KEY_';
                    for (let i = 0; i < 32; i++) {
                        result += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    document.getElementById('procurement_api_key').value = result;
                });

                // Test Connection
                document.getElementById('btn-test-connection').addEventListener('click', function() {
                    const btn = this;
                    const urlInput = document.getElementById('finance_api_url').value;
                    const keyInput = document.getElementById('procurement_api_key').value;
                    const resultContainer = document.getElementById('test-connection-result');
                    const resultInner = document.getElementById('test-connection-result-inner');

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menghubungkan...';
                    resultContainer.style.display = 'none';

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]')?.value;

                    fetch('{{ route("settings.test-finance-api") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            finance_api_url: urlInput,
                            procurement_api_key: keyInput
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        resultContainer.style.display = 'block';
                        if (data.success) {
                            resultInner.className = 'alert alert-success mb-0 py-2 px-3 text-xs';
                            resultInner.innerHTML = `<strong><i class="fas fa-check-circle mr-1"></i> Sukses!</strong> Terhubung ke FAT (${data.latency_ms}ms)`;
                        } else {
                            resultInner.className = 'alert alert-danger mb-0 py-2 px-3 text-xs';
                            resultInner.innerHTML = `<strong><i class="fas fa-times-circle mr-1"></i> Gagal!</strong> ${data.message}`;
                        }
                    })
                    .catch(err => {
                        resultContainer.style.display = 'block';
                        resultInner.className = 'alert alert-danger mb-0 py-2 px-3 text-xs';
                        resultInner.innerHTML = `<strong><i class="fas fa-exclamation-triangle mr-1"></i> Error:</strong> ${err.message}`;
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-satellite-dish mr-1"></i> Test Koneksi';
                    });
                });

                // Save Credentials
                document.getElementById('form-finance-credentials').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('btn-save-credentials');
                    const urlInput = document.getElementById('finance_api_url').value;
                    const keyInput = document.getElementById('procurement_api_key').value;

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]')?.value;

                    fetch('{{ route("settings.update-finance-credentials") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            finance_api_url: urlInput,
                            procurement_api_key: keyInput
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('Kredensial integrasi berhasil disimpan!');
                            location.reload();
                        } else {
                            alert('Gagal menyimpan: ' + data.message);
                        }
                    })
                    .catch(err => {
                        alert('Error: ' + err.message);
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan Kredensial';
                    });
                });

            } catch (e) {
                console.error("Runtime Exception di script budget:", e);
                const badge = document.getElementById('budget-status-badge');
                if (badge) {
                    badge.innerHTML = "Error rendering script";
                    badge.className = "badge badge-danger mt-1";
                }
            }
        })();
    </script>
    @endpush
</x-app-layout>
