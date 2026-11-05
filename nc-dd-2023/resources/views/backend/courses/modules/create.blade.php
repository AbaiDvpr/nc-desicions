@extends('backend.layouts.base')

@section('pageTitle', 'Создание модуля')

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="modules-create" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.courses.modules.store') }}" method="post" id="modules-create">
        {!! csrf_field() !!}

        <div class="row">
            <div class="col-12 col-md-1">
                <label class="c-switch c-switch-primary mt-4 pt-2">
                    {{ Form::checkbox('good', 1, false, ['class' => 'c-switch-input']) }}
                    <span class="c-switch-slider"></span>
                </label>
            </div>

            <div class="col-12 col-md-11">
                <x-input::text :name="'title'" :label="'Наименование модуля'" />
            </div>
        </div>

        <div class="nav-tabs-boxed">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#description" role="tab">Описание</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#plan" role="tab">План модуля</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="description" role="tabpanel">
                    <x-input::textarea :name="'description'" :value="null" :class="' tinymce'" :label="null"/>
                </div>

                <div class="tab-pane" id="plan" role="tabpanel">
                    <x-input::textarea :name="'plan'" :value="null" :class="' tinymce'" :label="null"/>
                </div>
            </div>
        </div>
    </form>
@endsection
