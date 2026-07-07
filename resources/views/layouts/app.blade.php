<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $appName ?? config('app.name', 'PR System') }}</title>

    @if(isset($appFavicon) && $appFavicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $appFavicon) }}">
    @endif

    <!-- Google Fonts for Premium Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- TomSelect -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <style>
        /* Fix Tailwind & Bootstrap Conflict */
        .collapse.show { visibility: visible !important; }
        @media (min-width: 992px) {
            .navbar-expand-lg .navbar-collapse {
                visibility: visible !important;
                display: flex !important;
            }
        }

        /* ===== MOBILE NAVBAR FIX ===== */
        @media (max-width: 991.98px) {
            /* Allow brand + toggler to sit on one row, collapse wraps below */
            .glass-navbar .container-fluid {
                flex-wrap: wrap !important;
            }
            /* Collapsed menu full-width panel below navbar */
            #navbarCollapse {
                width: 100%;
                order: 99;
                background: rgba(13, 20, 38, 0.98);
                border-top: 1px solid rgba(255,255,255,0.07);
                padding: 0.25rem 0 0.5rem 0;
                max-height: 80vh;
                overflow-y: auto;
            }
            /* Nav links stack vertically */
            #navbarCollapse .navbar-nav {
                flex-direction: column !important;
                width: 100%;
                flex-wrap: wrap !important;
            }
            #navbarCollapse .navbar-nav .nav-link {
                padding: 0.65rem 1.25rem !important;
                white-space: normal !important;
            }
            /* Dropdowns open inline (no absolute positioning off-screen) */
            #navbarCollapse .dropdown-menu {
                position: static !important;
                float: none !important;
                box-shadow: none !important;
                border: none !important;
                background: rgba(255,255,255,0.04) !important;
                border-radius: 8px !important;
                margin: 0.1rem 1rem 0.3rem !important;
                padding: 0.25rem 0 !important;
            }
            /* ml-auto has no meaning vertically */
            #navbarCollapse .ml-auto {
                margin-left: 0 !important;
            }
            /* Hide desktop-only right bar on mobile */
            #navbarCollapse .d-none.d-lg-flex {
                display: none !important;
            }
            /* Toggler stays right */
            .glass-navbar .navbar-toggler {
                margin-left: auto;
            }
        }
        /* Desktop: keep single row */
        @media (min-width: 992px) {
            .glass-navbar .container-fluid {
                flex-wrap: nowrap !important;
            }
        }
        /* Hide mobile body scroll caused by old nowrap */
        body { overflow-x: hidden; }

        /* ==========================================================
           GLOBAL MOBILE RESPONSIVE — applies to ALL views
           ========================================================== */
        @media (max-width: 767.98px) {

            /* --- Page Header --- */
            .content-header { padding: 1rem 0 0.5rem !important; }
            .content-header h1 { font-size: 1.3rem !important; }
            .content-header .breadcrumb { display: none; }

            /* --- Content padding --- */
            .content { padding: 0 !important; }
            .container-fluid.px-4 { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }

            /* --- Cards --- */
            .card { border-radius: 10px !important; margin-bottom: 1rem !important; }
            .card-header { padding: 0.75rem 1rem !important; }
            .card-body { padding: 0.75rem !important; }
            .card-header .d-flex { flex-wrap: wrap; gap: 0.5rem; }
            .card-header h3, .card-header h5 { font-size: 0.95rem !important; }

            /* ============================================================
               STACKED TABLE — transforms .table-stack into card rows
               Add class="table-stack" to any <table> to enable this
               Add data-label="Column Name" to each <td>
               ============================================================ */
            .table-stack { border: none !important; background: transparent !important; }
            .table-stack thead { display: none !important; }
            .table-stack tbody, .table-stack tr, .table-stack td { display: block !important; }

            /* Each row becomes a card */
            .table-stack tbody tr {
                background: rgba(255,255,255,0.03) !important;
                border: 1px solid rgba(255,255,255,0.07) !important;
                border-radius: 10px !important;
                margin-bottom: 0.65rem !important;
                padding: 0.25rem 0 !important;
                overflow: hidden;
            }
            .table-stack tbody tr:hover {
                background: rgba(59,130,246,0.06) !important;
                border-color: rgba(59,130,246,0.2) !important;
            }

            /* Each cell = one row: label left, value right */
            .table-stack tbody td {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                padding: 0.45rem 0.85rem !important;
                font-size: 0.82rem !important;
                border: none !important;
                border-bottom: 1px solid rgba(255,255,255,0.04) !important;
                white-space: normal !important;
                min-height: unset !important;
                gap: 0.5rem;
            }
            .table-stack tbody td:last-child { border-bottom: none !important; }

            /* Label (from data-label attribute) */
            .table-stack tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 0.68rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #64748b;
                flex-shrink: 0;
                min-width: 90px;
                padding-top: 2px;
            }

            /* Value side */
            .table-stack tbody td > * { text-align: right; }
            .table-stack tbody td .badge { text-align: center; }

            /* Actions cell — full width, centered */
            .table-stack tbody td.td-actions {
                justify-content: center !important;
                gap: 0.4rem;
            }
            .table-stack tbody td.td-actions::before { display: none; }

            /* Light mode adjustments */
            body.light-mode .table-stack tbody tr {
                background: #ffffff !important;
                border-color: #e2e8f0 !important;
            }
            body.light-mode .table-stack tbody td {
                border-bottom-color: #f1f5f9 !important;
            }
            body.light-mode .table-stack tbody td::before { color: #94a3b8; }

            /* Nested sub-table inside expanded row */
            .table-stack .sub-table { border-radius: 8px; overflow: hidden; }
            .table-stack .sub-table thead { display: none !important; }
            .table-stack .sub-table tr { display: flex !important; flex-wrap: wrap; border: none !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important; border-radius: 0 !important; margin: 0 !important; padding: 0.4rem 0.5rem !important; }
            .table-stack .sub-table td { display: inline-flex !important; border: none !important; padding: 0.15rem 0.4rem !important; font-size: 0.75rem !important; white-space: normal !important; }
            .table-stack .sub-table td::before { display: none; }

            /* === EXPAND / ACCORDION ROW === */
            /* The tr-expand row spans full width, not treated as a stacked card */
            .table-stack tbody tr.tr-expand {
                display: block !important;
                background: transparent !important;
                border: none !important;
                border-radius: 0 !important;
                margin-bottom: 0 !important;
                padding: 0 !important;
            }
            .table-stack tbody tr.tr-expand > td {
                display: block !important;
                border: none !important;
                padding: 0 !important;
                background: transparent !important;
            }
            .table-stack tbody tr.tr-expand > td::before { display: none !important; }

            /* Detail panel full-width card */
            .detail-panel-inner {
                padding: 0.75rem !important;
                margin: 0 0 0.5rem 0 !important;
                border-radius: 10px !important;
            }

            /* "Lihat Detail Penuh PR" button — full width on mobile */
            .detail-panel-inner .btn-block {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            /* --- Stat/Summary Cards (dashboard grid) --- */
            .row .col-lg-3, .row .col-md-3, .row .col-md-4 { margin-bottom: 0.75rem; }
            .small-box { border-radius: 10px !important; }
            .small-box .inner h3 { font-size: 1.6rem !important; }

            /* --- Buttons --- */
            .btn { font-size: 0.78rem !important; padding: 0.35rem 0.65rem !important; }
            .btn-group { flex-wrap: wrap; gap: 4px; }
            .table .btn-sm, .table-stack .btn-sm { padding: 0.25rem 0.5rem !important; }

            /* --- Filter/Search bar row --- */
            .filter-row, .filter-bar { flex-direction: column !important; gap: 0.5rem !important; }
            .filter-row .form-control, .filter-bar .form-control { width: 100% !important; }
            .filter-row .btn, .filter-bar .btn { width: 100% !important; }

            /* --- Forms --- */
            .form-group label { font-size: 0.82rem !important; }
            .form-control { font-size: 0.85rem !important; }
            .row .col-md-6, .row .col-md-4, .row .col-md-3, .row .col-md-8 {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            /* --- Badges --- */
            .badge { font-size: 0.68rem !important; }

            /* --- Modals --- */
            .modal-dialog { margin: 0.5rem !important; max-width: calc(100vw - 1rem) !important; }
            .modal-content { border-radius: 12px !important; }
            .modal-header { padding: 0.75rem 1rem !important; }
            .modal-body { padding: 0.75rem !important; }
            .modal-footer { padding: 0.5rem 0.75rem !important; flex-wrap: wrap; gap: 0.5rem; }
            .modal-footer .btn { flex: 1 1 auto; }

            /* --- Pagination --- */
            .pagination { flex-wrap: wrap; gap: 2px; }
            .pagination .page-link { padding: 0.3rem 0.55rem !important; font-size: 0.78rem !important; }

            /* --- Alerts --- */
            .alert { font-size: 0.82rem !important; padding: 0.65rem 0.9rem !important; }

            /* --- Section headers --- */
            .section-header { flex-direction: column !important; align-items: flex-start !important; gap: 0.5rem !important; }

            /* --- Footer --- */
            .main-footer { padding: 0.75rem 1rem !important; font-size: 0.78rem !important; }

            /* --- Hide non-essential elements on mobile --- */
            .hide-mobile { display: none !important; }
        }

        /* Extra small: phones < 480px */
        @media (max-width: 479.98px) {
            .content-header h1 { font-size: 1.1rem !important; }
            .card-body { padding: 0.5rem !important; }
            .modal-dialog { margin: 0.25rem !important; }
            .table-stack tbody td { font-size: 0.78rem !important; padding: 0.4rem 0.65rem !important; }
            .table-stack tbody td::before { min-width: 75px; font-size: 0.63rem; }
        }

        body, font-family {
            font-family: 'Inter', sans-serif !important;
        }

        @supports (padding: max(0px)) {
            .content-wrapper {
                padding-bottom: env(safe-area-inset-bottom);
            }
        }

        /* --- MODERN ELEGANT DARK/BLUE THEME OVERRIDES --- */
        body.dark-mode {
            background-color: #0f172a !important; /* Tailwind slate-900 */
            color: #f8fafc !important;
            letter-spacing: 0.2px;
        }
        
        /* Navbar Glassmorphism */
        .glass-navbar {
            background-color: rgba(15, 23, 42, 0.75) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            z-index: 1050;
            min-height: 60px;
        }
        /* Prevent navbar from wrapping at any screen size on desktop */
        .glass-navbar .container-fluid {
            flex-wrap: nowrap !important;
            align-items: center !important;
        }
        .glass-navbar .navbar-collapse {
            flex-grow: 0 !important; /* Don't stretch to full width */
        }
        /* Brand */
        .dark-mode .navbar-brand {
            color: #ffffff !important;
            font-weight: 700;
            white-space: nowrap;
            flex-shrink: 0;
        }
        /* Left Nav Links — compact padding */
        .dark-mode .navbar-nav .nav-link {
            color: #cbd5e1 !important;
            font-weight: 500;
            padding: 0.4rem 0.65rem !important;
            border-radius: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
            font-size: 0.875rem;
        }
        .dark-mode .navbar-nav .nav-link:hover,
        .dark-mode .navbar-nav .nav-link.active {
            color: #3b82f6 !important;
            background-color: rgba(59, 130, 246, 0.1) !important;
        }
        
        /* Content Wrapper */
        .dark-mode .content-wrapper, .dark-mode .main-footer {
            background-color: transparent !important;
            border-top: none !important;
        }

        /* Cards & Panels */
        .dark-mode .card,
        .dark-mode .info-box,
        .dark-mode .small-box {
            background-color: #1e293b !important; /* Tailwind slate-800 */
            border: 1px solid rgba(255,255,255,0.05) !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.1) !important;
            color: #f1f5f9 !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dark-mode .card-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            background-color: transparent !important;
            padding: 1.25rem 1.5rem;
        }

        /* Typography & Tailwind Overrides for Dark Mode */
        .dark-mode h1, .dark-mode h2, .dark-mode h3, 
        .dark-mode h4, .dark-mode h5, .dark-mode h6,
        .dark-mode .text-dark, .dark-mode .text-body,
        .dark-mode .text-gray-900, .dark-mode .text-gray-800, .dark-mode .text-gray-700 {
            color: #f8fafc !important;
        }
        .dark-mode label, 
        .dark-mode .form-group label,
        .dark-mode .text-gray-600, .dark-mode .text-gray-500 {
            color: #cbd5e1 !important;
            font-weight: 500;
        }

        /* Essential Blue Accents */
        .text-primary, .text-info {
            color: #3b82f6 !important;
        }
        .dark-mode .bg-primary, 
        .dark-mode .btn-primary {
            background-color: #3b82f6 !important;
            border-color: #3b82f6 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
        }
        .dark-mode .btn-primary:hover {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.5);
        }

        /* Buttons Core */
        .dark-mode .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.2s ease;
            letter-spacing: 0.3px;
        }
        
        /* Tables */
        .dark-mode .table {
            color: #e2e8f0;
        }
        .dark-mode .table thead th {
            border-bottom: 1px solid rgba(255,255,255,0.08) !important;
            border-top: none !important;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 1rem;
        }
        .dark-mode .table td, .dark-mode .table th {
            border-color: rgba(255,255,255,0.05) !important;
            vertical-align: middle;
            padding: 1rem;
        }
        .dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.015) !important;
        }
        .dark-mode .table-hover tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05) !important;
        }
        
        /* Forms & Inputs */
        .dark-mode .form-control,
        .dark-mode .custom-select,
        .dark-mode input[type="text"],
        .dark-mode input[type="email"],
        .dark-mode input[type="password"],
        .dark-mode input[type="number"],
        .dark-mode input[type="file"],
        .dark-mode textarea {
            background-color: #0f172a !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #f8fafc !important;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            height: auto !important;
            line-height: 1.5;
        }
        .dark-mode .form-control:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
        }
        
        /* Status Badges */
        .dark-mode .badge {
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: 600;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
        }
        .dark-mode .badge-success { background-color: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; border: 1px solid rgba(16,185,129,0.2) !important; }
        .dark-mode .badge-warning { background-color: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; border: 1px solid rgba(245,158,11,0.2) !important; }
        .dark-mode .badge-info    { background-color: rgba(56, 189, 248, 0.15) !important; color: #38bdf8 !important; border: 1px solid rgba(56,189,248,0.2) !important; }
        .dark-mode .badge-danger  { background-color: rgba(239, 68, 68, 0.15) !important; color: #f87171 !important; border: 1px solid rgba(239,68,68,0.2) !important; }

        /* Dropdown Menus */
        .dark-mode .dropdown-menu {
            background-color: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            padding: 0.5rem;
        }
        .dark-mode .dropdown-item {
            color: #cbd5e1 !important;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin-bottom: 2px;
            transition: all 0.2s;
        }
        .dark-mode .dropdown-item:hover, .dark-mode .dropdown-item:focus {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: #3b82f6 !important;
        }
        .dark-mode .dropdown-divider {
            border-top: 1px solid rgba(255,255,255,0.05);
            margin: 0.5rem 0;
        }

        .dark-mode .text-muted { color: #64748b !important; }

        /* ================================================ */
        /* ========== LIGHT MODE OVERRIDES ================ */
        /* ================================================ */
        body.light-mode {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }

        /* Navbar Light */
        body.light-mode .glass-navbar {
            background-color: rgba(255,255,255,0.9) !important;
            border-bottom: 1px solid rgba(0,0,0,0.08) !important;
            backdrop-filter: blur(12px);
        }
        body.light-mode .navbar-brand, body.light-mode .navbar-brand span {
            color: #0f172a !important;
        }
        body.light-mode .navbar-nav .nav-link {
            color: #475569 !important;
            padding: 0.4rem 0.65rem !important;
            font-size: 0.875rem;
        }
        body.light-mode .navbar-nav .nav-link:hover,
        body.light-mode .navbar-nav .nav-link.active {
            color: #3b82f6 !important;
            background-color: rgba(59, 130, 246, 0.08) !important;
        }
        body.light-mode .navbar-toggler {
            border-color: rgba(0,0,0,0.15) !important;
        }
        body.light-mode .navbar-toggler-icon {
            filter: invert(1) brightness(0);
        }

        /* Content Wrapper */
        body.light-mode .content-wrapper,
        body.light-mode .main-footer {
            background-color: transparent !important;
            border-top: 1px solid #e2e8f0 !important;
        }

        /* Cards */
        body.light-mode .card,
        body.light-mode .info-box {
            background-color: #ffffff !important;
            border: 1px solid rgba(0,0,0,0.06) !important;
            color: #0f172a !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06) !important;
        }
        body.light-mode .card-header {
            border-bottom: 1px solid rgba(0,0,0,0.06) !important;
        }

        /* Typography */
        body.light-mode h1, body.light-mode h2, body.light-mode h3,
        body.light-mode h4, body.light-mode h5, body.light-mode h6,
        body.light-mode .text-dark, body.light-mode .text-body,
        body.light-mode .text-gray-900, body.light-mode .text-gray-800,
        body.light-mode .text-gray-700, body.light-mode .text-white {
            color: #0f172a !important;
        }
        body.light-mode label,
        body.light-mode .form-group label,
        body.light-mode .text-gray-600, body.light-mode .text-gray-500 {
            color: #475569 !important;
        }
        body.light-mode .text-muted { color: #94a3b8 !important; }

        /* Tables */
        body.light-mode .table { color: #1e293b; }
        body.light-mode .table thead th {
            color: #64748b;
            border-bottom: 2px solid #e2e8f0 !important;
        }
        body.light-mode .table td, body.light-mode .table th {
            border-color: #f1f5f9 !important;
        }
        body.light-mode .table-hover tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.04) !important;
        }

        /* Forms */
        body.light-mode .form-control,
        body.light-mode .custom-select,
        body.light-mode input[type="text"],
        body.light-mode input[type="email"],
        body.light-mode input[type="number"],
        body.light-mode textarea {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
        }
        body.light-mode .form-control:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15) !important;
        }
        body.light-mode .input-group-text {
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #64748b !important;
        }

        /* Nav Pills */
        body.light-mode .nav-pills { background-color: rgba(0,0,0,0.04) !important; }
        body.light-mode .nav-pills .nav-link:not(.active) { color: #64748b !important; }

        /* Badges */
        body.light-mode .badge-success { background-color: #d1fae5 !important; color: #065f46 !important; border: 1px solid #6ee7b7 !important; }
        body.light-mode .badge-warning { background-color: #fef3c7 !important; color: #92400e !important; border: 1px solid #fcd34d !important; }
        body.light-mode .badge-info    { background-color: #e0f2fe !important; color: #0369a1 !important; border: 1px solid #7dd3fc !important; }
        body.light-mode .badge-danger  { background-color: #fee2e2 !important; color: #991b1b !important; border: 1px solid #fca5a5 !important; }
        body.light-mode .badge-secondary { background-color: #f1f5f9 !important; color: #475569 !important; border: 1px solid #cbd5e1 !important; }

        /* Dropdown Menus */
        body.light-mode .dropdown-menu {
            background-color: #ffffff !important;
            border: 1px solid rgba(0,0,0,0.1) !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
        }
        body.light-mode .dropdown-item { color: #1e293b !important; }
        body.light-mode .dropdown-item:hover, body.light-mode .dropdown-item:focus {
            background-color: #f1f5f9 !important;
            color: #3b82f6 !important;
        }
        body.light-mode .dropdown-divider { border-top: 1px solid #e2e8f0; }

        /* User avatar area in navbar */
        body.light-mode .nav-item .nav-link[style*="rgba(255"] {
            background: rgba(0,0,0,0.05) !important;
            border: 1px solid rgba(0,0,0,0.1) !important;
        }
        body.light-mode .nav-item .nav-link .text-sm { color: #0f172a !important; }

        /* Footer */
        body.light-mode .main-footer { color: #64748b !important; }

        /* Page Title */
        body.light-mode .content-header h1 { color: #0f172a !important; }

        /* Accordion expanded area */
        body.light-mode .collapse .card { background-color: #f8fafc !important; }

        /* ================================================ */
        /* === COMPREHENSIVE TEXT COMPATIBILITY OVERRIDES == */
        /* ================================================ */

        /* Generic text color classes */
        body.light-mode .text-white,
        body.light-mode .text-light { color: #0f172a !important; }
        body.light-mode .text-secondary { color: #64748b !important; }
        body.light-mode .text-success { color: #059669 !important; }
        body.light-mode .text-danger  { color: #dc2626 !important; }
        body.light-mode .text-warning { color: #d97706 !important; }
        body.light-mode .text-info    { color: #0284c7 !important; }
        body.light-mode .text-primary { color: #2563eb !important; }

        /* Backgrounds used across cards/panels */
        body.light-mode [style*="background-color: #222630"],
        body.light-mode [style*="background-color: #1e293b"],
        body.light-mode [style*="background-color: #1a1d24"],
        body.light-mode [style*="background-color: #0f172a"],
        body.light-mode [style*="background-color: rgba(0, 0, 0"] {
            background-color: #ffffff !important;
            color: #0f172a !important;
        }
        body.light-mode [style*="background: rgba(0,0,0,0.15)"],
        body.light-mode [style*="background-color: rgba(255,255,255,0.02)"],
        body.light-mode [style*="background-color: rgba(255,255,255,0.05)"] {
            background-color: #f8fafc !important;
        }

        /* Modal dialogs — override dark inline styles */
        body.light-mode .modal-content {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid rgba(0,0,0,0.1) !important;
        }
        body.light-mode .modal-header {
            border-bottom: 1px solid #e2e8f0 !important;
            background-color: #f8fafc !important;
        }
        body.light-mode .modal-footer {
            border-top: 1px solid #e2e8f0 !important;
            background-color: #f8fafc !important;
        }
        body.light-mode .modal-title { color: #0f172a !important; }
        body.light-mode .modal-body  { color: #0f172a !important; }
        body.light-mode .close { color: #475569 !important; opacity: 0.8; }
        body.light-mode .close:hover { color: #0f172a !important; }

        /* Inline textarea / input inside modals */
        body.light-mode .modal textarea[style],
        body.light-mode .modal input[style] {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
        }

        /* Chat bubbles (show.blade PR notes) */
        body.light-mode .chat-left {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
            border: 1px solid #e2e8f0 !important;
        }
        body.light-mode .chat-right {
            background-color: #dbeafe !important;
            color: #1e3a8a !important;
            border: 1px solid #bfdbfe !important;
        }
        body.light-mode .chat-name,
        body.light-mode .chat-time { color: inherit !important; }

        /* Small text / helper text */
        body.light-mode small,
        body.light-mode .small,
        body.light-mode .text-xs,
        body.light-mode .text-sm { color: #475569 !important; }

        /* Bold / strong text */
        body.light-mode strong,
        body.light-mode b { color: #0f172a !important; }

        /* Paragraphs inside cards */
        body.light-mode .card p,
        body.light-mode .card span:not(.badge) { color: #1e293b; }

        /* Section/stat labels in dashboard */
        body.light-mode .info-box-text,
        body.light-mode .info-box-number { color: #0f172a !important; }

        /* Borders that use rgba white */
        body.light-mode [style*="border-bottom: 1px solid rgba(255,255,255"] {
            border-bottom: 1px solid #e2e8f0 !important;
        }
        body.light-mode [style*="border-top: 1px solid rgba(255,255,255"] {
            border-top: 1px solid #e2e8f0 !important;
        }
        body.light-mode [style*="border: 1px solid rgba(255,255,255"] {
            border-color: rgba(0,0,0,0.1) !important;
        }

        /* Inline white color overrides */
        body.light-mode [style*="color: white"],
        body.light-mode [style*="color: #ffffff"],
        body.light-mode [style*="color: #f8fafc"],
        body.light-mode [style*="color: #e2e8f0"],
        body.light-mode [style*="color: #cbd5e1"],
        body.light-mode [style*="color: #f1f5f9"] {
            color: #1e293b !important;
        }

        /* Inline opacity text on dark bg */
        body.light-mode [style*="opacity: 0.75"],
        body.light-mode [style*="opacity:.75"] { opacity: 1 !important; }

        /* Select dropdowns */
        body.light-mode select.form-control option {
            background-color: #ffffff;
            color: #0f172a;
        }

        /* TomSelect (custom select) */
        body.light-mode .ts-control,
        body.light-mode .ts-dropdown {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        body.light-mode .ts-dropdown .option { color: #0f172a !important; }
        body.light-mode .ts-dropdown .option:hover { background-color: #eff6ff !important; }

        /* Pagination */
        body.light-mode .page-item .page-link {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            color: #3b82f6 !important;
        }
        body.light-mode .page-item.active .page-link {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
        }
        body.light-mode .page-item.disabled .page-link { color: #94a3b8 !important; }

        /* Alert boxes */
        body.light-mode .alert { border-radius: 10px; }
        body.light-mode .alert-success { background-color: #d1fae5 !important; color: #065f46 !important; border-color: #6ee7b7 !important; }
        body.light-mode .alert-danger  { background-color: #fee2e2 !important; color: #991b1b !important; border-color: #fca5a5 !important; }
        body.light-mode .alert-warning { background-color: #fef3c7 !important; color: #92400e !important; border-color: #fcd34d !important; }
        body.light-mode .alert-info    { background-color: #e0f2fe !important; color: #0369a1 !important; border-color: #7dd3fc !important; }

        /* input-group addon */
        body.light-mode .input-group-append .btn { border-color: #cbd5e1 !important; }

        /* Button variants in light */
        body.light-mode .btn-outline-warning { border-color: #f59e0b !important; color: #92400e !important; }
        body.light-mode .btn-outline-warning:hover { background-color: #fef3c7 !important; }
        body.light-mode .btn-secondary { background-color: #e2e8f0 !important; color: #1e293b !important; border-color: #cbd5e1 !important; }
        body.light-mode .btn-warning  { background-color: #f59e0b !important; color: #ffffff !important; }
        body.light-mode .btn-danger   { background-color: #dc2626 !important; color: #ffffff !important; }
        body.light-mode .btn-success  { background-color: #059669 !important; color: #ffffff !important; }
        body.light-mode .btn-info     { background-color: #0284c7 !important; color: #ffffff !important; }

        /* Table text that bypasses .table selector */
        body.light-mode td, body.light-mode th { color: #1e293b; }
        body.light-mode td strong, body.light-mode td b { color: #0f172a !important; }

        /* Notification dropdown header override */
        body.light-mode .dropdown-header.bg-light { background-color: #f1f5f9 !important; color: #0f172a !important; }

        /* Card body expandable accordion rows */
        body.light-mode .collapse .card-body { background-color: #f8fafc !important; color: #1e293b !important; }
        body.light-mode .collapse table td { color: #1e293b !important; }
        body.light-mode .collapse table th { color: #64748b !important; }
        /* Hide desktop-only right bar on mobile (already inside collapse) */
        body { overflow-x: hidden; }

        /* ===== MOBILE OFF-CANVAS DRAWER ===== */
        /* Hide the standard desktop navbar on mobile */
        @media (max-width: 991.98px) {
            .glass-navbar { display: none !important; }
        }

        /* Mobile top bar */
        #mobile-topbar {
            display: none;
            position: sticky;
            top: 0;
            z-index: 1051;
            width: 100%;
            height: 56px;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.07);
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
        }
        @media (max-width: 991.98px) {
            #mobile-topbar { display: flex !important; }
        }

        /* Drawer overlay */
        #mobile-drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1060;
            backdrop-filter: blur(2px);
        }
        #mobile-drawer-overlay.show { display: block; }

        /* Drawer panel */
        #mobile-drawer {
            position: fixed;
            top: 0;
            right: -100%;
            width: 78vw;
            max-width: 300px;
            height: 100%;
            background: #0f1629;
            z-index: 1061;
            border-left: 1px solid rgba(255,255,255,0.08);
            transition: right 0.3s cubic-bezier(0.4,0,0.2,1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        #mobile-drawer.open { right: 0; }

        /* Drawer header */
        #mobile-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            height: 56px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            flex-shrink: 0;
        }

        /* Drawer menu items */
        #mobile-drawer .drawer-nav-link {
            display: flex;
            align-items: center;
            padding: 0.85rem 1.25rem;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: background 0.15s, color 0.15s;
        }
        #mobile-drawer .drawer-nav-link:hover,
        #mobile-drawer .drawer-nav-link.active {
            background: rgba(59,130,246,0.1);
            color: #3b82f6;
        }
        #mobile-drawer .drawer-nav-link i { width: 20px; margin-right: 0.75rem; opacity: 0.8; }

        /* Drawer sub-items */
        #mobile-drawer .drawer-sub-link {
            display: flex;
            align-items: center;
            padding: 0.65rem 1.25rem 0.65rem 3rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.82rem;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            transition: background 0.15s, color 0.15s;
        }
        #mobile-drawer .drawer-sub-link:hover { color: #3b82f6; background: rgba(59,130,246,0.07); }
        #mobile-drawer .drawer-sub-link i { width: 18px; margin-right: 0.5rem; }

        /* Drawer section label */
        #mobile-drawer .drawer-section-label {
            padding: 0.5rem 1.25rem 0.25rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
        }

        /* Drawer footer */
        #mobile-drawer-footer {
            margin-top: auto;
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.07);
        }

        /* Light mode adjustments for drawer */
        body.light-mode #mobile-topbar {
            background: rgba(255,255,255,0.92) !important;
            border-bottom: 1px solid rgba(0,0,0,0.08) !important;
        }
        body.light-mode #mobile-drawer {
            background: #f8fafc;
            border-left-color: rgba(0,0,0,0.08);
        }
        body.light-mode #mobile-drawer .drawer-nav-link { color: #374151; }
        body.light-mode #mobile-drawer .drawer-nav-link:hover { color: #2563eb; background: rgba(59,130,246,0.08); }
        body.light-mode #mobile-drawer .drawer-sub-link { color: #64748b; }
        body.light-mode #mobile-drawer-header { border-color: rgba(0,0,0,0.07); }
        body.light-mode #mobile-drawer-footer { border-color: rgba(0,0,0,0.07); }
    </style>
</head>

<body class="hold-transition layout-top-nav dark-mode" id="app-body">
<div class="wrapper">

    <!-- ===== MOBILE TOP BAR ===== -->
    <div id="mobile-topbar">
        <!-- Left: User Avatar -->
        <div class="dropdown">
            <a href="#" data-toggle="dropdown" class="d-flex align-items-center" style="text-decoration:none; gap:8px;">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:36px; height:36px; flex-shrink:0;">
                    <i class="fas fa-user text-white" style="font-size:0.8rem;"></i>
                </div>
                <span style="font-size:0.8rem; color:#cbd5e1; font-weight:500; max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ Auth::user()->name }}</span>
            </a>
            <div class="dropdown-menu border-0 shadow mt-2" style="min-width:180px;">
                <div class="px-3 py-2 border-bottom" style="border-color:rgba(255,255,255,0.06)!important;">
                    <div style="font-size:0.8rem; font-weight:600;">{{ Auth::user()->name }}</div>
                    <div style="font-size:0.72rem; color:#3b82f6;">{{ ucfirst(str_replace('_',' ', Auth::user()->getRoleNames()->first())) }}</div>
                </div>
                <a href="{{ route('profile.edit') }}" class="dropdown-item py-2"><i class="fas fa-user-circle mr-2 text-muted"></i> My Profile</a>
                <div class="dropdown-divider m-0"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger py-2"><i class="fas fa-sign-out-alt mr-2"></i> Logout</button>
                </form>
            </div>
        </div>

        <!-- Right: Notification Bell + Theme + Hamburger -->
        <div class="d-flex align-items-center" style="gap:8px;">
            <!-- Bell -->
            <div class="dropdown">
                <a href="#" data-toggle="dropdown" style="position:relative; color:#cbd5e1; padding:6px;">
                    <i class="far fa-bell" style="font-size:1.1rem;"></i>
                    @if(auth()->user()->unreadNotifications->count() > 0)
                    <span class="badge badge-danger" style="position:absolute; top:2px; right:2px; font-size:0.6rem; padding:2px 4px; border-radius:50%;">
                        {{ auth()->user()->unreadNotifications->count() }}
                    </span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-right border-0 shadow" style="min-width:270px; max-width:90vw;">
                    <span class="dropdown-item dropdown-header text-center py-2 font-weight-bold">
                        {{ auth()->user()->unreadNotifications->count() }} Notifications
                    </span>
                    <div class="dropdown-divider m-0"></div>
                    <div style="max-height:240px; overflow-y:auto;">
                        @forelse(auth()->user()->unreadNotifications as $n)
                            <a href="{{ route('notifications.mark-as-read', $n->id) }}" class="dropdown-item py-2">
                                <p class="mb-0 small" style="white-space:normal; line-height:1.4;">{{ $n->data['message'] }}</p>
                                <small class="text-muted"><i class="far fa-clock mr-1"></i>{{ $n->created_at->diffForHumans() }}</small>
                            </a>
                            <div class="dropdown-divider m-0"></div>
                        @empty
                            <div class="dropdown-item text-center text-muted py-3">No notifications</div>
                        @endforelse
                    </div>
                    <a href="{{ route('notifications.index') }}" class="dropdown-item text-center py-2 text-primary font-weight-bold small">See All</a>
                </div>
            </div>

            <!-- Theme Toggle -->
            <button id="theme-toggle-mobile" class="btn btn-sm rounded-pill" style="border:1px solid rgba(255,255,255,0.18); background:rgba(255,255,255,0.07); color:#cbd5e1; padding:0.28rem 0.55rem; height:32px;">
                <i class="fas fa-sun" style="font-size:0.85rem;"></i>
            </button>

            <!-- Hamburger -->
            <button id="mobile-menu-btn" class="btn border-0 p-0" style="background:transparent; color:#cbd5e1; width:36px; height:36px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-bars" style="font-size:1.15rem;"></i>
            </button>
        </div>
    </div>

    <!-- ===== MOBILE DRAWER OVERLAY ===== -->
    <div id="mobile-drawer-overlay"></div>

    <!-- ===== MOBILE DRAWER PANEL ===== -->
    <div id="mobile-drawer">
        <!-- Drawer Header -->
        <div id="mobile-drawer-header">
            <div class="d-flex align-items-center" style="gap:8px;">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px; height:32px; flex-shrink:0;">
                    <i class="fas fa-user text-white" style="font-size:0.75rem;"></i>
                </div>
                <span style="font-size:0.82rem; font-weight:600; color:#e2e8f0;">{{ Auth::user()->name }}</span>
            </div>
            <button id="mobile-drawer-close" class="btn border-0 p-0" style="background:transparent; color:#94a3b8; width:32px; height:32px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                &times;
            </button>
        </div>

        <!-- Drawer Menu -->
        <div class="drawer-menu" style="flex:1;">
            <a href="{{ route('dashboard') }}" class="drawer-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>

            @can('view pr')
            @php
                $rejectedCount = \App\Models\PurchaseRequest::where('user_id', Auth::id())
                    ->whereHas('items', function ($q) {
                        $q->whereIn('status', ['rejected_om', 'rejected_gm', 'rejected_proc']);
                    })->count();

                $returQuery = \App\Models\PrItemDelivery::where('rejected_quantity', '>', 0)
                    ->whereNull('retur_for_delivery_id');
                if (!Auth::user()->hasAnyRole(['superadmin', 'procurement', 'procurement_holding'])) {
                    $returQuery->whereHas('prItem.purchaseRequest', function($q) {
                        $q->where('user_id', Auth::id());
                    });
                }
                $unresolvedReturCount = $returQuery->where(function($q) {
                    $q->whereRaw('rejected_quantity > (SELECT COALESCE(SUM(children.received_quantity), 0) FROM pr_item_deliveries AS children WHERE children.retur_for_delivery_id = pr_item_deliveries.id)');
                })->count();
            @endphp
            <div class="drawer-section-label">Purchase</div>
            @can('create pr')
            <a href="{{ route('purchase-requests.create') }}" class="drawer-sub-link">
                <i class="fas fa-plus-circle text-primary"></i> Create PR
            </a>
            <a href="{{ route('purchase-requests.drafts') }}" class="drawer-sub-link">
                <i class="fas fa-file-alt text-secondary"></i> Draft PRs
            </a>
            @endcan
            @if(Auth::user()->hasAnyRole(['operational_manager','manager_fat','general_manager','superadmin']))
            <a href="{{ route('purchase-requests.approvals') }}" class="drawer-sub-link">
                <i class="fas fa-user-check text-success"></i> Approval Queue
            </a>
            @endif
            <a href="{{ route('purchase-requests.index') }}" class="drawer-sub-link">
                <i class="fas fa-list text-info"></i> All Requests
            </a>
            <a href="{{ route('purchase-requests.rejected') }}" class="drawer-sub-link">
                <i class="fas fa-exclamation-circle text-danger"></i> Needs Revision
                @if($rejectedCount > 0)
                    <span class="badge badge-danger float-right mt-1">{{ $rejectedCount }}</span>
                @endif
            </a>
            <a href="{{ route('purchase-requests.deliveries.rejected') }}" class="drawer-sub-link">
                <i class="fas fa-undo text-warning"></i> Kedatangan Ditolak
                @if($unresolvedReturCount > 0)
                    <span class="badge badge-warning float-right mt-1">{{ $unresolvedReturCount }}</span>
                @endif
            </a>
            @endcan

            @can('manage users')
            <div class="drawer-section-label">Management</div>
            <a href="{{ route('users.index') }}" class="drawer-nav-link">
                <i class="fas fa-users"></i> Users
            </a>
            @endcan

            @can('manage departments')
            <a href="{{ route('departments.index') }}" class="drawer-nav-link">
                <i class="fas fa-building"></i> Departments
            </a>
            @endcan

            @can('view reports')
            <a href="{{ route('reports.index') }}" class="drawer-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Reports
            </a>
            @endcan

            @if(Auth::user()->hasAnyRole(['superadmin', 'manager_fat', 'general_manager', 'operational_manager', 'procurement']))
            <div class="drawer-section-label">Admin</div>
            @if(Auth::user()->hasRole('superadmin'))
            <a href="{{ route('settings.general') }}" class="drawer-nav-link {{ request()->routeIs('settings.general') ? 'active' : '' }}">
                <i class="fas fa-cogs"></i> Settings
            </a>
            <a href="{{ route('uoms.index') }}" class="drawer-sub-link">
                <i class="fas fa-ruler"></i> UOM Management
            </a>
            <a href="{{ route('purposes.index') }}" class="drawer-sub-link">
                <i class="fas fa-bullseye"></i> Purpose Management
            </a>
            <a href="{{ route('master-items.index') }}" class="drawer-sub-link">
                <i class="fas fa-boxes"></i> Master Item
            </a>
            @endif
            <a href="{{ route('settings.finance-budget') }}" class="drawer-sub-link {{ request()->routeIs('settings.finance-budget') ? 'active' : '' }}">
                <i class="fas fa-coins text-success"></i> Finance Budget
            </a>
            <a href="{{ route('staging-pagu.index') }}" class="drawer-sub-link {{ request()->routeIs('staging-pagu.*') ? 'active' : '' }}">
                <i class="fas fa-boxes text-warning"></i> Staging Pengeluaran
            </a>
            @if(Auth::user()->hasAnyRole(['superadmin', 'procurement']))
            <a href="{{ route('settings.odoo-vendors') }}" class="drawer-sub-link {{ request()->routeIs('settings.odoo-vendors') ? 'active' : '' }}">
                <i class="fas fa-address-book text-warning"></i> Odoo Vendors
            </a>
            @endif
            @endif
        </div>

        <!-- Drawer Footer -->
        <div id="mobile-drawer-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger w-100 rounded-pill">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Top Navbar (Desktop only) -->
    <nav class="main-header navbar navbar-expand-lg navbar-dark glass-navbar sticky-top">
        <div class="container-fluid px-3" style="flex-wrap: nowrap; align-items: center;">
            <!-- Brand -->
            <a href="{{ route('dashboard') }}" class="navbar-brand d-flex align-items-center flex-shrink-0 mr-3">
                @if(isset($appLogo) && $appLogo)
                    <img src="{{ asset('storage/' . $appLogo) }}" alt="Logo" class="brand-image rounded-lg mr-2" style="opacity: 1; height: 32px; width: 32px; object-fit: contain;">
                @else
                    <div class="bg-primary rounded-lg d-flex align-items-center justify-content-center mr-2 shadow-sm" style="width: 32px; height: 32px; flex-shrink:0;">
                        <i class="fas fa-layer-group text-white" style="font-size:0.875rem;"></i>
                    </div>
                @endif
                <span class="brand-text font-weight-bold" style="letter-spacing: 1px; font-size: 1rem;">{{ $appName ?? 'PR System' }}</span>
            </a>

            <!-- Hamburger (mobile) -->
            <button class="navbar-toggler order-3 border-0 ml-auto" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Collapsible content -->
            <div class="collapse navbar-collapse order-2" id="navbarCollapse" style="flex-grow: 1;">
                <!-- Left nav links -->
                <ul class="navbar-nav align-items-center" style="flex-wrap: nowrap;">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fas fa-chart-pie mr-1"></i> Dashboard</a>
                    </li>

                    @can('view pr')
                        <li class="nav-item dropdown">
                            <a id="dropdownPR" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle {{ request()->routeIs('purchase-requests.*') ? 'active' : '' }}"><i class="fas fa-shopping-bag mr-1"></i> Purchase Requests</a>
                            <ul aria-labelledby="dropdownPR" class="dropdown-menu border-0 shadow">
                                @can('create pr')
                                    <li><a href="{{ route('purchase-requests.create') }}" class="dropdown-item"><i class="fas fa-plus-circle mr-2 text-primary"></i> Create PR</a></li>
                                    <li><a href="{{ route('purchase-requests.drafts') }}" class="dropdown-item"><i class="fas fa-file-alt mr-2 text-secondary"></i> Draft PRs</a></li>
                                    <div class="dropdown-divider"></div>
                                @endcan

                                @if(Auth::user()->hasAnyRole(['operational_manager', 'manager_fat', 'general_manager', 'superadmin']))
                                    <li><a href="{{ route('purchase-requests.approvals') }}" class="dropdown-item"><i class="fas fa-user-check mr-2 text-success"></i> Approval Queue</a></li>
                                @endif
                                <li><a href="{{ route('purchase-requests.index') }}" class="dropdown-item"><i class="fas fa-list mr-2 text-info"></i> All Requests</a></li>

                                @php
                                    $rejectedCount = \App\Models\PurchaseRequest::where('user_id', Auth::id())
                                        ->whereHas('items', function ($q) {
                                            $q->whereIn('status', ['rejected_om', 'rejected_gm', 'rejected_proc']);
                                        })->count();

                                    $returQuery = \App\Models\PrItemDelivery::where('rejected_quantity', '>', 0)
                                        ->whereNull('retur_for_delivery_id');
                                    if (!Auth::user()->hasAnyRole(['superadmin', 'procurement', 'procurement_holding'])) {
                                        $returQuery->whereHas('prItem.purchaseRequest', function($q) {
                                            $q->where('user_id', Auth::id());
                                        });
                                    }
                                    $unresolvedReturCount = $returQuery->where(function($q) {
                                        $q->whereRaw('rejected_quantity > (SELECT COALESCE(SUM(children.received_quantity), 0) FROM pr_item_deliveries AS children WHERE children.retur_for_delivery_id = pr_item_deliveries.id)');
                                    })->count();
                                @endphp
                                <li>
                                    <a href="{{ route('purchase-requests.rejected') }}" class="dropdown-item">
                                        <i class="fas fa-exclamation-circle mr-2 text-danger"></i> Needs Revision
                                        @if($rejectedCount > 0)
                                            <span class="badge badge-danger float-right mt-1">{{ $rejectedCount }}</span>
                                        @endif
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('purchase-requests.deliveries.rejected') }}" class="dropdown-item">
                                        <i class="fas fa-undo mr-2 text-warning"></i> Kedatangan Ditolak
                                        @if($unresolvedReturCount > 0)
                                            <span class="badge badge-warning float-right mt-1">{{ $unresolvedReturCount }}</span>
                                        @endif
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endcan

                    @can('manage users')
                        <li class="nav-item dropdown">
                            <a id="dropdownUsers" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle {{ request()->routeIs('users.*') ? 'active' : '' }}"><i class="fas fa-users mr-1"></i> Users</a>
                            <ul aria-labelledby="dropdownUsers" class="dropdown-menu border-0 shadow">
                                <li><a href="{{ route('users.index') }}" class="dropdown-item">All Users</a></li>
                                <li><a href="{{ route('users.create') }}" class="dropdown-item">Add User</a></li>
                            </ul>
                        </li>
                    @endcan

                    @can('manage departments')
                        <li class="nav-item dropdown">
                            <a id="dropdownDepts" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle {{ request()->routeIs('departments.*') ? 'active' : '' }}"><i class="fas fa-building mr-1"></i> Departments</a>
                            <ul aria-labelledby="dropdownDepts" class="dropdown-menu border-0 shadow">
                                <li><a href="{{ route('departments.index') }}" class="dropdown-item">All Departments</a></li>
                                <li><a href="{{ route('departments.create') }}" class="dropdown-item">Add Department</a></li>
                            </ul>
                        </li>
                    @endcan

                    @if(Auth::user()->can('view reports') || Auth::user()->hasAnyRole(['superadmin', 'manager_fat', 'general_manager', 'operational_manager', 'procurement']))
                    <li class="nav-item dropdown">
                        <a id="dropdownData" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle {{ request()->routeIs('reports.*') || request()->routeIs('staging-pagu.*') ? 'active' : '' }}">
                            <i class="fas fa-database mr-1"></i> Data
                            @php
                                try {
                                    $navPendingCount = \Illuminate\Support\Facades\DB::connection('fat_db')
                                        ->table('expense_stagings')->where('status', 'pending')->count();
                                } catch (\Exception $e) { $navPendingCount = 0; }
                            @endphp
                            @if($navPendingCount > 0 && Auth::user()->hasAnyRole(['superadmin', 'manager_fat', 'general_manager', 'operational_manager', 'procurement']))
                                <span class="badge badge-warning ml-1">{{ $navPendingCount }}</span>
                            @endif
                        </a>
                        <ul aria-labelledby="dropdownData" class="dropdown-menu border-0 shadow">
                            @can('view reports')
                                <li>
                                    <a href="{{ route('reports.index') }}" class="dropdown-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                                        <i class="fas fa-chart-line mr-2 text-info"></i> Reports
                                    </a>
                                </li>
                            @endcan
                            @if(Auth::user()->hasAnyRole(['superadmin', 'manager_fat', 'general_manager', 'operational_manager', 'procurement']))
                                <li>
                                    <a href="{{ route('staging-pagu.index') }}" class="dropdown-item {{ request()->routeIs('staging-pagu.*') ? 'active' : '' }}">
                                        <i class="fas fa-boxes mr-2 text-warning"></i> Staging Pagu
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    @if(Auth::user()->hasAnyRole(['superadmin', 'manager_fat', 'general_manager', 'operational_manager', 'procurement']))
                        <li class="nav-item dropdown">
                            <a id="dropdownSettings" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle {{ request()->routeIs('settings.*') || request()->routeIs('uoms.*') || request()->routeIs('purposes.*') ? 'active' : '' }}"><i class="fas fa-cogs mr-1"></i> Settings</a>
                            <ul aria-labelledby="dropdownSettings" class="dropdown-menu border-0 shadow">
                                @if(Auth::user()->hasRole('superadmin'))
                                    <li><a href="{{ route('settings.general') }}" class="dropdown-item">General Settings</a></li>
                                    <li><a href="{{ route('uoms.index') }}" class="dropdown-item">UOM Management</a></li>
                                    <li><a href="{{ route('purposes.index') }}" class="dropdown-item">Purpose Management</a></li>
                                    <li><a href="{{ route('master-items.index') }}" class="dropdown-item">Master Item</a></li>
                                    <div class="dropdown-divider"></div>
                                @endif
                                <li><a href="{{ route('settings.finance-budget') }}" class="dropdown-item"><i class="fas fa-coins mr-2 text-success"></i> Finance Budget</a></li>
                                @if(Auth::user()->hasAnyRole(['superadmin', 'procurement']))
                                    <li><a href="{{ route('settings.odoo-vendors') }}" class="dropdown-item"><i class="fas fa-address-book mr-2 text-warning"></i> Odoo Vendors</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif
                </ul>

                <!-- Right navbar links -->
                <ul class="navbar-nav ml-auto align-items-center flex-nowrap" style="gap: 4px;">
                <!-- Dark/Light Mode Toggle -->
                <li class="nav-item d-flex align-items-center">
                    <button id="theme-toggle" title="Toggle Light/Dark Mode"
                            class="btn btn-sm rounded-pill"
                            style="border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.08); color: #cbd5e1; font-size: 0.78rem; font-weight: 500; transition: all 0.25s ease; white-space: nowrap; padding: 0.3rem 0.75rem; height: 32px;">
                        <i class="fas fa-sun mr-1"></i> Light
                    </button>
                </li>
                <!-- Notifications Bell -->
                <li class="nav-item dropdown d-flex align-items-center">
                    <a class="nav-link px-2" data-toggle="dropdown" href="#" style="position: relative;">
                        <i class="far fa-bell" style="font-size: 1.1rem;"></i>
                        <span class="badge badge-danger navbar-badge notification-bubble" style="{{ auth()->user()->unreadNotifications->count() > 0 ? '' : 'display: none;' }}">
                            {{ auth()->user()->unreadNotifications->count() }}
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right shadow-lg border-0" style="min-width: 320px;">
                        <span class="dropdown-item dropdown-header text-center py-3 bg-light text-dark font-weight-bold" style="border-radius: 12px 12px 0 0;">{{ auth()->user()->unreadNotifications->count() }} Notifications</span>
                        <div class="dropdown-divider m-0"></div>
                        <div style="max-height: 350px; overflow-y: auto;">
                            @forelse(auth()->user()->unreadNotifications as $notification)
                                <a href="{{ route('notifications.mark-as-read', $notification->id) }}" class="dropdown-item py-3 text-wrap">
                                    <div class="d-flex">
                                        <div class="mr-3 mt-1">
                                            <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <i class="fas fa-envelope text-primary"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-sm mb-1" style="white-space: normal; line-height: 1.4;">{{ $notification->data['message'] }}</p>
                                            <p class="text-xs text-muted mb-0"><i class="far fa-clock mr-1"></i> {{ $notification->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </a>
                                <div class="dropdown-divider m-0"></div>
                            @empty
                                <div class="dropdown-item text-center text-muted py-4">No new notifications</div>
                            @endforelse
                        </div>
                        <a href="{{ route('notifications.index') }}" class="dropdown-item dropdown-footer text-center py-3 text-primary font-weight-bold" style="border-radius: 0 0 12px 12px;">See All Notifications</a>
                    </div>
                </li>

                <!-- User Dropdown (compact pill) -->
                <li class="nav-item dropdown">
                    <a class="nav-link d-flex align-items-center" data-toggle="dropdown" href="#"
                       style="background: rgba(255,255,255,0.05); border-radius: 50px; padding: 0.25rem 0.4rem 0.25rem 0.75rem; border: 1px solid rgba(255,255,255,0.1); white-space: nowrap;">
                        <!-- Name hidden on small screens -->
                        <div class="d-none d-xl-block text-right mr-2">
                            <div style="font-size:0.8rem; font-weight:600; color:#ffffff; line-height:1.2;">{{ Auth::user()->name }}</div>
                            <div style="font-size:0.7rem; color:#3b82f6; line-height:1.2;">{{ ucfirst(str_replace('_', ' ', Auth::user()->getRoleNames()->first())) }}</div>
                        </div>
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; flex-shrink:0;">
                            <i class="fas fa-user text-white" style="font-size:0.8rem;"></i>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2">
                        <a href="{{ route('profile.edit') }}" class="dropdown-item py-2">
                            <i class="fas fa-user-circle mr-3 text-muted"></i> My Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a href="{{ route('logout') }}" class="dropdown-item py-2 text-danger" onclick="event.preventDefault(); this.closest('form').submit();">
                                <i class="fas fa-sign-out-alt mr-3"></i> Logout
                            </a>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
        </div>
    </nav>
    <!-- /.navbar -->

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header py-4">
            <div class="container-fluid px-4">
                <div class="row mb-2">
                    <div class="col-sm-12">
                        @isset($header)
                            <h1 class="m-0 text-white font-weight-bold" style="font-size: 1.8rem;">{{ $header }}</h1>
                        @else
                            @yield('header')
                        @endisset
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="content">
            <div class="container-fluid px-4 pb-5">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </div>
        </div>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer border-top-0 mt-auto py-4 text-center">
        <div class="container-fluid px-4">
            <div class="text-muted text-sm">
                <strong>Copyright &copy; {{ date('Y') }} {{ $appName ?? 'PR System' }}.</strong> All rights reserved.
                <span class="mx-2">|</span> Designed for Efficiency
            </div>
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<style>
    .notification-bubble {
        position: absolute;
        top: 2px;
        right: 0px;
        border-radius: 50%;
        padding: 0.25em 0.4em;
        font-size: 0.6rem;
        line-height: 1;
        background-color: #ef4444 !important;
        border: 2px solid #0f172a;
    }
</style>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
    // ===== Dark/Light Mode Toggle (runs immediately, before DOMContentLoaded to prevent flash) =====
    (function() {
        const savedTheme = localStorage.getItem('pr-theme') || 'dark';
        const body = document.getElementById('app-body');
        if (savedTheme === 'light') {
            body.classList.remove('dark-mode');
            body.classList.add('light-mode');
        }
    })();
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme toggle init
        const body = document.getElementById('app-body');
        const btn = document.getElementById('theme-toggle');
        const btnMobile = document.getElementById('theme-toggle-mobile');

        function applyTheme(theme) {
            if (theme === 'light') {
                body.classList.remove('dark-mode');
                body.classList.add('light-mode');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-moon mr-1"></i> Dark';
                    btn.style.color = '#475569';
                    btn.style.borderColor = 'rgba(0,0,0,0.15)';
                    btn.style.background = 'rgba(0,0,0,0.05)';
                }
                if (btnMobile) {
                    btnMobile.innerHTML = '<i class="fas fa-moon" style="font-size:0.85rem;"></i>';
                    btnMobile.style.color = '#475569';
                    btnMobile.style.borderColor = 'rgba(0,0,0,0.15)';
                    btnMobile.style.background = 'rgba(0,0,0,0.05)';
                }
            } else {
                body.classList.remove('light-mode');
                body.classList.add('dark-mode');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-sun mr-1"></i> Light';
                    btn.style.color = '#cbd5e1';
                    btn.style.borderColor = 'rgba(255,255,255,0.2)';
                    btn.style.background = 'rgba(255,255,255,0.08)';
                }
                if (btnMobile) {
                    btnMobile.innerHTML = '<i class="fas fa-sun" style="font-size:0.85rem;"></i>';
                    btnMobile.style.color = '#cbd5e1';
                    btnMobile.style.borderColor = 'rgba(255,255,255,0.18)';
                    btnMobile.style.background = 'rgba(255,255,255,0.07)';
                }
            }
            localStorage.setItem('pr-theme', theme);
        }

        // Sync button label with current state
        const currentTheme = localStorage.getItem('pr-theme') || 'dark';
        applyTheme(currentTheme);

        if (btn) btn.addEventListener('click', function() {
            applyTheme(body.classList.contains('dark-mode') ? 'light' : 'dark');
        });
        if (btnMobile) btnMobile.addEventListener('click', function() {
            applyTheme(body.classList.contains('dark-mode') ? 'light' : 'dark');
        });

        // ===== MOBILE DRAWER =====
        const drawer = document.getElementById('mobile-drawer');
        const overlay = document.getElementById('mobile-drawer-overlay');
        const openBtn = document.getElementById('mobile-menu-btn');
        const closeBtn = document.getElementById('mobile-drawer-close');

        function openDrawer() {
            drawer.classList.add('open');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function closeDrawer() {
            drawer.classList.remove('open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }

        if (openBtn) openBtn.addEventListener('click', openDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (overlay) overlay.addEventListener('click', closeDrawer);
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                background: '#1e293b',
                color: '#f8fafc',
                iconColor: '#34d399'
            });
        @endif

        @if(session('error'))
            (function() {
                const errorMsg = "{{ session('error') }}";
                const isOverBudget = /budget|anggaran|pagu|limit|melebihi/i.test(errorMsg);
                if (isOverBudget) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Over Budget!',
                        text: errorMsg,
                        position: 'center',
                        showConfirmButton: true,
                        confirmButtonText: 'Ok',
                        background: '#1e293b',
                        color: '#f8fafc',
                        iconColor: '#f59e0b',
                        customClass: {
                            popup: 'rounded-2xl border border-slate-700'
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: errorMsg,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 5000,
                        timerProgressBar: true,
                        background: '#1e293b',
                        color: '#f8fafc',
                        iconColor: '#f87171'
                    });
                }
            })();
        @endif

        @if($errors->any())
            (function() {
                const errorsHtml = "{!! implode('<br>', $errors->all()) !!}";
                const isOverBudget = /budget|anggaran|pagu|limit|melebihi/i.test(errorsHtml);
                if (isOverBudget) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Over Budget!',
                        html: errorsHtml,
                        position: 'center',
                        showConfirmButton: true,
                        confirmButtonText: 'Ok',
                        background: '#1e293b',
                        color: '#f8fafc',
                        iconColor: '#f59e0b',
                        customClass: {
                            popup: 'rounded-2xl border border-slate-700'
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal!',
                        html: errorsHtml,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 6000,
                        timerProgressBar: true,
                        background: '#1e293b',
                        color: '#f8fafc',
                        iconColor: '#f87171'
                    });
                }
            })();
        @endif

        let currentUnreadCount = {{ auth()->user()->unreadNotifications->count() }};
        
        @if(auth()->user()->unreadNotifications->count() > 0)
            @php
                $latestNotification = auth()->user()->unreadNotifications->first();
            @endphp

            @if(!session('notification_popup_shown_' . $latestNotification->id))
                @php session(['notification_popup_shown_' . $latestNotification->id => true]); @endphp
                showNotificationPopup(@json($latestNotification->data['message']), "{{ route('notifications.mark-as-read', $latestNotification->id) }}");
            @endif
        @endif

        setInterval(function() {
            fetch("{{ route('notifications.check') }}", {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.unread_count > currentUnreadCount && data.latest) {
                        showNotificationPopup(data.latest.message, data.latest.url);
                    }
                    
                    if (data.unread_count != currentUnreadCount) {
                        currentUnreadCount = data.unread_count;
                        const badge = document.querySelector('.navbar-badge');
                        const header = document.querySelector('.dropdown-header');
                        
                        if (badge) {
                            badge.textContent = data.unread_count;
                            badge.style.display = data.unread_count > 0 ? 'inline-block' : 'none';
                        }
                        if (header) {
                            header.textContent = data.unread_count + " Notifications";
                        }
                    }
                })
                .catch(error => console.error('Error checking notifications:', error));
        }, 2000);

        document.querySelectorAll('.form-confirm').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const message = this.getAttribute('data-message') || 'Are you sure?';
                
                Swal.fire({
                    title: 'Konfirmasi',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal',
                    background: '#1e293b',
                    color: '#f8fafc'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });

        function showNotificationPopup(message, url) {
            Swal.fire({
                title: 'Notifikasi Baru',
                text: message,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Lihat Detail',
                cancelButtonText: 'Tutup',
                toast: true,
                position: 'top-end',
                timer: 10000,
                timerProgressBar: true,
                background: '#1e293b',
                color: '#f8fafc',
                iconColor: '#3b82f6',
                confirmButtonColor: '#3b82f6'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    });
</script>



@stack('scripts')

</body>
</html>
