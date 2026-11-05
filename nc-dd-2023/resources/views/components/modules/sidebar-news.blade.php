@if (count($news))
    <ul class="news-preview__list panel">
        @foreach($news as $item)
            @php
                $photo = '/static/images/cabinet/default-image.svg';
                $media = $item->getFirstMedia(\App\Models\News::COVER_COLLECTION_NAME) ?? null;

                if (!is_null($media) && $media->getUrl('smallThumb')) {
                    $photo = $media->getUrl('smallThumb');
                }
            @endphp

            <li>
                <a class="news-preview__item" href="{{ route('frontend.modules.news.show', ['slug' => $item->category->slug, 'id' => $item->id]) }}">
                    <div class="news-preview__item-img">
                        <img class="news-item__img" src="{{ $photo }}" >
                    </div>

                    <div class="news-preview__item-text">
                        <h3 class="news-preview__item-title">
                            {{ $item->title }}
                        </h3>

                        <div class="date date--compact">
                            {!! icon('calendar') !!}

                            <span>{{ formatDate($item->created_at) }}</span>
                        </div>
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
@endif
