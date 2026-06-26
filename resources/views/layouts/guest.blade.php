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
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-tr from-slate-50 via-blue-50/30 to-slate-100">
            <div class="flex flex-col items-center justify-center gap-3">
                <a href="/" class="flex flex-col items-center gap-2">
                    <x-application-logo class="w-16 h-16 rounded-2xl shadow-sm border border-gray-100 object-contain p-2 bg-white" />
                    <span class="text-2xl font-bold tracking-tight text-gray-800">VisiFoto</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-8 px-8 py-6 bg-white border border-gray-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.03)] overflow-hidden sm:rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
