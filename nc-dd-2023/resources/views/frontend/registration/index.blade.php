@extends('frontend.layouts.auth')

@push('vendor-js')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush

@section('main')
    <div class="container">
        <section class="authorization">
            <h1 class="hidden">Регистрация</h1>
            <div class="authorization__body">
                <div class="authorization__form">
                    <div class="authorization__form-info">
                        <h2 class="authorization__form-title">Уже есть аккаунт?</h2>
                        <p class="authorization__form-subtitle">Используйте Ваши учетные данные для доступа в личный кабинет</p>
                        <a href="{{ route('frontend.auth.login') }}" class="btn btn--primary">Войти в кабинет</a>
                    </div>

                    <form action="{{ route('registration.post') }}" method="post">
                        {{ csrf_field() }}

                        <h2 class="authorization__form-title">Создать аккаунт</h2>

                        @if (session()->has('success'))
                            <div class="alert alert--success">
                                {!! __('registration.registration.success')  !!}
                            </div>
                        @else
                            <p class="authorization__form-subtitle">Введите Ваши учетные данные</p>
                        @endif

                        <div class="authorization__form-body">
                            @if (!session()->has('success'))
                                <div class="form-group form-group--lg @error('name') is-error @enderror">
                                    {{ Form::text('name', null, ['class' => 'input', 'placeholder' => 'ФИО']) }}

                                    @error('name')
                                        <span class="form-group__error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group form-group--lg @error('email') is-error @enderror">
                                    {{ Form::email('email', null, ['class' => 'input', 'placeholder' => 'E-mail']) }}

                                    @error('email')
                                        <span class="form-group__error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group form-group--lg @error('password') is-error @enderror">
                                    {{ Form::password('password', ['class' => 'input', 'placeholder' => 'Пароль']) }}

                                    @error('password')
                                        <span class="form-group__error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group form-group--lg @error('password_confirmation') is-error @enderror">
                                    {{ Form::password('password_confirmation', ['class' => 'input', 'placeholder' => 'Подтвердите пароль']) }}

                                    @error('password_confirmation')
                                        <span class="form-group__error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group form-group--lg">
                                    <div class="g-recaptcha"
                                         data-sitekey="{{ config('google.recaptcha.siteKey') }}"></div>

                                    @error('g-recaptcha-response')
                                        <span class="form-group__error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <button class="btn btn--primary mt-0" type="submit">Зарегистрироваться</button>
                            @endif
                        </div>

                        <ul class="authorization__form-menu">
                            <li class="hidden-lg"><a href="{{ route('frontend.auth.login') }}">Войти в кабинет</a></li>
                            <li><a href="{{ route('frontend.index') }}">Вернуться на главную</a></li>
                        </ul>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
