@extends('backend.layouts.base')

@section('pageTitle') Редактирование роли @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="update-role" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.roles.update', ['role' => $role->id]) }}" method="post" id="update-role">
        {!! csrf_field() !!}
        {!! method_field('put') !!}

        <div class="row">
            <div class="col-8">
                <x-input::text :name="'name'" :value="$role->name" :label="'Имя роли'" :help-block="'Имя на английском языке, без пробелов'" />

                <x-input::select :name="'guard_name'" :value="$role->guard_name" :data="$guards" :label="'Охранник'" />
            </div>

            <div class="col-4">
                <h4 class="border-bottom pb-1">Правила</h4>

                @if ($permissions->count() > 0)
                    @php $rolePermissions = $role->permissions->pluck('id'); @endphp
                    @foreach($permissions as $permission)
                        <x-input::checkbox :name="'permissions[' . $permission->id . ']'"
                                           :value="$permission->id"
                                           :checked="in_array($permission->id, $rolePermissions->toArray())"
                                           :label="$permission->name" />
                    @endforeach
                @endif
            </div>
        </div>
    </form>
@endsection
