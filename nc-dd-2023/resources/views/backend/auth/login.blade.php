<!DOCTYPE html>
<html lang="ru">
    <head>
        <base href="./">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
        <meta name="google" content="notranslate">
        <title>NC-decision</title>
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="theme-color" content="#ffffff">

        <!-- Main styles for this application-->
        <link rel="stylesheet" href="/admin/assets/icons/css/all.css">
        <link rel="stylesheet" href="{{ mix('/admin/css/app.css') }}">
    </head>

    <body class="c-app flex-row align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                    <div class="card-group">
                        <div class="card p-4">
                            <div class="card-body">
                                <form action="{{ route('backend.auth.post.login') }}" method="post">
                                    {{ csrf_field() }}

                                    <h1 class="text-center">Вход</h1>
                                    <p class="text-muted text-center">Войдите в свой аккаунт</p>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="cil-user"></i>
                                            </span>
                                        </div>
                                        <input class="form-control {{ !$errors->has('email') ?: 'is-invalid' }}" name="email" type="text" placeholder="E-mail">

                                        @if ($errors->has('email'))
                                            <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                                        @endif
                                    </div>
                                    <div class="input-group mb-4">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="cil-lock-locked"></i>
                                            </span>
                                        </div>
                                        <input class="form-control {{ ($errors->has('password') || $errors->has('field')) ? 'is-invalid' : '' }}" name="password" type="password" placeholder="Password">

                                        @if ($errors->has('password') || $errors->has('field'))
                                            @if ($errors->has('password'))
                                                <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                                            @endif
                                            @if ($errors->has('field'))
                                                <div class="invalid-feedback">{{ $errors->first('field') }}</div>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <button class="btn btn-primary w-100" type="submit">Войти</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
