@extends('backend.layouts.base')

@section('pageTitle') Создание правила @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="create-permission" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.roles.permissions.store') }}" method="post" id="create-permission">
        {!! csrf_field() !!}

        <x-input::text :name="'name'" :label="'Имя правила'" />

        <x-input::select :name="'guard_name'" :data="$guards" :label="'Охранник'" />
    </form>
@endsection
