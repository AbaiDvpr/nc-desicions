<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }}</title>
    <link href="{{ mix('/static/frontend/cabinet/app.css') }}" rel="stylesheet">

    @include('frontend.blocks.base.statistics')

    <script>
        window.data = {
            name: 'NC-decision',
            user: {!! json_encode($client) !!},
            locale: '{{ app()->getLocale() }}',
            locales: { ru: 'Рус', kk: 'Қаз', en: 'Eng' },
            fallbackLocale: '{{ config('app.fallback_locale') }}',
            formData: {!! json_encode(config('form-data')) !!},
            apiDomain: '{!! config('app.api') !!}',
        };
    </script>
</head>
<body>
    @include('frontend.blocks.admin-mode')

    <div class="app" id="app"></div>

    <script src="{{ mix('/static/frontend/cabinet/app.js') }}"></script>
</body>
</html>
