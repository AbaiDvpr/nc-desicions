@extends('backend.layouts.base')

@section('pageTitle') Список пользователей @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button
            class="card-header-action btn border-right mr-2 pr-3 pl-3" type="button"
            data-toggle="collapse" data-target="#collapseExample"
            aria-expanded="false" aria-controls="collapseExample"
        ><i class="cil-filter"></i></button>

        <a
            class="card-header-action btn"
            href="{{ route('backend.modules.client.create') }}"
            title="Создать пользователя"
        ><i class="cil-plus"></i></a>
    </div>
@endsection

@section('body')
    <div class="collapse {{ !request()->input() ?: ' show' }}" id="collapseExample">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('backend.modules.client.index') }}" method="get" id="filter-users">
                    <div class="row">
                        <div class="col-6">
                            <x-input::text :name="'name'" :value="request()->input('name', null)" :label="'Пользователь'"/>
                        </div>
                        <div class="col-6">
                            <x-input::text :name="'email'" :value="request()->input('email', null)" :label="'E-mail'"/>
                        </div>

                        <div class="col-6">
                            <x-input::date :name="'fromDate'" :value="request()->input('fromDate', null)" :label="'Дата регистрации с:'"/>
                        </div>

                        <div class="col-6">
                            <x-input::date :name="'toDate'" :value="request()->input('toDate', null)" :label="'Дата регистрации по:'"/>
                        </div>

                        <div class="col-6">
                            <x-input::select :name="'admin'" :data="[null => 'Все', 1 => 'Да', 0 => 'Нет']" :value="request()->input('admin', null)" :label="'Вход в админк панель'"/>
                        </div>

                        <div class="col-6">
                            <x-input::select :name="'activated'" :data="[null => 'Все', 1 => 'Да', 0 => 'Нет']" :value="request()->input('activated', null)" :label="'Активирован'"/>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-footer bg-light">
                <div class="btn-group btn-group-sm float-right">
                    <a href="{{ route('backend.modules.client.index') }}" class="btn btn-danger">Очистить</a>
                    <button type="submit" class="btn btn-primary" form="filter-users">Показать</button>
                </div>
            </div>
        </div>
    </div>

    <table class="table table-sm table-bordered">
        <thead>
            <tr class="text-center">
                <th class="align-middle" style="width: 50px;"></th>
                <th class="align-middle" style="width: 80px;">Активи рован</th>
                <th class="align-middle">Пользователь</th>
                <th class="align-middle">E-mail</th>
                <th class="align-middle">Телефон</th>
                <th class="align-middle">Тарифный план</th>
                <th class="align-middle">Баланс</th>
                <th class="align-middle" style="width: 150px;">Дата регистрации</th>
                <th class="align-middle" style="width: 100px;">Действия</th>
            </tr>
        </thead>

        <tbody>
        @php $iteration = 30 * ($users->currentPage() - 1); @endphp

        @forelse ($users as $user)
            @php $userTariff = $user->tariff(); @endphp

            <tr class="{{ $user->fullAccess ? 'table-info' : '' }}">
                <td class="text-center">{{ ++$iteration }}</td>

                <td class="text-center align-middle">
                    <i class="cib-discover text-{{ $user->isActivated() ? 'success' : 'danger' }}"></i>
                </td>

                <td>{{ $user->name }}</td>

                <td>{{ $user->email }}</td>

                <td>{{ formatPhone($user->mobile ?? '') }}</td>

                <td>
                    @if ($userTariff)
                        {{ $userTariff->getName() }}
                        <span class="text-muted">
                            ({{ mb_strtolower(config('tariffs.types')[$userTariff->getType()]) }})
                        </span>
                    @else
                        <span class="text-muted">Отсутствует</span>
                    @endif
                </td>

                <td class="text-right">
                    {{ number_format($user->balance, 0, '.', ' ') }}
                </td>

                <td class="text-center">{{ $user->created_at->format('Y-m-d H:i') }}</td>

                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('backend.modules.client.payment.index', ['user' => $user->id]) }}"
                           class="btn btn-primary" title="История баланса">
                            <i class="cil-dollar"></i>
                        </a>

                        <a href="{{ route('backend.modules.client.edit', ['user' => $user->id]) }}"
                               class="btn btn-success" title="Изменение данных пользователя">
                            <i class="cil-pencil"></i>
                        </a>

                        <a href="{{ route('backend.modules.client.auth', ['user' => $user->id]) }}"
                           class="btn {{ $user->isActivated() ? 'btn-danger' : 'btn-secondary disabled' }}"
                           title="Войти в личный кабинет"
                           target="_blank">
                            <i class="bi bi-shield-slash d-flex"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Нет пользователей</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$users"/>
@endsection
