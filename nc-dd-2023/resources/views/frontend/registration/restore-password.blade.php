@extends('frontend.layouts.auth')

@section('main')
    <div class="container">
        <section class="authorization">
            <h1 class="hidden">Восстановление пароля</h1>

            <div class="authorization__body">
                <div class="authorization__form">
                    <div class="authorization__form-info">
                        <h2 class="authorization__form-title">Забыли пароль?</h2>
                    </div>

                    @if ($status)
                        <form action="{{ route('registration.restore.code.update', ['code' => $code]) }}" method="post">
                            {{ csrf_field() }}

                            <h2 class="authorization__form-title">Восстановление пароля</h2>

                            <p class="authorization__form-subtitle">Введите пароль</p>

                            <div class="authorization__form-body">
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

                                <button class="btn btn--primary" type="submit">Восстановить</button>

                                <ul class="authorization__form-menu">
                                    <li><a href="{{ route('frontend.auth.login') }}">Войти в кабинет</a></li>
                                    <li><a href="{{ route('frontend.index') }}">Вернуться на главную</a></li>
                                </ul>
                            </div>
                        </form>
                    @else
                        <form>
                            <div class="alert alert--danger">
                                {{ __('registration.restore.link_is_not_valid') }}
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
