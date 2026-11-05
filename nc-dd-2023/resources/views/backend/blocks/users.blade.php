<div class="card">
    <div class="card-header">
        <i class="cil-user mr-2"></i>
        Информация о пользователях
    </div>
    <div class="card-body">
        <div class="row justify-content-around">
            <div class="col-sm-6">
                <div class="c-callout c-callout-info">
                    <small class="text-muted">Всего пользователей</small><br>
                    <strong class="h4">{{ $usersCount }}</strong>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="c-callout c-callout-danger">
                    <small class="text-muted">За последнюю неделю</small><br>
                    <strong class="h4">{{ $usersCountLastWeek }}</strong>
                </div>
            </div>
        </div>
        <div class="row justify-content-around">
            <div class="col-sm-12">
                @if ($users->count())
                    <table class="table table-sm table-bordered">
                        <thead>
                        <tr class="text-center">
                            <th class="align-middle" style="width: 25px;">Активирован</th>
                            <th class="align-middle">Пользователь</th>
                            <th class="align-middle">E-mail</th>
                            <th class="align-middle" style="width: 100px;">Дата регистрации</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="text-center">
                                    <i class="cib-discover text-{{ $user->isActivated() ? 'success' : 'danger' }}"></i>
                                </td>
                                <td><small class="h7">{{ $user->name }}</small></td>
                                <td class="text-center">{{ $user->email }}</td>
                                <td class="text-center">{{ $user->created_at->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center">
                        Не удалось получить последних пользователей
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
