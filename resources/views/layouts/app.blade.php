<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'VisiFoto') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-800 bg-white overflow-hidden h-screen flex flex-col">
        
        <!-- Top Navigation Bar (Google Drive Style) -->
        @include('layouts.navigation')

        <!-- Main Layout (Sidebar + Content) -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Left Sidebar -->
            @include('layouts.sidebar')

            <!-- Main Content Area -->
            <main class="flex-1 flex flex-col bg-white sm:bg-gray-50 sm:m-2 sm:rounded-2xl sm:border border-gray-200 overflow-y-auto relative shadow-inner">
                
                <!-- Page Heading (Optional) -->
                @isset($header)
                    <header class="bg-transparent pt-4 px-6 pb-2">
                        {{ $header }}
                    </header>
                @endisset
                
                <!-- Page Content -->
                <div class="flex-1">
                    {{ $slot }}
                </div>
                
            </main>
        </div>
        
        <!-- Script Stack (harus setelah DOM) -->
        @stack('scripts')
        
        <!-- Global UI helpers -->
        <script>
            function toggleSidebarNew(event) {
                event.stopPropagation();
                document.getElementById('sidebar-new-menu').classList.toggle('hidden');
            }
            function closeSidebarNew() {
                const el = document.getElementById('sidebar-new-menu');
                if (el) el.classList.add('hidden');
            }
            // Tutup sidebar dropdown saat klik di luar
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#sidebar-new-wrapper')) {
                    closeSidebarNew();
                }
            });
        </script>
    </body>
</html>
