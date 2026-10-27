    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Feedee Forums')</title>
    @hasSection('description')
    <meta name="description" content="@yield('description')">
    @endif
    @stack('meta')
    <meta name="theme-color" content="#131313">
    <link rel="icon" href="{{ asset('assets/images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ url()->versionedAsset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ url()->versionedAsset('assets/css/style.css') }}">
    @stack('head')
