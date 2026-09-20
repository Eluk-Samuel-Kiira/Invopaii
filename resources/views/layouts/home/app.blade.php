<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Stardena Pay — Get paid, everywhere')</title>
    <meta name="description" content="@yield('description', 'Collect cards from anywhere in the world and mobile money across Africa through one API, payment links, and SMS alerts.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="shortcut icon" href="{{ asset('pay.png') }}" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @include('layouts.home.style')
    @stack('styles')
</head>
<body>

@include('layouts.home.nav')

<main>
@yield('content')
</main>

@include('layouts.home.footer')
@include('layouts.home.widget')

@include('layouts.home.script')
@stack('scripts')
</body>
</html>