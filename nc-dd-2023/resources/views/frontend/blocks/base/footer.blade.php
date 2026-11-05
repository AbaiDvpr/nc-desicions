    @include('frontend.blocks.base.statistics')

    @stack('vendor-js')

    <script src="{{ mix('/static/frontend/site/app.js') }}"></script>

    @stack('js')
</body>
</html>
