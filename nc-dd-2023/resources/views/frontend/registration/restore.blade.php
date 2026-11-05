@extends('frontend.layouts.auth')

@section('main')
    <div class="container">
        <section class="authorization">
            <h1 class="hidden">Восстановление пароля</h1>

            <div class="authorization__body">
                <div class="authorization__form">
                    <div class="authorization__form-info">
                        <h2 class="authorization__form-title">Забыли пароль?</h2>
                        <p class="authorization__form-subtitle">Введите email указанный при регистрации и мы пришелм вам ссылку для сброса пароля</p>
                    </div>

                    <form action="{{ route('registration.restore.post') }}" method="post">
                        {{ csrf_field() }}

                        <h2 class="authorization__form-title">Восстановление пароля</h2>

                        <p class="authorization__form-subtitle">Укажите email для восстановления пароля</p>

                        @if (session()->has('success'))
                            <div class="authorization__form-subtitle" style="color: #28A745">
                                {!! __('registration.restore.success')  !!}
                            </div>
                        @endif

                        <div class="authorization__form-body">
                            <div class="form-group form-group--lg @error('email') is-error @enderror">
                                {{ Form::email('email', null, ['class' => 'input', 'placeholder' => 'E-mail']) }}

                                @error('email')
                                    <span class="form-group__error">{{ $message }}</span>
                                @enderror
                            </div>

                            <button class="btn btn--primary" type="submit">Восстановить пароль</button>

                            <ul class="authorization__form-menu">
                                <li><a href="{{ route('frontend.auth.login') }}">Войти в кабинет</a></li>
                                <li><a href="{{ route('frontend.index') }}">Вернуться на главную</a></li>
                            </ul>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
