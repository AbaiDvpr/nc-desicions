@extends('frontend.inner')

@section('content')

    @component('components.page-main', ['hideSidebar' => true])
        <h2 class="title-secondary">{{ $page->title }}</h2>

        <div class="post panel">
            <div class="post__content formatted">
                {!! $page->text !!}
            </div>
        </div>
    @endcomponent

@endsection

