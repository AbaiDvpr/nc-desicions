@extends('backend.layouts.base')

@section('pageTitle') Каталог проектов @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button class="card-header-action btn" type="button"
                data-toggle="collapse" data-target="#collapseExample"
                aria-expanded="false" aria-controls="collapseExample">
            <i class="cil-filter"></i>
        </button>
    </div>
@endsection

@section('body')
    <div class="collapse {{ !request()->input() ?: ' show' }}" id="collapseExample">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('backend.projects.project.index') }}" method="get" id="filter-projects">
                    <div class="row">
                        <div class="col-6">
                            <x-input::select :name="'status'"
                                             :data="array_merge([null => 'Все'], $statuses)"
                                             :value="request()->input('status', null)"
                                             :label="'Статус'"/>
                        </div>

                        <div class="col-6">
                            <x-input::text :name="'name'"
                                           :value="request()->input('name', null)"
                                           :label="'Наименование'"/>
                        </div>

                        <div class="col-6">
                            <x-input::select :name="'tax_regime'"
                                             :data="array_merge(['0' => 'Все'], $tax_regime)"
                                             :value="request()->input('tax_regime', null)"
                                             :label="'Режим налогообложения'"/>
                        </div>

                        {{--<div class="col-6">
                            <x-input::select :name="'tariffId'"
                                             :data="$tariffOptions"
                                             :value="request()->input('tariffId', null)"
                                             :label="'Тариф'"/>
                        </div>--}}

                        <div class="col-6">
                            <x-input::text :name="'userId'"
                                           :value="request()->input('userId', null)"
                                           :label="'ID пользователя'"/>
                        </div>

                        <div class="col-6">
                            <x-input::date :name="'fromDate'"
                                           :value="request()->input('fromDate', null)"
                                           :label="'Дата создания от:'"/>
                        </div>

                        <div class="col-6">
                            <x-input::date :name="'toDate'"
                                           :value="request()->input('toDate', null)"
                                           :label="'Дата создания до:'"/>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-footer bg-light">
                <div class="btn-group btn-group-sm float-right">
                    <a href="{{ route('backend.projects.project.index') }}" class="btn btn-danger">Очистить</a>
                    <button type="submit" class="btn btn-primary" form="filter-projects">Показать</button>
                </div>
            </div>
        </div>
    </div>

    <table class="table table-sm table-bordered">
        <thead>
        <tr>
            <th class="text-center align-middle" style="width: 50px;"></th>
            <th class="text-center align-middle" style="width: 80px;">Шаг</th>
            <th class="text-center align-middle" style="width: 80px;">Статус</th>
            <th class="text-center align-middle">Наименование</th>
            <th class="text-center align-middle">Режим налогообложения</th>
            <th class="text-center align-middle">Расчет в валюте</th>
            <th class="text-center align-middle">График работы</th>
            {{--<th class="text-center align-middle">Тариф</th>--}}
            <th class="text-center align-middle">Пользователь</th>
            <th class="text-center align-middle" style="width: 150px;">Дата создания</th>
        </tr>
        </thead>
        <tbody>
        @php $iteration = 30 * ($projects->currentPage() - 1); @endphp
        @forelse($projects as $project)
            <tr>
                <td class="text-center align-middle">{{ ++$iteration }}</td>
                <td class="text-center align-middle">{{ $project->step }}</td>
                <td class="text-center align-middle">{{ $project->getStatusName() }}</td>
                <td class="align-middle">
                    <a href="{{ route('report.get', ['uuid' => $project->getUuid() ]) }}">
                        <b>{{ getProjectFormValue('form', $project->getForm()) }}:</b>
                        {!! $project->name ?? '<span class="text-muted">Название еще не заполнено</span>' !!}
                    </a>
                </td>
                <td class="align-middle">{{ getProjectFormValue('tax_regime', $project->getTaxRegime()) }}</td>
                <td class="align-middle">{{ getProjectFormValue('currency', $project->getCurrency()) }}</td>
                <td class="text-center">{{ getProjectFormValue('schedule', $project->getSchedule()) }}</td>
                {{--<td class="text-center">
                    {{ Str::limit($project->tariff()?->getName() ?? 'тариф не выбран', 70) }}
                </td>--}}
                <td class="text-center">{{ $project->user->name }}</td>
                <td class="text-center align-middle">{{ $project->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10">На текущий момент нет ни одного проекта</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$projects"/>
@endsection
