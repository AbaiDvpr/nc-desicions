<div class="card">
    <div class="card-header">
        <i class="cil-casino mr-2"></i>
        Информация о финансовых моделях
    </div>

    <div class="card-body">
        <div class="row justify-content-around">
            <div class="col-sm-3">
                <div class="c-callout c-callout-info">
                    <small class="text-muted">Всего</small><br>
                    <strong class="h4">{{ $projectsCount['all'] }}</strong>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="c-callout c-callout-danger">
                    <small class="text-muted">Завершено</small><br>
                    <strong class="h4">{{ $projectsCount['completed'] }}</strong>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="c-callout c-callout-warning">
                    <small class="text-muted">Не завершено</small><br>
                    <strong class="h4">{{ $projectsCount['unCompleted'] }}</strong>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="c-callout c-callout-success">
                    <small class="text-muted">За последнюю неделю</small><br>
                    <strong class="h4">{{ $projectsCount['byPeriod'] }}</strong>
                </div>
            </div>
        </div>

        <div class="row justify-content-around">
            <div class="col-sm-12">
                @if ($projects->count())
                    <table class="table table-sm table-bordered">
                        <thead>
                        <tr class="text-center">
                            <th class="align-middle" style="width: 80px;">Статус</th>
                            <th class="align-middle">Наименование</th>
                            <th class="align-middle">Пользователь</th>
                            <th class="align-middle">Дата создания</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($projects as $project)
                            <tr>
                                <td class="text-center align-middle">{{ $project->getStatusName() }}</td>
                                <td class="align-middle">
                                    <a href="{{ route('report.get', ['uuid' => $project->getUuid() ]) }}">
                                        <b>{{ getProjectFormValue('form', $project->getForm()) }}:</b>
                                        {!! $project->name ?? '<span class="text-muted">Название еще не заполнено</span>' !!}
                                    </a>
                                </td>
                                <td class="text-center">{{ $project->user->name }}</td>
                                <td class="text-center align-middle">{{ $project->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center">
                        Не удалось получить последние проекты
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
