@extends('backend.layouts.base')

@section('pageTitle') Редактирование правила @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="update-permission" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.roles.permissions.update', ['permission' => $permission->id]) }}" method="post" id="update-permission">
        {!! csrf_field() !!}
        {!! method_field('put') !!}

        <x-input::text :name="'name'" :value="$permission->name" :label="'Имя правила'" />

        <x-input::select :name="'guard_name'" :value="$permission->guard_name" :data="$guards" :label="'Охранник'" />
    </form>
@endsection
