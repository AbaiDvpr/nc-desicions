<div class="prices__item {{ $loop->iteration === 2 ? 'prices__item--dark' : '' }}">
    <img
        class="prices__item-img"
        src="/static/images/common/prices/price-item-img-{{ $tariff['id'] }}.svg"
        alt="{{ $tariff['name'] }}"
        width="100"
        height="100"
    >

    <b class="prices__item-title">{{ $tariff['name'] }}</b>

    @if ($tariff['price'])
        @php $price = isset($isAnnual) ? $tariff['price'] / 12 : $tariff['price']; @endphp

        <div class="price__item-wrap">
            <b class="prices__item-cost">{{ number_format($price, 0, '', ' ') }} ₸</b>

            @if (isset($isAnnual))
                <b class="prices__item-period">за 1 месяц</b>
            @endif
        </div>
    @endif

    <div class="prices__item-content">{!! $tariff['description'] !!}</div>

    <a
        class="btn btn--outline-primary w-100"
        href="/{{ app()->getLocale() }}/cabinet#/payments/confirmation/{{ $tariff['id'] }}"
        target="_blank"
    >Выбрать тариф</a>
</div>

