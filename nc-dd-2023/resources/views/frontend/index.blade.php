@extends('frontend.layouts.base')

@section('main')

    @include('frontend.blocks.sections.intro')
    @include('frontend.blocks.sections.partners')
    @include('frontend.blocks.sections.support')
    @include('frontend.blocks.sections.about')
    @include('frontend.blocks.sections.services')
    @include('frontend.blocks.sections.prices')
    @include('frontend.blocks.sections.callback')
    @include('frontend.blocks.sections.review')
    @include('frontend.blocks.sections.news')
    @include('frontend.blocks.sections.feedback')

@endsection
