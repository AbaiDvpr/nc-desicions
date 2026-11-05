@extends('frontend.inner')

@section('content')

    @component('components.page-main')
        <h2 class="title-secondary">{{ $record->category->title }}</h2>

        <div class="post panel">
            @php
                $photo = '/static/images/cabinet/default-image.svg';
                $media = $record->getFirstMedia(\App\Models\News::COVER_COLLECTION_NAME) ?? null;

                if (!is_null($media) && $media->getUrl('largeThumb')) {
                    $photo = $media->getUrl('largeThumb');
                }
            @endphp

            @if ($photo)
                <div class="post__img-wrap ratio">
                    <img src="{{ $photo }}"  alt="{{ $record->title }}" width="780" height="516"/>
                </div>
            @endif

            <div class="date">
                {!! icon('calendar') !!}

                <time datetime="{{ date('Y-m-d', strtotime($record->created_at)) }}">
                    {{ formatDate($record->created_at) }}
                </time>
            </div>

            <h2 class="post__title">{{ $record->title }}</h2>

            <div class="post__content formatted">
                {!! $record->short !!}
                {!! $record->full !!}
            </div>
        </div>
    @endcomponent

    @component('components.page-sidebar')
        @include('frontend.blocks.sidebar.news-category')
    @endcomponent

@endsection

