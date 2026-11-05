@extends('backend.layouts.base')

@section('pageTitle') Редактирование Вопроса @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="update-faq" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.modules.faq.update', ['faq' => $faq->id]) }}" method="post" id="update-faq">
        {!! csrf_field() !!}
        {!! method_field('PUT') !!}

        <div class="row">
            <div class="col-6 col-sm-3 col-lg-2 col-xl-1">
                <label class="d-block">Опубликовать</label>
                <label class="c-switch c-switch-primary">
                    {{ Form::checkbox('published', 1, $faq->published ?? false, ['class' => 'c-switch-input']) }}
                    <span class="c-switch-slider"></span>
                </label>
            </div>

            <div class="col-6 col-sm-3 col-lg-2 col-xl-1">
                <label class="d-block">Главная</label>
                <label class="c-switch c-switch-primary">
                    {{ Form::checkbox('main', 1, $faq->main ?? false, ['class' => 'c-switch-input']) }}
                    <span class="c-switch-slider"></span>
                </label>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xl-3">
                <x-input::text :name="'name'" :value="$faq->name" :label="'Имя'" />
            </div>

            <div class="col-12 col-xl-3">
                <x-input::text :name="'surname'" :value="$faq->surname" :label="'Фамилия'" />
            </div>

            <div class="col-12 col-xl-3">
                <x-input::text :name="'email'" :value="$faq->email" :label="'E-mail'" />
            </div>

            <div class="col-12 col-xl-3">
                <x-input::text :name="'phone'" :value="$faq->phone" :label="'Телефон'" />
            </div>

            <div class="col-12 col-xl-2">
                <label>Язык</label>
                <span class="form-control bg-light">{{ $faq->lang }}</span>
            </div>

            <div class="col-12 col-xl-2">
                <label class="d-block">Уведомление</label>
                <label class="c-switch c-switch-primary">
                    {{ Form::checkbox('send_notification', 1, false, ['class' => 'c-switch-input']) }}
                    <span class="c-switch-slider"></span>
                </label>
            </div>
        </div>

        <div class="nav-tabs-boxed mt-2">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#question" role="tab" aria-controls="profile">Вопрос</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#reply" role="tab" aria-controls="profile">Ответ</a>
                </li>
            </ul>

            <div class="tab-content p-1">
                <div class="tab-pane active" id="question" role="tabpanel">
                    <x-input::textarea :name="'question'" :value="$faq->question ?? null" :class="' tinymce'" :label="null"/>
                </div>
                <div class="tab-pane" id="reply" role="tabpanel">
                    <x-input::textarea :name="'reply'" :value="$faq->reply ?? null" :class="' tinymce'" :label="null"/>
                </div>
            </div>
        </div>

    </form>
@endsection
