@extends('backend.layouts.base')

@section('pageTitle') Создание пользователя @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="create-user" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.users.store') }}" method="post" id="create-user">
        {!! csrf_field() !!}

        <div class="row">
            <div class="col-12 col-md-9">
                <x-input::text :name="'name'" :label="'ФИО'" />
            </div>

            <div class="col-12 col-md-3">
                <x-input::select :name="'admin'" :data="[1 => 'Да', 0 => 'Нет']" :label="'Админ'" />
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-6">
                <x-input::text :name="'email'" :label="'E-mail'" />
            </div>

            <div class="col-12 col-md-6">
                <x-input::select :name="'roles'" :data="$roles" :label="'Выберите роль'" />
            </div>

            <div class="col-12">
                <x-input::text :name="'mobile'" :class="' mobile-number'" :label="'Мобильный телефон'" />
            </div>
        </div>

        <h4 class="border-bottom pb-2">Безопасность</h4>

        <x-input::password :name="'password'" :label="'Пароль'" />

        <x-input::password :name="'password_confirmation'" :label="'Повторите пароль'" />
    </form>
@endsection
