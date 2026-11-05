@extends('frontend.layouts.base')

@section('main')
    <div class="container">
        <section class="payment">
            @if (isset($isError))
                <div class="payment-message payment-message--error">
                    <div class="payment-message__img">
                        <img src="/static/images/common/payment/payment-error.svg" alt="Изображение сообщения об оплате">
                    </div>
                    <h2 class="payment-message__title">Произошла ошибка!</h2>
                    <p class="payment-message__subtitle">Сожалеем, но при выполнении операции произошла ошибка.</p>
                    <a href="/" class="btn btn--primary">Вернуться на сайт</a>
                </div>
            @else
                @if($invoice->isPaid())
                    <div class="payment-message payment-message--success">
                        <div class="payment-message__img">
                            <img src="/static/images/common/payment/payment-success.svg" alt="Изображение сообщения об оплате">
                        </div>
                        <h2 class="payment-message__title">Оплата прошла успешно!</h2>
                        <p class="payment-message__subtitle">Теперь Вам доступны услуги по тарифному плану
                            <span class="payment-message__tariff">
                                "{{ $invoice->service }}"
                            </span>
                            <br> Для того чтобы воспользоваться сервисом, перейдите в личный кабинет.</p>
                        <a href="{{ route('frontend.cabinet.home') }}" class="btn btn--primary">Личный кабинет</a>
                    </div>
                @else
                    <div class="payment-message payment-message--progress">
                        <div class="payment-message__img">
                            <img src="/static/images/common/payment/payment-progress.svg" alt="Изображение сообщения об оплате">
                        </div>
                        <h2 class="payment-message__title">В обработке!</h2>
                        <p class="payment-message__subtitle">Оплата успешно прошла, но мы пока не получили подтверждение от платежной системы, как только мы получим подтверждение, Вы получите уведомление на почту.</p>
                        <a href="/" class="btn btn--primary">Вернуться на сайт</a>
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
