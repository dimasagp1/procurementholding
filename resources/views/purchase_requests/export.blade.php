<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>PR - {{ $purchaseRequest->pr_number }}</title>

<style>
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        color: #333;
    }

    .header-table {
        width: 100%;
        border-bottom: 2px solid #333;
        margin-bottom: 15px;
        padding-bottom: 5px;
    }

    .company-name {
        font-size: 11pt;
        font-weight: bold;
        color: #000;
    }

    .company-address {
        font-size: 6.5pt;
        color: #333;
        line-height: 1;
    }

    .logo-container {
        text-align: right;
    }

    .logo-container img {
        height: 30px;
    }

    .document-title {
        font-size: 12pt;
        font-weight: bold;
        margin-top: 3px;
        margin-bottom: 10px;
        color: #000;
        text-transform: uppercase;
    }

    /* Metadata Box */
    .meta-container {
        float: right;
        width: 35%;
        margin-bottom: 15px;
    }

    .meta-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 7pt;
    }

    .meta-table td {
        border: 1px solid #333;
        padding: 1px 5px;
    }

    .meta-label {
        background-color: #f2f2f2;
        font-weight: bold;
        width: 40%;
    }

    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }

    .intro-text {
        font-size: 7pt;
        margin-bottom: 2px;
    }

    .target-date {
        font-size: 7pt;
        font-weight: bold;
        margin-bottom: 5px;
    }

    /* Items Table */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 7pt;
        margin-bottom: 10px;
    }

    .items-table th {
        background-color: #92D050; /* Green from image */
        border: 1px solid #333;
        padding: 3px;
        font-weight: bold;
        text-align: center;
    }

    .items-table td {
        border: 1px solid #333;
        padding: 2px 5px;
        height: 12px;
    }

    .text-center { text-align: center; }

    /* Notes Box */
    .notes-section {
        margin-top: 3px;
        font-size: 7pt;
    }

    .notes-label {
        font-weight: bold;
        margin-bottom: 2px;
    }

    .notes-box {
        width: 100%;
        min-height: 30px;
        border: 1px solid #333;
        padding: 5px;
        box-sizing: border-box;
    }

    /* Signature Grid */
    .signature-grid {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 7pt;
    }

    .signature-grid td {
        border: 1px solid #333;
        width: 33.33%;
        text-align: center;
        padding: 0;
    }

    .sig-header {
        padding: 4px;
        border-bottom: 1px solid #333;
        background-color: #f9f9f9;
        font-weight: bold;
    }

    .sig-body {
        height: 55px;
        vertical-align: bottom;
        padding-top: 20px;
        padding-bottom: 3px;
        text-align: center;
    }

    .sig-name {
        font-weight: bold;
        text-decoration: underline;
        margin-top: 0;
        margin-bottom: 1px;
    }

    .sig-role {
        font-size: 6.5pt;
        text-transform: uppercase;
        margin-bottom: 0;
        margin-top: 0;
    }

    .digitally-signed {
        color: #28a745;
        font-size: 5.5pt;
        font-weight: bold;
        display: block;
        margin-bottom: 2px;
    }
</style>
</head>

<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td width="70%">
                @php
                    $printCompany = $purchaseRequest->company;
                @endphp
                <div class="company-name">{{ strtoupper($printCompany->name ?? 'PT. HERBATECH INNOPHARMA INDUSTRY') }}</div>
                @if($printCompany && $printCompany->description)
                    <div class="company-address">{{ $printCompany->description }}</div>
                @else
                    <div class="company-address" style="color:#aaa; font-style:italic;">—</div>
                @endif
                <div style="font-size:6pt; color:#666; margin-top:2px;">
                    Kode Perusahaan: <strong>{{ $printCompany->code ?? '' }}</strong>
                </div>
            </td>
            <td width="30%" class="logo-container">
                @php
                    // Priority 1: Export Logo
                    $logoSetting = \App\Models\Setting::get('export_logo');
                    
                    // Priority 2: App Logo (fallback)
                    if (!$logoSetting) {
                        $logoSetting = \App\Models\Setting::get('app_logo');
                    }
                    
                    $logoBase64 = '';
                    
                    if ($logoSetting) {
                        $logoPath = storage_path('app/public/' . $logoSetting);
                        if (!file_exists($logoPath)) {
                            $logoPath = public_path('storage/' . $logoSetting);
                        }
                        
                        if (file_exists($logoPath)) {
                            $mime = function_exists('mime_content_type') ? mime_content_type($logoPath) : 'image/png';
                            $logoData = file_get_contents($logoPath);
                            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($logoData);
                        }
                    }
                    
                    // Priority 3: Default Hardcoded Logo
                    if (!$logoBase64) {
                        $logoPath = public_path('images/hbt.png');
                        if (file_exists($logoPath)) {
                            $logoData = file_get_contents($logoPath);
                            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                        }
                    }
                @endphp
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 50px;">
                @endif
            </td>
        </tr>
    </table>


    <div class="document-title">SURAT PERMINTAAN PEMBELIAN</div>

    <div class="clearfix">
        <div class="meta-container">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Perusahaan</td>
                    <td>: {{ $purchaseRequest->company->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Kode</td>
                    <td>: {{ $purchaseRequest->company->code ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">No. PR</td>
                    <td>: {{ $purchaseRequest->pr_number }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Tanggal</td>
                    <td>: {{ $purchaseRequest->created_at->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Departemen</td>
                    <td>: {{ $purchaseRequest->department->name }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Request Date</td>
                    <td>: {{ $purchaseRequest->request_date?->format('d/m/Y') ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="intro-text">Agar dibelikan barang sesuai daftar berikut:</div>
    <div class="target-date">Target tiba: {{ $purchaseRequest->request_date?->format('d/m/Y') ?? '-' }}</div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="55%">Nama Barang</th>
                <th width="10%">Qty</th>
                <th width="15%">Uom</th>
                <th width="20%">Ket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseRequest->items as $item)
            <tr>
                <td>
                    <b>{{ $item->item_name }}</b>
                    @if($item->description)
                        <br><small style="color: #666;">{{ $item->description }}</small>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-center">{{ $item->uom }}</td>
                <td>{{ $item->due_date }}</td>
            </tr>
            @endforeach
            
            {{-- Fill empty rows to match the image aesthetic --}}
            @for($i = count($purchaseRequest->items); $i < 10; $i++)
            <tr>
                <td>&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endfor
        </tbody>
    </table>

    <div class="notes-section">
        <div class="notes-label">Notes:</div>
        <div class="notes-box">
            {{ $purchaseRequest->purpose }}
        </div>
    </div>

    @php
        $hasOmApproval = $purchaseRequest->approvals()->whereIn('approver_role', ['operational_manager', 'manager_fat'])->where('status', 'approved')->exists();
        $hasGmApproval = $purchaseRequest->approvals()->where('approver_role', 'general_manager')->where('status', 'approved')->exists();
        $hasProcApproval = $purchaseRequest->items()->whereIn('status', ['approved_proc', 'ordered', 'delivered', 'completed'])->exists();
        
        $omApproval = $purchaseRequest->approvals()->whereIn('approver_role', ['operational_manager', 'manager_fat'])->orderBy('created_at', 'desc')->first();
        $gmApproval = $purchaseRequest->approvals()->where('approver_role', 'general_manager')->orderBy('created_at', 'desc')->first();
        
        $omName = ($omApproval && $omApproval->approver) ? $omApproval->approver->name : 'Rifqi Aulawi Yunahar';
        $gmName = ($gmApproval && $gmApproval->approver) ? $gmApproval->approver->name : 'Lisa Hana';

        // Get digital signatures from settings
        $valOm = \App\Models\Setting::get('signature_om');
        $valGm = \App\Models\Setting::get('signature_gm');
        $valProc = \App\Models\Setting::get('signature_proc');

        $sigOmBase64 = '';
        if ($valOm) {
            $sigOmPath = storage_path('app/public/' . $valOm);
            if (!file_exists($sigOmPath)) {
                $sigOmPath = public_path('storage/' . $valOm);
            }
            if (file_exists($sigOmPath) && is_file($sigOmPath)) {
                $mime = function_exists('mime_content_type') ? mime_content_type($sigOmPath) : 'image/png';
                $sigOmBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($sigOmPath));
            }
        }
        
        $sigGmBase64 = '';
        if ($valGm) {
            $sigGmPath = storage_path('app/public/' . $valGm);
            if (!file_exists($sigGmPath)) {
                $sigGmPath = public_path('storage/' . $valGm);
            }
            if (file_exists($sigGmPath) && is_file($sigGmPath)) {
                $mime = function_exists('mime_content_type') ? mime_content_type($sigGmPath) : 'image/png';
                $sigGmBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($sigGmPath));
            }
        }

        $sigProcBase64 = '';
        if ($valProc) {
            $sigProcPath = storage_path('app/public/' . $valProc);
            if (!file_exists($sigProcPath)) {
                $sigProcPath = public_path('storage/' . $valProc);
            }
            if (file_exists($sigProcPath) && is_file($sigProcPath)) {
                $mime = function_exists('mime_content_type') ? mime_content_type($sigProcPath) : 'image/png';
                $sigProcBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($sigProcPath));
            }
        }

        $sigRequesterBase64 = '';
        if ($purchaseRequest->user?->signature_path) {
            $sigRequesterPath = storage_path('app/public/' . $purchaseRequest->user->signature_path);
            if (!file_exists($sigRequesterPath)) {
                $sigRequesterPath = public_path('storage/' . $purchaseRequest->user->signature_path);
            }
            if (file_exists($sigRequesterPath) && is_file($sigRequesterPath)) {
                $mime = function_exists('mime_content_type') ? mime_content_type($sigRequesterPath) : 'image/png';
                $sigRequesterBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($sigRequesterPath));
            }
        }
    @endphp

    <style>
        .signature-grid td { width: 33.33% !important; }
        .sig-image { height: 35px; width: auto; margin-bottom: 0; margin-left: auto; margin-right: auto; display: block; }
        .sig-spacer { height: 35px; margin-bottom: 0; }
    </style>

    <table class="signature-grid">
        <tr>
            <td>
                <div class="sig-header">Request By</div>
                <div class="sig-body">
                    @if($sigRequesterBase64)
                        <img src="{{ $sigRequesterBase64 }}" class="sig-image">
                    @else
                        <div class="sig-spacer"></div>
                    @endif
                    <div class="sig-name">{{ $purchaseRequest->user->name }}</div>
                    <div class="sig-role">{{ $purchaseRequest->user->getRoleNames()->first() ?? 'Staff' }}</div>
                </div>
            </td>
            <td>
                <div class="sig-header">{{ $purchaseRequest->pr_type == 'non_operational' ? 'Manager FAT' : 'Operational Manager' }}</div>
                <div class="sig-body">
                    @if($hasOmApproval)
                        @if($sigOmBase64)
                            <img src="{{ $sigOmBase64 }}" class="sig-image">
                        @endif
                        <div class="sig-name">{{ $omName }}</div>
                        
                    @else
                        <div style="color: #ccc; margin-bottom: 10px;">Pending Approval</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="sig-header">General Manager</div>
                <div class="sig-body">
                    @if($hasGmApproval)
                        @if($sigGmBase64)
                            <img src="{{ $sigGmBase64 }}" class="sig-image">
                        @endif
                        <div class="sig-name">{{ $gmName }}</div>
                        
                    @else
                        <div style="color: #ccc; margin-bottom: 10px;">Pending Approval</div>
                    @endif
                </div>
            </td>

        </tr>
    </table>

    <div style="margin-top: 30px; font-size: 7pt; text-align: center; color: #999;">
        Sistem PR {{ $purchaseRequest->company->name ?? 'PT. Herbatech Innopharma Industry' }} ({{ $purchaseRequest->company->code ?? '' }}) — Printed: {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
