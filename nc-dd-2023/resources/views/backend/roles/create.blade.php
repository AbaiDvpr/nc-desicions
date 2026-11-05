@extends('backend.layouts.base')

@section('pageTitle') Создание роли @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="create-role" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.roles.store') }}" method="post" id="create-role">
        {!! csrf_field() !!}

        <div class="row">
            <div class="col-8">
                <x-input::text :name="'name'" :label="'Имя роли'" :help-block="'Имя на английском языке, без пробелов'" />

                <x-input::select :name="'guard_name'" :data="$guards" :label="'Охранник'" />
            </div>

            <div class="col-4">
                <h4 class="border-bottom pb-1">Правила</h4>

                @if ($permissions->count() > 0)
                    @foreach($permissions as $permission)
                        <x-input::checkbox :name="'permissions[' . $permission->id . ']'" :value="$permission->id" :label="$permission->name" />
                    @endforeach
                @endif
            </div>
        </div>
    </form>
@endsection
