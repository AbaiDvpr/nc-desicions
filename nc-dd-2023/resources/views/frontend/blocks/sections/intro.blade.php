<section class="intro" id="intro">
    <div class="container">
        <div class="intro__body">
            <div class="intro__content">
                <h1 class="intro__title">Расчёт вашего проекта за 15 минут</h1>

                <p class="intro__subtitle">
                    NC Decision - это программа, которая предоставляет уникальную возможность
                    самостоятельно рассчитывать планируемые инвестиционные решения без риска потери финансовых
                    средств.
                </p>

                <p class="intro__subtitle">
                    С ее помощью Вы сможете принимать важные инвестиционные решения, используя
                    точные расчеты и планирование, которые помогут Вам достичь своих финансовых целей.
                </p>

                <div class="intro__controls">
                    <a class="btn btn--primary" href="{{ route('registration.index') }}">Регистрация</a>
                    {{--<button class="btn btn--outline-primary" data-modal="modalIntroVideo">Смотреть видео</button>--}}
                </div>
            </div>

            <div class="intro__media">
                <img
                    class="img-responsive"
                    src="/static/images/common/intro/intro-img-lg.webp"
                    width="498"
                    height="476"
                    alt="Главное изображение секции Интро"
                >
            </div>
        </div>

        @include('frontend.blocks.modals.intro-video')
    </div>
</section>
