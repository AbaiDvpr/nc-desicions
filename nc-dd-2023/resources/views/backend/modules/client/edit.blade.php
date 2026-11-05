@extends('backend.layouts.base')

@section('pageTitle', 'Редактирование данных клиента')

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="update-user" class="btn card-header-action" title="Сохранить">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.modules.client.update', ['user' => $user->id]) }}" method="post" id="update-user">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        <div class="row">
            <div class="col-12">
                <label class="form-group d-inline-flex align-items-center mr-4">
                    <span class="mr-2">Полный доступ</span>

                    <div class="c-switch c-switch-primary mb-0">
                        {{ Form::checkbox('fullAccess', 1, $user->fullAccess, ['class' => 'c-switch-input']) }}
                        <span class="c-switch-slider"></span>
                    </div>
                </label>

                <label class="form-group d-inline-flex align-items-center">
                    <span class="mr-2">Доступ у курсам</span>

                    <div class="c-switch c-switch-primary mb-0">
                        {{ Form::checkbox('courseAccess', 1, $user->courseAccess, ['class' => 'c-switch-input']) }}
                        <span class="c-switch-slider"></span>
                    </div>
                </label>

                <hr>
            </div>

            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-6">
                        <x-input::text :name="'name'" :value="$user->name" :label="'ФИО'" />
                    </div>

                    <div class="col-md-6">
                        <x-input::date :name="'birthday'" :value="$user->birthday" :label="'Дата рождения'" />
                    </div>

                    <div class="col-md-6">
                        <x-input::text :name="'mobile'" :value="$user->mobile ?? null" :class="' mobile-number'" :label="'Мобильный телефон'" />
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <span class="form-control bg-secondary">{{ $user->email }}</span>
                        </div>
                    </div>

                    @if ($user->isActivated() && $user->tariff())
                        <div class="col-md-12">
                            <x-input::select name="tariff" :data="$tariffs" :value="$user->tariff()->getTariffId()" label="Тарифный план" />
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-md-4 d-flex flex-column">
                <x-input::password :name="'password'" :label="'Пароль'" />

                <x-input::password :name="'password_confirmation'" :label="'Повторите пароль'" />

                <div class="form-group mt-auto">
                    <a href="{{ route('backend.modules.client.clearCache', ['user' => $user->id]) }}"
                       class="btn btn-danger d-block"
                    >Очистить кеш пользователя</a>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('js')
    <script src="{{ mix('admin/js/users/users.min.js') }}"></script>
@endpush
