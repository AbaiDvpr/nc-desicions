@if($news && $news->count())
    <section class="news" id="news">
        <div class="container">
            <div class="section-heading">
                <h2 class="section-heading__title">Новости</h2>

                <p class="section-heading__subtitle">Новости нашего сервиса и не только</p>
            </div>
            <div class="slider news-slider" id="newsSlider">
                <div class="news__list">
                    @foreach($news as $nws)
                        @php
                            $photo = '/static/images/cabinet/default-image.svg';
                            $media = $nws->getFirstMedia(\App\Models\News::COVER_COLLECTION_NAME) ?? null;

                            if (!is_null($media) && $media->getUrl('mediumThumb')) {
                                $photo = $media->getUrl('mediumThumb');
                            }
                        @endphp
                    <div>
                        <div class="news-item">
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
            </div>
        </div>
    </section>
@endif
