@extends('frontend.layouts.auth')

@section('main')
    <div class="container">
        <section class="authorization">
            <h1 class="hidden">Авторизация</h1>

            <div class="authorization__body">
                <div class="authorization__form">
                    <form action="{{ route('frontend.auth.post.login') }}" method="post">
                        {{ csrf_field() }}

                        <h2 class="authorization__form-title">Войти в кабинет</h2>

                        <div class="authorization__form-social">
                            <a class="btn btn--icon" href="{{ route('frontend.auth.socialite', ['client' => 'facebook']) }}">
                                {!! icon('facebook') !!}
                            </a>

                            <a class="btn btn--icon" href="{{ route('frontend.auth.socialite', ['client' => 'github']) }}">
                                {!! icon('git') !!}
                            </a>

                            <a class="btn btn--icon" href="{{ route('frontend.auth.socialite', ['client' => 'google']) }}">
                                {!! icon('google') !!}
                            </a>
                        </div>

                        @if($errors->count())
                            <div class="alert alert--danger mt-32">{{ $errors->first() }}</div>
                        @endif

                        <p class="authorization__form-subtitle">или используйте аккаунт</p>

                        <div class="authorization__form-body">
                            <div class="form-group form-group--lg @error('email') is-error @enderror">
                                <input class="input" type="text" name="email" placeholder="E-mail">

                                @error('email')
                                    <span class="form-group__error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group form-group--lg @error('password') is-error @enderror">
                                <input class="input" type="password" name="password" placeholder="Пароль">

                                @error('password')
                                    <span class="form-group__error">{{ $message }}</span>
                                @enderror
                            </div>

                            <button class="btn btn--primary" type="submit">Войти в кабинет</button>

                            <ul class="authorization__form-menu">
                                <li>
                                    <a href="/{{ app()->getLocale() }}">На главную</a>
                                </li>
                                <li class="hidden-lg">
                                    <a href="{{ route('registration.index') }}">Создать аккаунт</a>
                                </li>
                                <li>
                                    <a href="{{ route('registration.restore') }}">Восстановить пароль</a>
                                </li>
                            </ul>
                        </div>
                    </form>

                    <div class="authorization__form-info">
                        <h2 class="authorization__form-title">Впервые у нас?</h2>

                        <p class="authorization__form-subtitle">Зарегистрируйтесь на сайте для доступа в личный кабинет</p>

                        <a href="{{ route('registration.index') }}" class="btn btn--primary">Создать аккаунт</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

