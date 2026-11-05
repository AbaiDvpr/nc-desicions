@extends('frontend.layouts.auth')

@section('main')
    <div class="container">
        <section class="authorization">
            <h1 class="hidden">Авторизация</h1>

            <div class="authorization__body">
                <div class="authorization__form">
                    <div class="authorization__form-info">
                        <h2 class="authorization__form-title">Забыли пароль?</h2>
                    </div>

                    <form action="">
                        <h2 class="authorization__form-title">{{ __('registration.restore.title') }}</h2>

                        @if ($status)
                            <div class="alert alert-success">
                                {{ __('registration.restore.password_updated') }}
                            </div>

                            <div class="authorization__form-body">
                                <a class="btn btn--primary" href="{{ route('frontend.auth.login') }}">Войти</a>
                            </div>
                        @else
                            <div class="alert alert-danger">
                                {{ __('registration.restore.link_is_not_valid') }}
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
