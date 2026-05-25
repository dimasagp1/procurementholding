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
            background-color: #0b1120 !important;
            background-image: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 25%),
                              radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.15), transparent 25%);
            color: #e2e8f0;
            min-height: 100vh;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.4) !important;
            backdrop-filter: blur(24px) !important;
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-left: 1px solid rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255,255,255,0.02) inset !important;
        }

        /* Override components default styles */
        .glass-panel input[type="email"], 
        .glass-panel input[type="password"], 
        .glass-panel input[type="text"] {
            background-color: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #f8fafc !important;
            border-radius: 12px !important;
            padding: 0.875rem 1.25rem !important;
            transition: all 0.3s ease !important;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05) !important;
            width: 100% !important;
        }

        /* Fix padding for inputs with icons */
        .glass-panel input.pl-11 { padding-left: 2.75rem !important; }
        .glass-panel input.pr-10 { padding-right: 2.75rem !important; }
        
        .glass-panel input[type="email"]:focus, 
        .glass-panel input[type="password"]:focus, 
        .glass-panel input[type="text"]:focus {
            background-color: rgba(15, 23, 42, 0.9) !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15), inset 0 2px 4px 0 rgba(0, 0, 0, 0.05) !important;
            outline: none !important;
            color: #ffffff !important;
        }

        .glass-panel input::placeholder {
            color: #64748b !important;
        }

        .glass-panel button[type="submit"] {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            font-weight: 600 !important;
            padding: 0.875rem 1.5rem !important;
            border-radius: 12px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            text-transform: none !important;
            font-size: 1rem !important;
            letter-spacing: 0.025em !important;
            width: 100% !important;
            justify-content: center !important;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.39) !important;
        }
        
        .glass-panel button[type="submit"]:hover {
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5) !important;
            transform: translateY(-2px) !important;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
        }
        
        .glass-panel button[type="submit"]:active {
            transform: translateY(0) !important;
        }

        /* Smooth floating animation for blobs */
        @keyframes float {
            0% { transform: translateY(0px) scale(1); }
            33% { transform: translateY(-30px) scale(1.05); }
            66% { transform: translateY(15px) scale(0.95); }
            100% { transform: translateY(0px) scale(1); }
        }
        
        .animate-float {
            animation: float 15s ease-in-out infinite;
        }

        .animate-float-delayed {
            animation: float 18s ease-in-out infinite;
            animation-delay: 2s;
        }
        
        /* Custom Checkbox */
        .custom-checkbox {
            appearance: none;
            background-color: rgba(15, 23, 42, 0.6);
            margin: 0;
            font: inherit;
            color: currentColor;
            width: 1.15em;
            height: 1.15em;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 0.25em !important;
            display: grid;
            place-content: center;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .custom-checkbox::before {
            content: "";
            width: 0.65em;
            height: 0.65em;
            transform: scale(0);
            transition: 120ms transform ease-in-out;
            box-shadow: inset 1em 1em white;
            background-color: white;
            transform-origin: center;
            clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
        }

        .custom-checkbox:checked {
            background-color: #3b82f6;
            border-color: #3b82f6 !important;
        }

        .custom-checkbox:checked::before {
            transform: scale(1);
        }
        
        /* Eye Icon */
        .eye-toggle {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
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
        <div class="flex flex-col items-center justify-center mb-10 w-full text-center">
            @if(isset($appLogo) && $appLogo)
                <img src="{{ asset('storage/' . $appLogo) }}" alt="Logo" class="w-24 h-24 object-contain mb-5 drop-shadow-2xl">
            @else
                <x-logo class="w-16 h-16 mb-5" />
            @endif
            
            <h1 class="text-3xl font-bold tracking-tight text-white mb-2" style="text-shadow: 0 2px 10px rgba(0,0,0,0.5);">Purchase Request</h1>
            <p class="text-sm text-blue-300/80 font-medium tracking-wide">Streamlining Your Procurement</p>
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
