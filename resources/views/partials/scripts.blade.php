    <script src="{{ url()->versionedAsset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ url()->versionedAsset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url()->versionedAsset('assets/js/custom.js') }}"></script>
    @if (session('status'))
        <script>$(function () { window.ffToast(@json(session('status'))); });</script>
    @endif
    @stack('scripts')
