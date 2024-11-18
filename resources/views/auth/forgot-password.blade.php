<x-guest-layout>
    <div class="mb-4 text-sm text-dark">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>

        <div class="mt-6 text-center">
            @if (Route::has('login'))
                <p class="text-sm text-gray-600">
                    {{ __('Remember your password?') }}
                    <a class="text-primary underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                        href="{{ route('login') }}">
                        {{ __('Login') }}
                    </a>
                </p>
            @endif
        </div>
    </form>
</x-guest-layout>
