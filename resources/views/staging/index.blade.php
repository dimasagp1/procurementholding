@extends('layouts.app')

@section('content')
<style>
    /* Custom High-Contrast Table Styles for Staging (Light & Dark Mode) */
    
    /* Dark Mode Overrides */
    .dark-mode table.table tbody tr.table-success td,
    .dark-mode table.table tbody tr.table-success td * {
        background-color: #1b4d22 !important; /* Rich Dark Green */
        color: #a7f3d0 !important; /* High contrast bright text */
    }

    .dark-mode table.table tbody tr.table-secondary td,
    .dark-mode table.table tbody tr.table-secondary td * {
        background-color: #2d3748 !important; /* Dark Grey */
        color: #e2e8f0 !important; /* Light Grey Text */
    }

    /* Light Mode Overrides */
    table.table tbody tr.table-success td,
    table.table tbody tr.table-success td * {
        background-color: #d4edda !important; /* Soft Light Green */
        color: #155724 !important; /* Dark Green Text */
    }

    table.table tbody tr.table-secondary td,
    table.table tbody tr.table-secondary td * {
        background-color: #e2e3e5 !important; /* Soft Light Grey */
        color: #383d41 !important; /* Dark Grey Text */
    }
</style>
<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-boxes text-warning mr-2"></i>
                    Staging Pengeluaran Pagu
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Staging Pengeluaran Pagu</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->hasRole('superadmin'))
<div class="content-header pt-0">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-secondary d-flex align-items-center justify-content-between flex-wrap" style="gap: 0.5rem;">
                    <div>
                        <i class="fas fa-tools text-warning mr-2"></i>
                        <strong>Mode Maintenance (Superadmin):</strong>
                        Gunakan tombol ini untuk mensinkronkan <em>semua</em> PR lama yang belum terhubung ke pagu Finance.
                        Proses ini aman untuk dijalankan berulang kali (idempotent).
                    </div>
                    <form action="{{ route('purchase-requests.bulk-sync-expenses') }}" method="POST"
                          onsubmit="return confirm('Yakin ingin menjalankan bulk sync expense ke sistem pagu Finance?\n\nProses ini akan mengirim ulang data expense SEMUA PR yang memiliki item ordered/delivered/completed.\nAman dijalankan berulang kali.')">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm text-dark font-weight-bold">
                            <i class="fas fa-sync-alt mr-1"></i> Bulk Sync Semua PR ke Pagu
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Main Content -->
<section class="content">
    <div class="container-fluid">

        @if(isset($error))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-ban"></i> Koneksi Gagal!</h5>
            {{ $error }}
        </div>
        @endif

        <!-- Info Alert -->
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-info-circle"></i> Informasi Staging</h5>
            Data berikut adalah pengeluaran pagu yang tercatat otomatis saat PR disetujui di sistem Procurement ini.
            Pihak Finance akan memeriksa data ini di sistem FAT dan melakukan pencatatan <strong>BON</strong> secara manual di Odoo.
            Status <span class="badge badge-warning">Pending</span> = belum diproses Finance,
            <span class="badge badge-success">BON</span> = sudah dicatat di Odoo,
            <span class="badge badge-secondary">Diabaikan</span> = dilewati Finance.
        </div>

        <!-- Summary Cards -->
        @if($summary)
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($summary->total) }}</h3>
                        <p>Total Staging</p>
                    </div>
                    <div class="icon"><i class="fas fa-list"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ number_format($summary->pending_count) }}</h3>
                        <p>Pending (Belum BON)</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($summary->bon_count) }}</h3>
                        <p>Sudah BON</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>Rp {{ number_format($summary->total_amount, 0, ',', '.') }}</h3>
                        <p>Total Nilai (Aktif)</p>
                    </div>
                    <div class="icon"><i class="fas fa-coins"></i></div>
                </div>
            </div>
        </div>
        @endif

        <!-- Filter Card -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter Data</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('staging-pagu.index') }}" class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Cari Referensi / Deskripsi</label>
                            <input type="text" name="search" class="form-control"
                                   value="{{ request('search') }}"
                                   placeholder="Contoh: PR-2026-001...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Departemen</label>
                            <select name="department" class="form-control">
                                <option value="">Semua Departemen</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}" @selected(request('department') === $dept)>{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="pending" @selected(request('status') === 'pending')>Pending (Belum BON)</option>
                                <option value="bon" @selected(request('status') === 'bon')>BON (Sudah Diproses)</option>
                                <option value="ignored" @selected(request('status') === 'ignored')>Diabaikan</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-1"></i> Filter
                            </button>
                            @if(request()->anyFilled(['search', 'department', 'status']))
                                <a href="{{ route('staging-pagu.index') }}" class="btn btn-default btn-block mt-1">
                                    <i class="fas fa-times mr-1"></i> Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table Card -->
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-boxes mr-1"></i>
                    Daftar Staging Pengeluaran Pagu
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning">{{ $stagings->total() }} entri</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>Referensi PR</th>
                                <th>Departemen & Kategori</th>
                                <th width="10%">Tanggal</th>
                                <th>Deskripsi</th>
                                <th width="6%" class="text-center">Qty</th>
                                <th width="12%" class="text-right">Jumlah (Rp)</th>
                                <th width="10%" class="text-center">Status</th>
                                <th width="10%" class="text-center">Dicek Pada</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stagings as $index => $staging)
                                @php
                                    if ($staging->status === 'pending') {
                                        $badge = 'badge-warning';
                                        $label = 'Pending';
                                        $rowClass = '';
                                    } elseif ($staging->status === 'bon') {
                                        $badge = 'badge-success';
                                        $label = 'BON ✓';
                                        $rowClass = 'table-success';
                                    } else {
                                        $badge = 'badge-secondary';
                                        $label = 'Diabaikan';
                                        $rowClass = 'table-secondary';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="text-center">{{ $stagings->firstItem() + $index }}</td>
                                    <td>
                                        <strong class="text-dark">{{ $staging->reference }}</strong>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $staging->department_name ?? '-' }}</div>
                                        <small class="text-muted">
                                            <span class="badge badge-light border">[{{ $staging->category_code ?? '-' }}]</span>
                                            {{ $staging->category_name ?? '-' }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <small>{{ \Carbon\Carbon::parse($staging->date)->format('d M Y') }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $staging->description }}</small>
                                    </td>
                                    <td class="text-center font-weight-bold">
                                        {{ number_format($staging->qty, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format($staging->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $badge }}">{{ $label }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($staging->checked_at)
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($staging->checked_at)->format('d M Y') }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                        Tidak ada data staging yang ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($stagings->hasPages())
            <div class="card-footer clearfix">
                <div class="float-right">
                    {{ $stagings->links() }}
                </div>
                <small class="text-muted">
                    Menampilkan {{ $stagings->firstItem() }}–{{ $stagings->lastItem() }} dari {{ $stagings->total() }} entri
                </small>
            </div>
            @endif
        </div>

    </div>
</section>
@endsection
