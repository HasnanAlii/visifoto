<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'VisiFoto') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
                @import "tailwindcss";
            </style>
        @endif
    </head>
    <body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex flex-col justify-between">
        <!-- Navbar -->
        <header class="w-full max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 select-none">
                <x-application-logo class="w-9 h-9 rounded-2xl shadow-sm border border-gray-100 object-contain p-1.5 bg-white" />
                <span class="text-xl font-bold tracking-tight text-gray-900">VisiFoto</span>
            </a>
            
            <div class="flex items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 text-gray-600 hover:text-gray-900 text-xs font-semibold transition-colors">
                            Masuk
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                                Daftar
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </header>

        <!-- Hero Content -->
        <main class="w-full max-w-7xl mx-auto px-6 py-12 md:py-20 flex-1 flex flex-col md:flex-row items-center gap-12">
            <!-- Left: Hero Text -->
            <div class="flex-1 text-center md:text-left">
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium border border-blue-100/50 mb-4">
                    Teknologi Face Recognition
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900 leading-tight">
                    Kelola dan Cari Foto Wajah Anda Secara Instan
                </h1>
                <p class="text-md md:text-lg text-gray-500 mt-4 leading-relaxed max-w-lg">
                    VisiFoto adalah sistem cerdas manajemen galeri foto. Unggah momen Anda, biarkan sistem mendeteksi wajah otomatis, dan cari foto diri Anda dengan sekali klik.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row items-center gap-3 justify-center md:justify-start">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors">
                            Buka Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors">
                            Mulai Sekarang
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-gray-200 hover:border-gray-300 text-gray-700 bg-white hover:bg-gray-50 rounded-xl text-sm font-semibold shadow-sm transition-colors">
                                Buat Akun
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Right: Hero Image Background -->
            <div class="flex-1 w-full flex justify-center items-center">
                <div class="w-full max-w-lg aspect-square bg-cover bg-center border border-gray-200/80 rounded-[2.5rem] shadow-[0_8px_30px_rgba(0,0,0,0.02)]" style="background-image: url('{{ asset('assets/images/face_scan_hero.png') }}')">
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full border-t border-gray-200/50 py-6 text-center text-xs text-gray-400">
            <div class="max-w-7xl mx-auto px-6">
                &copy; {{ date('Y') }} VisiFoto. Hak Cipta Dilindungi.
            </div>
        </footer>
    </body>
</html>
