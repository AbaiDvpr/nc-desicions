@extends('backend.layouts.base')

@section('pageTitle', 'Создание урока')

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="lesson-create" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.courses.lessons.store') }}" method="post" id="lesson-create">
        {!! csrf_field() !!}

        <div class="row">
            <div class="col-12 col-md-1">
                <label class="c-switch c-switch-primary mt-4 pt-2">
                    {{ Form::checkbox('good', 1, false, ['class' => 'c-switch-input']) }}
                    <span class="c-switch-slider"></span>
                </label>
            </div>

            <div class="col-12 col-md-11">
                <x-input::text :name="'title'" :label="'Наименование урока'" />
            </div>

            <div class="col-md-12">
                <x-input::select name="moduleId" :data="$modules" label="Список модулей" placeholder="Выберите модуль" />
            </div>

            <div class="col-12 col-md-6">
                <x-input::text :name="'linkVideo'" :label="'Ссылка на youtube ролик'" />
            </div>

            <div class="col-12 col-md-6">
                <x-input::text :name="'linkAdditional'" :label="'Ссылка на доп. материалы'" />
            </div>

            <div class="col-12">
                <x-input::textarea :name="'description'" :value="null" :class="' tinymce'" label="Описание" />
            </div>
        </div>
    </form>
@endsection
