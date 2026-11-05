@if (count($tariffs))
    <section class="prices bg-gray" id="prices">
        <div class="container">
            <div class="section-heading">
                <h2 class="section-heading__title">Тарифы на услуги</h2>

                <p class="section-heading__subtitle">
                    Выберите подходящий тариф и начните работать уже сегодня
                </p>
            </div>

            <div class="prices__body">
                <div class="tariff-nav" id="tariffNav">
                   <button class="tariff-nav__btn is-active" data-target="#pricesMonthly">
                       Месяц
                   </button>

                   <button class="tariff-nav__btn" data-target="#pricesAnnual">
                       Год
                       <span class="tariff-nav__badge">-50%</span>
                   </button>
                </div>

                <div class="slider" id="priceSlider">
                    <div class="prices__list" id="pricesMonthly">
                        @foreach ($tariffs as $tariff)
                            <div>
                                @include('frontend.blocks.snippets.prices-item', [
                                    'loop' => $loop,
                                    'tariff' => $tariff
                                ])
                            </div>
                        @endforeach
                    </div>

                    <div class="prices__list prices__list--annual hidden" id="pricesAnnual">
                        @foreach ($tariffsAnnual as $tariffAnnual)
                            <div>
                                @include('frontend.blocks.snippets.prices-item', [
                                    'loop'     => $loop,
                                    'tariff'   => $tariffAnnual,
                                    'isAnnual' => true
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
