@extends('backend.layouts.base')

@push('js')
    <script src="{{ mix('admin/js/courses/lessons/order.js') }}"></script>
@endpush

@section('pageTitle')
    <i class="cil-book"></i>
    Обучение - список уроков
@endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <a class="card-header-action" href="{{ route('backend.courses.lessons.create') }}">
            <i class="cil-plus"></i>
        </a>
    </div>
@endsection

@section('body')
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 70px;"></th>
                <th class="text-center align-middle" style="width: 70px;">On/Off</th>
                <th class="text-center align-middle" style="width: 100px;">Порядок</th>
                <th class="text-center align-middle">Наименование</th>
                <th class="text-center align-middle">Модуль</th>
                <th class="text-center align-middle" style="width: 150px;">Дата создания</th>
                <th class="text-center align-middle" style="width: 100px;">Действия</th>
            </tr>
        </thead>
        <tbody>
        @forelse($lessons as $index => $lesson)
            @php $module = $lesson->module(); @endphp

            <tr>
                <td class="text-center align-middle">{{ ++$index }}</td>

                <td class="text-center align-middle">
                    <i class="cib-discover text-{{ $lesson->good ? 'success' : 'danger' }}"></i>
                </td>

                <td>
                    <div class="input-group input-group-sm">
                        <input
                            class="form-control"
                            id="course-lesson-order-{{ $lesson->id }}"
                            type="text"
                            value="{{ $lesson->order }}"
                            autocomplete="off"
                        >

                        <div class="input-group-append">
                            <button
                                class="input-group-text js-course-lesson-order"
                                data-id="{{ $lesson->id }}"
                            ><i class="cil-save"></i></button>
                        </div>
                    </div>
                </td>

                <td class="align-middle">{{ $lesson->title ?: 'Название не указано' }}</td>

                <td class="align-middle">{{ $module ? $module->title : 'Не выбран модуль' }}</td>

                <td class="text-center align-middle">{{ $lesson->created_at }}</td>

                <td class="text-center align-middle">
                    <div class="btn-group btn-group-sm" role="group">
                        <a
                            class="btn btn-success"
                            href="{{ route('backend.courses.lessons.edit', ['lesson' => $lesson->id]) }}"
                        ><i class="cil-pencil"></i></a>

                        <a
                            class="btn btn-secondary disabled"
                            href="{{ route('backend.courses.lessons.destroy', ['lesson' => $lesson->id]) }}"
                            data-toggle="modal" data-target="#modal-remove-lesson"
                        ><i class="cil-trash"></i></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-center" colspan="7">На текущий момент нет ни одного урока</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection
