@extends('frontend.inner')

@section('content')

    @component('components.page-main')
        <h2 class="title-secondary">{{ $category->title }}</h2>

        @if ($news->count())
            <div class="row">
                @foreach($news as $nws)
                    @php
                        $photo = '/static/images/cabinet/default-image.svg';
                        $media = $nws->getFirstMedia(\App\Models\News::COVER_COLLECTION_NAME) ?? null;

                        if (!is_null($media) && $media->getUrl('mediumThumb')) {
                            $photo = $media->getUrl('mediumThumb');
                        }
                    @endphp
                    <div class="col-12 col-md-6">
                        <div class="news-item panel">
                            <a  class="news-item__link"
                                href="{{ route('frontend.modules.news.show', ['slug' => $nws->category->slug, 'id' => $nws->id]) }}"
                            >
                                <div class="news-item__img-wrap">
                                    <img class="news-item__img" src="{{ $photo }}" alt="{{ $nws->title }}" width="336" height="184">
                                </div>

                                <div class="date date--compact">
                                    {!! icon('calendar') !!}

                                    <time datetime="{{ date('Y-m-d', strtotime($nws->created_at)) }}">
                                        {{ formatDate($nws->created_at) }}
                                    </time>
                                </div>

                                <div class="news-item__info">
                                    <h3 class="news-item__title">{{ $nws->title }}</h3>

                                    <div class="news-item__text">
                                        {!! \Illuminate\Support\Str::limit(strip_tags($nws->short), 150) !!}
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                @endforeach
            </div>

            {{ $news->onEachSide(2)->links('vendor.pagination.frontend') }}
        @endif
    @endcomponent

    @component('components.page-sidebar')
        @include('frontend.blocks.sidebar.news-category')
    @endcomponent

@endsection

