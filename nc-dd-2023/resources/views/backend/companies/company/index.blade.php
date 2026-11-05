@extends('backend.layouts.base')

@section('pageTitle') Каталог организаций @endsection

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
    <div class="collapse {{ !request()->input() ?: 'show' }}" id="collapseExample">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('backend.companies.company.index') }}" method="get" id="filter-companies">
                    <div class="row">
                        <div class="col-6">
                            <x-input::text :name="'name'"
                                           :value="request()->input('name')"
                                           :label="'Наименование организации'"/>
                        </div>

                        <div class="col-6">
                            <x-input::text :name="'userId'"
                                           :value="request()->input('userId')"
                                           :label="'ID Пользователя'"/>
                        </div>

                        <div class="col-6">
                            <x-input::select :name="'currency'"
                                             :data="array_merge([null => 'Все валюты'], $currency)"
                                             :value="request()->input('currency')"
                                             :label="'Валюта'"/>
                        </div>

                        {{--<div class="col-6">
                            <x-input::select :name="'tariffId'"
                                             :data="$tariffOptions"
                                             :value="request()->input('tariffId')"
                                             :label="'Тариф'"/>
                        </div>--}}

                        <div class="col-6">
                            <x-input::date :name="'fromDate'"
                                           :value="request()->input('fromDate')"
                                           :label="'Дата регистрации от:'"/>
                        </div>

                        <div class="col-6">
                            <x-input::date :name="'toDate'"
                                           :value="request()->input('toDate')"
                                           :label="'Дата регистрации до:'"/>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-footer bg-light">
                <div class="btn-group btn-group-sm float-right">
                    <a href="{{ route('backend.companies.company.index') }}" class="btn btn-danger">Очистить</a>
                    <button type="submit" class="btn btn-primary" form="filter-companies">Показать</button>
                </div>
            </div>
        </div>
    </div>

    <table class="table table-sm table-bordered">
        <thead>
        <tr>
            <th class="text-center align-middle" style="width: 50px;"></th>
            <th class="text-center align-middle">Наименование организации</th>
            <th class="text-center align-middle">Валюта</th>
            <th class="text-center align-middle">Пользователь</th>
            {{--<th class="text-center align-middle">Тариф</th>--}}
            <th class="text-center align-middle" style="width: 150px;">Дата создания</th>
        </tr>
        </thead>
        <tbody>
        @php $iteration = 30 * ($companies->currentPage() - 1); @endphp
        @forelse($companies as $company)
            <tr>
                <td class="text-center align-middle">{{ ++$iteration }}</td>
                <td class="text-left">{{ $company->name }}</td>
                <td class="align-middle">{{ $company->currency }}</td>
                <td class="align-middle">{{ $company->user->name }}</td>
                {{--<td class="text-center">{{ $company->tariff->name ?? 'тариф не выбран' }}</td>--}}
                <td class="text-center align-middle">{{ $company->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">На текущий момент нет ни одной организации</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$companies"/>
@endsection
