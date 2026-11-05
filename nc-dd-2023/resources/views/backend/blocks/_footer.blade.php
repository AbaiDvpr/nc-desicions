
        @stack('before-js')

        <script src="/admin/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
        <script src="{{ mix('admin/js/app.js') }}"></script>

        @stack('js')

        <x-notify/>
    </body>
</html>
