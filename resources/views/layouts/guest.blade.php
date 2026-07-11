<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Purchase Request System') }} - Login</title>

    @if(isset($appFavicon) && $appFavicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $appFavicon) }}">
    @endif

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif !important;
            background-color: #0b0f19 !important;
            background-image: radial-gradient(circle at 15% 15%, rgba(59, 130, 246, 0.15), transparent 35%),
                              radial-gradient(circle at 85% 85%, rgba(16, 185, 129, 0.12), transparent 35%);
            color: #f1f5f9;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.01) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255, 255, 255, 0.01) 1px, transparent 1px);
            background-size: 35px 35px;
            pointer-events: none;
            z-index: 0;
        }

        .glass-panel {
            background: rgba(13, 20, 38, 0.45) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.06) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-left: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 24px !important;
            box-shadow: 
                0 30px 60px -15px rgba(0, 0, 0, 0.75),
                0 0 50px -10px rgba(59, 130, 246, 0.1),
                inset 0 1px 0 rgba(255,255,255,0.02) !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-panel:hover {
            border-color: rgba(59, 130, 246, 0.22) !important;
            box-shadow: 
                0 35px 70px -10px rgba(0, 0, 0, 0.8),
                0 0 50px 0px rgba(59, 130, 246, 0.2),
                inset 0 1px 0 rgba(255,255,255,0.05) !important;
        }

        /* Clean inputs (non-floating) */
        .glass-panel input[type="email"], 
        .glass-panel input[type="password"], 
        .glass-panel input[type="text"] {
            background-color: rgba(10, 15, 30, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            border-radius: 12px !important;
            padding: 0.85rem 1rem !important;
            transition: all 0.25s ease-in-out !important;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.1) !important;
            width: 100% !important;
            font-size: 0.925rem !important;
        }

        .glass-panel input.pl-11 { padding-left: 2.75rem !important; }
        .glass-panel input.pr-10 { padding-right: 2.75rem !important; }

        .glass-panel input[type="email"]:focus, 
        .glass-panel input[type="password"]:focus, 
        .glass-panel input[type="text"]:focus {
            background-color: rgba(10, 15, 30, 0.85) !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15), inset 0 2px 4px 0 rgba(0, 0, 0, 0.1) !important;
            outline: none !important;
        }

        .glass-panel input::placeholder {
            color: #475569 !important;
        }

        .glass-panel button[type="submit"] {
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%) !important;
            border: none !important;
            color: white !important;
            font-weight: 600 !important;
            padding: 0.85rem 1.5rem !important;
            border-radius: 12px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            text-transform: none !important;
            font-size: 0.95rem !important;
            letter-spacing: 0.025em !important;
            width: 100% !important;
            justify-content: center !important;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(16, 185, 129, 0.25) !important;
            cursor: pointer;
        }
        
        .glass-panel button[type="submit"]:hover {
            box-shadow: 0 6px 24px rgba(16, 185, 129, 0.45) !important;
            transform: translateY(-2px) !important;
            background: linear-gradient(135deg, #2563eb 0%, #059669 100%) !important;
        }
        
        .glass-panel button[type="submit"]:active {
            transform: translateY(0) !important;
        }

        /* Modern Logo Badge Container */
        .logo-badge-container {
            width: 84px;
            height: 84px;
            background-color: #ffffff;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255,255,255,0.1);
            padding: 10px;
            margin-bottom: 20px;
            position: relative;
        }

        /* Pulsing Glow Ring around the logo container */
        .logo-badge-container::before {
            content: "";
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 24px;
            background: linear-gradient(135deg, #3b82f6, #10b981);
            z-index: -1;
            opacity: 0.4;
            filter: blur(4px);
            animation: pulse-ring 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse-ring {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.05); opacity: 0.6; }
        }

        /* Smooth floating animation for blobs */
        @keyframes float {
            0% { transform: translateY(0px) scale(1); }
            33% { transform: translateY(-20px) scale(1.03); }
            66% { transform: translateY(10px) scale(0.97); }
            100% { transform: translateY(0px) scale(1); }
        }
        
        .animate-float {
            animation: float 14s ease-in-out infinite;
        }

        .animate-float-delayed {
            animation: float 16s ease-in-out infinite;
            animation-delay: 2.5s;
        }
        
        /* Eye Icon */
        .eye-toggle {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #475569;
            cursor: pointer;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 0 0.5rem;
            background: transparent;
            border: none;
            outline: none;
        }
        .eye-toggle:hover {
            color: #94a3b8;
        }
    </style>
</head>
<body class="antialiased flex items-center justify-center relative overflow-hidden selection:bg-blue-500 selection:text-white">

    <!-- Decorative Background Elements -->
    <div class="absolute top-[-10%] left-[-10%] w-[40vw] h-[40vw] max-w-[600px] max-h-[600px] rounded-full bg-blue-600/20 mix-blend-screen filter blur-[100px] animate-float pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[35vw] h-[35vw] max-w-[500px] max-h-[500px] rounded-full bg-purple-600/20 mix-blend-screen filter blur-[100px] animate-float-delayed pointer-events-none"></div>
    <div class="absolute top-[40%] right-[20%] w-[20vw] h-[20vw] max-w-[300px] max-h-[300px] rounded-full bg-teal-500/10 mix-blend-screen filter blur-[80px] animate-float pointer-events-none" style="animation-delay: 5s"></div>

    <!-- Main Login Card -->
    <div class="w-full sm:max-w-[440px] px-8 py-10 glass-panel sm:rounded-3xl relative z-10 transition-all duration-300 m-4">
        
        <!-- Header & Branding -->
        <div class="flex flex-col items-center justify-center mb-8 w-full text-center">
            <div class="logo-badge-container">
                @if(isset($appLogo) && $appLogo)
                    <img src="{{ asset('storage/' . $appLogo) }}" alt="Logo" class="max-w-full max-h-full object-contain">
                @else
                    <x-logo class="w-10 h-10 text-slate-800" />
                @endif
            </div>
            
            <h1 class="text-3xl font-bold tracking-tight text-white mb-1.5" style="text-shadow: 0 2px 12px rgba(0,0,0,0.4);">Purchase Request</h1>
            <p class="text-sm text-slate-400 font-medium tracking-wide">Erhanesia Procurement Platform</p>
        </div>

        <!-- Auth Content Slot -->
        <div class="w-full">
            {{ $slot }}
        </div>

        <!-- Footer / Copyright -->
        <div class="mt-10 text-center text-xs text-slate-500 font-medium flex flex-col gap-1">
            <span>&copy; {{ date('Y') }} PT Herbatech Innopharma Industry</span>
            <span>All rights reserved.</span>
        </div>
    </div>
    
</body>
</html>
