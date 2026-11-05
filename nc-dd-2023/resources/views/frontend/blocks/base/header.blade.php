<!DOCTYPE html>
<html lang="{{ app()->currentLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="google" content="notranslate">
    <title>{{ $seo->title }}</title>
    <meta name="description" content="{!! strip_tags($seo->description) !!}">
    <meta name="keywords" content="{!! strip_tags($seo->keywords) !!}">
    @include('frontend.blocks.base.open-graph')
    <link href="{{ mix('/static/frontend/site/app.css') }}" rel="stylesheet">
</head>
<body>
