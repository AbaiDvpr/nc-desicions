<ul class="header__list header__list--login">
    @if (!auth()->check())
        <li><a class="header__link" href="{{ route('frontend.auth.login') }}">Вход</a></li>
        <li><a class="header__link" href="{{ route('registration.index') }}">Регистрация</a></li>
    @else
        <li>
            <div class="user-info">
                <a href="{{ route('frontend.cabinet.home') }}">
                    @php
                        $photo = '/static/images/cabinet/default-image.svg';
                        $media = auth()->user()->getFirstMedia(\App\Models\User::PROFILE_COLLECTION_NAME) ?? null;

                        if (!is_null($media)) {
                            $photo = $media->getUrl('mediumThumb') ?? null;
                        }
                    @endphp

                    <img
                        class="user-info__picture"
                        src="{{ $photo }}"
                        width="52"
                        height="52"
                        alt="Аватар"
                    >
                </a>

                <div class="dropdown" id="userMenu">
                    <span class="dropdown__btn user-info__name" id="userMenuBtn">{{ auth()->user()->getName() }}</span>

                    <div class="dropdown__items" id="userMenuItems">
                        <a href="{{ route('frontend.cabinet.home') }}" class="dropdown__item">Кабинет</a>

                        <a href="{{ route('auth.exit') }}" class="dropdown__item">Выход</a>
                    </div>
                </div>
            </div>
        </li>
    @endif
</ul>
