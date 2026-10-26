<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
@include('partials.head')
</head>
<body @yield('body_attributes')>

    <a href="#main" class="skip-link">Skip to content</a>

    @include('partials.header')

    @yield('content')

    @section('footer')
        @include('partials.footer')
    @show

@include('partials.support-fab')
@include('partials.scripts')
</body>
</html>
