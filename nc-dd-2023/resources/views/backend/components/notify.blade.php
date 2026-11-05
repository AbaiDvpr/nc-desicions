@if(Session::has('success'))
    <script type="text/javascript">
        toastr.success("{!! session()->get('success') !!}")
    </script>
@endif

@if (session()->has('errors') || $errors)
    @php $errors = $errors->all() ?? session()->get('errors') ?? []; @endphp

    @if (count($errors) > 0)
        @foreach($errors as $error)
            <script type="text/javascript">
                toastr.error("{!! stripcslashes($error) !!}")
            </script>
        @endforeach
    @endif
@endif
