@push('vendor-js')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush

<form
    class="feedback__form"
    id="feedback-form"
    data-action="{{ route('frontend.modules.faq.store') }}"
>
    {!! csrf_field() !!}

    <h2 class="feedback__title">Оставить сообщение</h2>

    <p class="feedback__subtitle mb-16">Мы свяжемся с Вами в ближайшее время</p>

    <div class="feedback__form-body">
        <div class="row">
            <div class="col-12 col-sm-6">
                <div class="form-group form-group--lg">
                    <input class="input" type="text" name="name" placeholder="Имя">
                </div>
            </div>

            <div class="col-12 col-sm-6">
                <div class="form-group form-group--lg">
                    <input class="input" type="text" name="surname" placeholder="Фамилия">
                </div>
            </div>

            <div class="col-12 col-sm-6">
                <div class="form-group form-group--lg">
                    <input class="input" type="email" name="email" placeholder="Email">
                </div>
            </div>

            <div class="col-12 col-sm-6">
                <div class="form-group form-group--lg">
                    <input
                        class="input"
                        type="text"
                        name="phone"
                        placeholder="Телефон"
                        data-maska="+7 (###) ###-##-##"
                    >
                </div>
            </div>

            <div class="col-12 col-sm-12">
                <div class="form-group form-group--lg">
                    <textarea
                        class="textarea textarea--feedback"
                        name="question"
                        placeholder="Текст сообщения"
                    ></textarea>
                </div>
            </div>
        </div>

        <div class="feedback__form-control">
            <div class="feedback__privacy">
                <div class="checkbox">
                    <input class="checkbox__input" type="checkbox" id="feedback-cb" name="policy">
                    <label class="checkbox__label feedback__privacy-text" for="feedback-cb">
                        Соглашаюсь с <a href="#">политикой</a> обработки персональных данных
                    </label>
                </div>

                <div class="feedback__form-submit-wrap">
                    <div
                        class="feedback__form-recaptcha g-recaptcha"
                        data-sitekey="{{ config('google.recaptcha.siteKey') }}"
                    ></div>

                    <button class="feedback__form-submit btn btn--primary" type="submit">
                        Отправить сообщение
                        <i class="loader"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
