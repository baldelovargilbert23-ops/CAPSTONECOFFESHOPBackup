<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @include('layouts.partials.theme-init')

        @vite(['resources/css/app.css'])
        @livewireStyles
        @include('layouts.partials.global-theme')
    </head>
    <body class="font-sans antialiased">
        @include('layouts.employee.header')
        @include('layouts.employee.sidebar')

        {{ $slot }}

        @livewireScripts
        @vite(['resources/js/app.js'])
    </body>
</html>
