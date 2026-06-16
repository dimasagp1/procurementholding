<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-0">
                {{ __('Odoo ERP Vendors') }}
            </h2>
            @if(Auth::user()->hasAnyRole(['superadmin', 'procurement']))
                <button type="button" class="btn btn-primary btn-sm shadow-sm" data-toggle="modal" data-target="#addVendorModal">
                    <i class="fas fa-plus mr-1"></i> Tambah Vendor ke Odoo
                </button>
            @endif
        </div>
    </x-slot>

    <div class="row">
        <div class="col-md-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(isset($errorMessage) && $errorMessage)
                <div class="alert alert-warning shadow-sm" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i> <strong>Koneksi Odoo Terkendala:</strong> {{ $errorMessage }}
                    <p class="mb-0 mt-2 text-sm">Harap periksa kembali pengaturan kredensial integrasi Odoo Anda di halaman <a href="{{ route('settings.general') }}" class="font-weight-bold text-dark">General Settings</a>.</p>
                </div>
            @endif

            <div class="card shadow-sm rounded-lg">
                <div class="card-header border-bottom-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap" style="gap: 15px;">
                    <div>
                        <h3 class="card-title text-lg font-medium"><i class="fas fa-address-book mr-2 text-primary"></i> Data Vendor Terintegrasi</h3>
                        <p class="text-sm text-muted mb-0 mt-1">Menampilkan data rekanan/pemasok aktif yang disinkronkan langsung dari Odoo ERP.</p>
                    </div>
                    <div class="card-tools" style="min-width: 250px;">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" id="search-vendor" class="form-control text-sm" placeholder="Cari nama, email, kota...">
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="vendors-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Odoo ID</th>
                                    <th>Nama Vendor</th>
                                    <th>Kontak</th>
                                    <th>NPWP / VAT</th>
                                    <th>Alamat / Kota</th>
                                    <th>Website</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vendors as $vendor)
                                    <tr class="vendor-row">
                                        <td class="text-center font-weight-bold">
                                            <span class="badge badge-secondary px-2 py-1" style="font-size: 0.8rem;">#{{ $vendor['id'] }}</span>
                                        </td>
                                        <td class="vendor-name-cell">
                                            <strong>{{ $vendor['name'] }}</strong>
                                        </td>
                                        <td class="vendor-contact-cell text-sm">
                                            @if(!empty($vendor['email']))
                                                <div><i class="far fa-envelope mr-1 text-muted"></i> <a href="mailto:{{ $vendor['email'] }}">{{ $vendor['email'] }}</a></div>
                                            @endif
                                            @if(!empty($vendor['phone']))
                                                <div><i class="fas fa-phone mr-1 text-muted"></i> {{ $vendor['phone'] }}</div>
                                            @endif
                                            @if(!empty($vendor['mobile']))
                                                <div><i class="fas fa-mobile-alt mr-2 text-muted"></i> {{ $vendor['mobile'] }}</div>
                                            @endif
                                            @if(empty($vendor['email']) && empty($vendor['phone']) && empty($vendor['mobile']))
                                                <span class="text-muted font-italic">-</span>
                                            @endif
                                        </td>
                                        <td class="vendor-vat-cell text-sm">
                                            {{ $vendor['vat'] ?: '-' }}
                                        </td>
                                        <td class="vendor-address-cell text-sm">
                                            @if(!empty($vendor['street']))
                                                <span>{{ $vendor['street'] }}</span>
                                            @endif
                                            @if(!empty($vendor['city']))
                                                <br><span class="badge badge-light border text-xs px-2 py-1"><i class="fas fa-map-marker-alt mr-1 text-danger"></i>{{ $vendor['city'] }}</span>
                                            @endif
                                            @if(empty($vendor['street']) && empty($vendor['city']))
                                                <span class="text-muted font-italic">-</span>
                                            @endif
                                        </td>
                                        <td class="vendor-website-cell text-sm">
                                            @if(!empty($vendor['website']))
                                                <a href="{{ $vendor['website'] }}" target="_blank" class="text-blue-600"><i class="fas fa-external-link-alt mr-1"></i>Kunjungi</a>
                                            @else
                                                <span class="text-muted font-italic">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-address-book fa-3x mb-3 text-muted" style="opacity: 0.4;"></i>
                                            <p class="mb-0">Tidak ada data vendor ditemukan.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->hasAnyRole(['superadmin', 'procurement']))
        <!-- Add Vendor Modal -->
        <div class="modal fade" id="addVendorModal" tabindex="-1" role="dialog" aria-labelledby="addVendorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form action="{{ route('settings.odoo-vendors.store') }}" method="POST">
                    @csrf
                    <div class="modal-content" style="background-color: #222630; color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;">
                        <div class="modal-header border-bottom-0">
                            <h5 class="modal-title" id="addVendorModalLabel"><i class="fas fa-plus-circle mr-2 text-primary"></i> Tambah Vendor Baru ke Odoo</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-left">
                            <p class="text-sm text-gray-300 mb-4">Mendaftarkan vendor baru secara langsung ke database Odoo ERP Anda.</p>
                            
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="text-gray-300 text-sm">Nama Vendor / Perusahaan *</label>
                                    <input type="text" name="name" class="form-control" required placeholder="Contoh: PT. Sumber Makmur" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="text-gray-300 text-sm">NPWP / Tax ID / VAT</label>
                                    <input type="text" name="vat" class="form-control" placeholder="Contoh: 01.234.567.8-999.000" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label class="text-gray-300 text-sm">Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="vendor@email.com" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label class="text-gray-300 text-sm">Telepon Kantor</label>
                                    <input type="text" name="phone" class="form-control" placeholder="021-xxxxxx" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label class="text-gray-300 text-sm">HP Kontak Personal</label>
                                    <input type="text" name="mobile" class="form-control" placeholder="0812-xxxx-xxxx" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="text-gray-300 text-sm">Alamat Jalan</label>
                                <textarea name="street" class="form-control" rows="2" placeholder="Jl. Raya No. 123" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="text-gray-300 text-sm">Kota</label>
                                    <input type="text" name="city" class="form-control" placeholder="Contoh: Jakarta Barat" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="text-gray-300 text-sm">Website Perusahaan</label>
                                    <input type="url" name="website" class="form-control" placeholder="https://www.example.com" style="background-color: #1a1d24; border: 1px solid rgba(255,255,255,0.1); color: white;">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-check mr-1"></i> Daftarkan Vendor
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Client-side quick filter
                $('#search-vendor').on('keyup input', function() {
                    const value = $(this).val().toLowerCase();
                    let matchCount = 0;

                    $('.vendor-row').each(function() {
                        const name = $(this).find('.vendor-name-cell').text().toLowerCase();
                        const contact = $(this).find('.vendor-contact-cell').text().toLowerCase();
                        const address = $(this).find('.vendor-address-cell').text().toLowerCase();
                        const vat = $(this).find('.vendor-vat-cell').text().toLowerCase();

                        if (name.includes(value) || contact.includes(value) || address.includes(value) || vat.includes(value)) {
                            $(this).show();
                            matchCount++;
                        } else {
                            $(this).hide();
                        }
                    });

                    // Manage no-match feedback
                    const noMatchId = 'no-vendor-match-row';
                    $('#' + noMatchId).remove();

                    if (matchCount === 0 && $('.vendor-row').length > 0) {
                        $('#vendors-table tbody').append(`
                            <tr id="${noMatchId}">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-2x mb-3"></i>
                                    <p class="mb-0">Pencarian untuk "<strong>${value}</strong>" tidak menghasilkan apapun.</p>
                                </td>
                            </tr>
                        `);
                    }
                });

                // Move modal to body
                const $addModal = $('#addVendorModal');
                if ($addModal.length && $addModal.parent().is('body') === false) {
                    $addModal.appendTo('body');
                }
            });
        </script>
    @endpush
</x-app-layout>
