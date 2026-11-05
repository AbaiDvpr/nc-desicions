<div class="sidebar__item">
    <h2 class="title-secondary">Рубрики новостей</h2>

    <ul class="news-category__list panel">
        <li>
            <a class="news-category__item {{ request()?->slug === 'business' ? 'is-active' : '' }}"
               href="{{ route('frontend.modules.news.index', ['slug' => 'business']) }}"
            >
                Новости бизнеса
            </a>
        </li>

        <li>
            <a class="news-category__item {{ request()?->slug === 'releases' ? 'is-active' : '' }}"
               href="{{ route('frontend.modules.news.index', ['slug' => 'releases']) }}"
            >
                Пресс-релизы
            </a>
        </li>
    </ul>

    <x-sidebar-news />
</div>

