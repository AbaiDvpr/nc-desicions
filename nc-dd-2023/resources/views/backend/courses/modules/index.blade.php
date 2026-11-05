@extends('backend.layouts.base')

@push('js')
    <script src="{{ mix('admin/js/courses/modules/order.js') }}"></script>
@endpush

@section('pageTitle')
    <i class="cil-view-module"></i>

    Обучение - список модулей
@endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <a class="card-header-action" href="{{ route('backend.courses.modules.create') }}">
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
                <th class="text-center align-middle" style="width: 150px;">Дата создания</th>
                <th class="text-center align-middle" style="width: 100px;">Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($modules as $index => $module)
                <tr>
                    <td class="text-center">{{ ++$index }}</td>

                    <td class="text-center align-middle">
                        <i class="cib-discover text-{{ $module->good ? 'success' : 'danger' }}"></i>
                    </td>

                    <td>
                        <div class="input-group input-group-sm">
                            <input
                                class="form-control"
                                id="course-module-order-{{ $module->id }}"
                                type="text"
                                value="{{ $module->order }}"
                                autocomplete="off"
                            >

                            <div class="input-group-append">
                                <button
                                    class="input-group-text js-course-module-order"
                                    data-id="{{ $module->id }}"
                                ><i class="cil-save"></i></button>
                            </div>
                        </div>
                    </td>

                    <td>{{ $module->title ?: 'Название не указано' }}</td>

                    <td class="text-center">{{ $module->created_at }}</td>

                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <a
                                class="btn btn-success"
                                href="{{ route('backend.courses.modules.edit', ['module' => $module->id]) }}"
                            ><i class="cil-pencil"></i></a>

                            <a
                                class="btn btn-secondary disabled"
                                href="{{ route('backend.courses.modules.destroy', ['module' => $module->id]) }}"
                                data-toggle="modal" data-target="#modal-remove-module"
                            ><i class="cil-trash"></i></a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="6">На текущий момент нет ни одного модуля</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
