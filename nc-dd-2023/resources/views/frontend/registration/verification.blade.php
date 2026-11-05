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
                        <h2 class="authorization__form-title">{{ __('registration.verification.title') }}</h2>

                        @if ($status)
                            <div class="alert alert-success text-center" role="alert">
                                {!! __('registration.verification.success')  !!}
                            </div>
                        @else
                            <div class="alert alert-danger text-center" role="alert">
                                <h4 class="alert-heading">{{ __('registration.verification.error_title') }}</h4>
                                <hr>
                                <p class="mb-0">{{ __('registration.verification.error_description') }}</p>
                            </div>
                        @endif

                        <div class="authorization__form-body">
                            <a class="btn btn--primary" href="{{ route('frontend.auth.login') }}">Войти</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
