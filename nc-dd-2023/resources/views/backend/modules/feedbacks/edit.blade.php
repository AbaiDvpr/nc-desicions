@extends('backend.layouts.base')

@section('pageTitle', 'Редактирование заявки')

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button
            form="update-feedback"
            class="btn card-header-action"
            title="Сохранить"
        ><i class="cil-save"></i></button>
    </div>
@endsection

@section('body')
    <form
        action="{{ route('backend.modules.feedbacks.update', ['feedback' => $feedback->id]) }}"
        method="post"
        id="update-feedback"
    >
        {!! csrf_field() !!}
        {!! method_field('PUT') !!}

        <div class="row">
            <div class="col-12 col-lg-12">
                <x-input::select
                    :name="'status'"
                    :data="$statuses"
                    :value="$feedback->status" :label="'Статус'"
                />
            </div>

            <div class="col-12 col-lg-6">
                <div class="form-group">
                    <div class="mb-2">Язык</div>
                    <div class="form-control bg-light">{{ $feedback->lang }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="form-group">
                    <div class="mb-2">Имя</div>
                    <div class="form-control bg-light">{{ $feedback->name }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="form-group">
                    <div class="mb-2">Телефон</div>
                    <div class="form-control bg-light">{{ $feedback->phone }}</div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="form-group">
                    <div class="mb-2">Дата создания</div>
                    <div class="form-control bg-light">{{ $feedback->created_at }}</div>
                </div>
            </div>
        </div>
    </form>
@endsection
