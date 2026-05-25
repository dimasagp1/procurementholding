@extends('layouts.app')

@section('header')
    <h1 class="m-0">Notifikasi</h1>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm rounded-lg">
                <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                    <h3 class="card-title text-lg font-medium">Daftar Notifikasi</h3>
                    <div class="card-tools">
                        @if($notifications->count() > 0)
                            <form action="{{ route('notifications.mark-all-as-read') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-info">
                                    <i class="fas fa-check-double mr-1"></i> Tandai Semua Terbaca
                                </button>
                            </form>
                            <form action="{{ route('notifications.clear-all') }}" method="POST" class="d-inline ml-1 form-confirm" data-message="Apakah Anda yakin ingin menghapus semua riwayat notifikasi?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash-alt mr-1"></i> Kosongkan Riwayat
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 10px">#</th>
                                    <th>Pesan</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($notifications as $notification)
                                    <tr class="{{ $notification->unread() ? 'bg-light font-weight-bold' : '' }}">
                                        <td>{{ $loop->iteration + ($notifications->firstItem() - 1) }}</td>
                                        <td>{{ $notification->data['message'] }}</td>
                                        <td>{{ $notification->created_at->diffForHumans() }}</td>
                                        <td>
                                            @if($notification->unread())
                                                <span class="badge badge-warning">Belum Dibaca</span>
                                            @else
                                                <span class="badge badge-success">Sudah Dibaca</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('notifications.mark-as-read', $notification->id) }}" class="btn btn-sm btn-primary" title="Lihat">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" class="d-inline ml-1 form-confirm" data-message="Hapus notifikasi ini?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">Tidak ada notifikasi ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer clearfix">
                    <div class="float-right">
                        {{ $notifications->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
