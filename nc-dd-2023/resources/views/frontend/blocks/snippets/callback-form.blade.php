<form
    class="callback__form"
    id="callback-form"
    data-action="{{ route('frontend.modules.feedback.store') }}"
>
    {!! csrf_field() !!}

    <div class="row">
        <div class="form-group form-group--lg col-12 col-md-6">
            <input
                type="text"
                class="input"
                name="name"
                placeholder="Введите имя"
            >
        </div>

        <div class="form-group form-group--lg col-12 col-md-6">
            <input
                type="text"
                class="input"
                name="phone"
                placeholder="Введите телефон"
                data-maska="+7 (###) ###-##-##"
            >
        </div>

        <div class="form-group col-12">
            <div
                class="callback__form-recaptcha g-recaptcha"
                data-sitekey="{{ config('google.recaptcha.siteKey') }}"
            ></div>
        </div>
    </div>

    <div class="callback__form-control">
        <div class="callback__privacy">
            <div class="checkbox">
                <input class="checkbox__input" type="checkbox" id="callback-cb" name="policy">
                <label class="checkbox__label callback__privacy-text" for="callback-cb">
                    Соглашаюсь с <a href="#">политикой</a> обработки персональных данных
                </label>
            </div>
        </div>

        <button class="callback__form-submit btn btn--primary" type="submit">
            Оставить заявку
            <i class="loader"></i>
        </button>
    </div>
</form>
