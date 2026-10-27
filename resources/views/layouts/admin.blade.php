<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
@include('partials.head')
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="{{ url()->versionedAsset('assets/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ url()->versionedAsset('assets/css/admin.css') }}">
</head>
<body class="admin-body">

    <div class="admin">
        @include('admin.partials.sidebar')
        <div class="side-backdrop"></div>

        <div class="admin-main">
            @include('admin.partials.topbar')

            <main class="admin-content">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('modals')

@include('partials.scripts')
    <script src="{{ url()->versionedAsset('assets/js/dataTables.min.js') }}"></script>
    <script src="{{ url()->versionedAsset('assets/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ url()->versionedAsset('assets/js/admin.js') }}"></script>
</body>
</html>
