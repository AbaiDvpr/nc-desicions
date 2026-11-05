<section class="feedback bg-gray" id="feedback">
    <div class="container">
        <div class="section-heading">
            <h2 class="section-heading__title">Будем на связи</h2>

            <p class="section-heading__subtitle">
                Оставьте нам сообщение и мы очень скоро свяжемся с Вами
            </p>
        </div>

        <div class="feedback__body">
            <div class="feedback__contacts">
                <h2 class="feedback__title">Контакты</h2>

                <ul class="feedback__contacts-list">
                    <li>
                        <div class="icon-wrap">
                            {!! icon('phone') !!}
                        </div>
                        <a href="tel:77012223527">+7 (701) 222 3527 </a>&nbsp;<a href="tel:77054823484">+7 (705) 482 3484</a>
                    </li>

                    <li>
                        <div class="icon-wrap">
                            {!! icon('location') !!}
                        </div>
                        г. Астана, ул. Ш. Калдаякова 13, офис 3
                    </li>

                    <li>
                        <div class="icon-wrap">
                            {!! icon('time') !!}
                        </div>Пн - Пт: 10:00 - 19:00
                    </li>

                    <li>
                        <div class="icon-wrap">
                            {!! icon('mail') !!}
                        </div>info@ncdecision.com
                    </li>

                    <li>
                        <div class="feedback__map">
                            <span class="feedback__map-title">Как к нам добраться?</span>

                            <iframe class="feedback__map-wrap" loading="lazy" src="https://yandex.ru/map-widget/v1/?um=constructor%3A42caa55591f27951b72a47debb51f85bd3bed09130641a1cbbf5de645987305c&amp;source=constructor" width="320" height="240" frameborder="0"></iframe>
                        </div>
                    </li>
                </ul>
            </div>

            @include('frontend.blocks.snippets.feedback-form')
        </div>
    </div>
</section>
