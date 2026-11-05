@extends('backend.layouts.base')

@section('pageTitle') Список пользователей @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button class="card-header-action border-right mr-2 pr-3 pl-3 btn" type="button"
                data-toggle="collapse" data-target="#collapseExample"
                aria-expanded="false" aria-controls="collapseExample">
            <i class="cil-filter"></i>
        </button>

        <a class="card-header-action btn" href="{{ route('backend.users.create') }}" title="Создать пользователя">
            <i class="cil-plus"></i>
        </a>
    </div>
@endsection

@section('body')
    <div class="collapse {{ !request()->input() ?: ' show' }}" id="collapseExample">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('backend.users.index') }}" method="get" id="filter-users">
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
                    <a href="{{ route('backend.users.index') }}" class="btn btn-danger">Очистить</a>
                    <button type="submit" class="btn btn-primary" form="filter-users">Показать</button>
                </div>
            </div>
        </div>
    </div>

    <table class="table table-sm table-bordered">
        <thead>
        <tr>
            <th style="width: 50px;"></th>
            <th class="text-center align-middle" style="width: 80px;">Активи рован</th>
            <th class="text-center align-middle" style="width: 80px;">Админ</th>
            <th class="align-middle">Пользователь</th>
            <th class="align-middle">E-mail</th>
            <th class="align-middle">Телефон</th>
            <th class="text-center align-middle">Роль</th>
            <th class="text-center align-middle" style="width: 150px;">Дата регистрации</th>
            <th class="text-center align-middle" style="width: 100px;">Действия</th>
        </tr>
        </thead>
        <tbody>
        @php $iteration = 30 * ($users->currentPage() - 1); @endphp
        @forelse($users as $user)
            <tr>
                <td class="text-center">{{ ++$iteration }}</td>
                <td class="text-center align-middle">
                    <i class="cib-discover text-{{ $user->isActivated() ? 'success' : 'danger' }}"></i>
                </td>
                <td class="text-center">{{ $user->admin ? 'Да' : 'Нет' }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->mobile }}</td>
                <td class="text-center">
                    @php $roles = $user->roles; @endphp
                    @forelse($roles as $role)
                        {{ $role->name }}
                    @empty
                    @endforelse
                </td>

                <td class="text-center">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a class="btn btn-success" href="{{ route('backend.users.edit', ['user' => $user->id]) }}">
                            <i class="cil-pencil"></i>
                        </a>

                        <a class="btn btn-danger" href="{{ route('backend.users.destroy', ['user' => $user->id]) }}"
                           data-toggle="modal" data-target="#modal-remove-user">
                            <i class="cil-trash"></i>
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

@push('modals')
    <div class="modal fade" id="modal-remove-user" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-danger" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="modal-header">
                        <h4 class="modal-title">Удаление пользователя</h4>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы действительно желаете удалить пользователя?</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Отменить</button>
                        <button class="btn btn-danger" type="submit">Удалить</button>
                    </div>
                </form>
            </div>
            <!-- /.modal-content-->
        </div>
        <!-- /.modal-dialog-->
    </div>
@endpush

@push('js')
    <script type="text/javascript">
        $(document).ready(function() {
            $("a[data-toggle=modal]").click(function () {
                var form = $("#modal-remove-user").find('form');

                form.attr('action', $(this).attr('href'));
            });

            var modalRemoveRole = document.getElementById('modal-remove-user');
            modalRemoveRole.addEventListener('hidden.coreui.modal', function (event) {
                var form = modalRemoveRole.querySelector('form');

                form.setAttribute('action', '');
            });
        });
    </script>
@endpush
