<x-guest-layout>
    <div class="mb-6 text-sm leading-relaxed text-slate-300 bg-blue-500/5 border border-blue-500/10 rounded-xl p-4">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 p-4 font-medium" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-300 mb-2">{{ __('Email Address') }}</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                        <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                    </svg>
                </div>
                <input id="email" class="block w-full pl-11" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your registered email" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-400 text-sm" />
        </div>

        <div class="pt-2 space-y-4">
            <button type="submit" class="w-full flex items-center justify-center gap-2">
                <span>{{ __('Email Password Reset Link') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            
            <div class="text-center">
                <a class="text-sm font-medium text-blue-400 hover:text-blue-300 transition-colors" href="{{ route('login') }}">
                    {{ __('Back to Login') }}
                </a>
            </div>
        </div>
    </form>
</x-guest-layout>
