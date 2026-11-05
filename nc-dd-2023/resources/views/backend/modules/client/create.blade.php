@extends('backend.layouts.base')

@section('pageTitle', 'Создание пользователя')

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="create-user" class="btn card-header-action" title="Сохранить">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.modules.client.store') }}" method="post" id="create-user">
        {{ csrf_field() }}

        <div class="row">
            <div class="col-12">
                <label class="form-group d-inline-flex align-items-center mr-4">
                    <span class="mr-2">Полный доступ</span>

                    <div class="c-switch c-switch-primary mb-0">
                        {{ Form::checkbox('fullAccess', 1, false, ['class' => 'c-switch-input']) }}
                        <span class="c-switch-slider"></span>
                    </div>
                </label>

                <label class="form-group d-inline-flex align-items-center">
                    <span class="mr-2">Доступ у курсам</span>

                    <div class="c-switch c-switch-primary mb-0">
                        {{ Form::checkbox('courseAccess', 1, false, ['class' => 'c-switch-input']) }}
                        <span class="c-switch-slider"></span>
                    </div>
                </label>

                <hr>
            </div>

            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-6">
                        <x-input::text :name="'name'" :value="null" :label="'ФИО'" />
                    </div>

                    <div class="col-md-6">
                        <x-input::date :name="'birthday'" :value="null" :label="'Дата рождения'" />
                    </div>

                    <div class="col-md-6">
                        <x-input::text :name="'mobile'" :value="null" :class="' mobile-number'" :label="'Мобильный телефон'" />
                    </div>

                    <div class="col-md-6">
                        <x-input::text :name="'email'" :value="null" :label="'E-mail'" />
                    </div>

                    <div class="col-md-12">
                        <x-input::select name="tariff" :data="$tariffs" :value="null" label="Тарифный план" />
                    </div>
                </div>
            </div>

            <div class="col-md-4 d-flex flex-column">
                <x-input::password :name="'password'" :label="'Пароль'" />
                <x-input::password :name="'password_confirmation'" :label="'Повторите пароль'" />
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script src="{{ mix('admin/js/users/users.min.js') }}"></script>
@endpush
