<x-guest-layout>
    <!-- Card Header -->
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-gray-800">Selamat Datang Kembali</h2>
        <p class="text-sm text-gray-500 mt-1">Silakan masuk untuk mengelola foto Anda</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1.5 w-full rounded-xl" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="Masukan Email"/>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1.5 w-full rounded-xl"
                            type="password"
                            name="password"
                            required autocomplete="current-password" 
                            placeholder="Masukan Password"/>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Kode Unik Folder (Optional) -->
        <div class="mt-4">
            <x-input-label for="folder_code" value="Kode Unik Folder (Opsional)" />
            <x-text-input id="folder_code" class="block mt-1.5 w-full rounded-xl border-gray-300 shadow-sm" type="text" name="folder_code" :value="old('folder_code')" placeholder="Masukan Kode Unik" />
        </div>

        <!-- Remember Me & Forgot Password Row -->
        <div class="flex items-center justify-between mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                <span class="ms-2 text-sm text-gray-500">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-blue-600 hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <!-- Login Button -->
        <div class="mt-6">
            <x-primary-button class="w-full">
                {{ __('Log in') }}
            </x-primary-button>
        </div>

        <!-- Registration Link -->
        @if (Route::has('register'))
            <p class="mt-6 text-center text-sm text-gray-500">
                Belum memiliki akun?
                <a href="{{ route('register') }}" class="font-medium text-blue-600 hover:text-blue-700 hover:underline">
                    Daftar Sekarang
                </a>
            </p>
        @endif
    </form>
</x-guest-layout>
