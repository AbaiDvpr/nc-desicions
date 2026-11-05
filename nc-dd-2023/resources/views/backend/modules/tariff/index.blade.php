@extends('backend.layouts.base')

@section('pageTitle') Тарифные планы @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <a class="card-header-action btn" href="{{ route('backend.modules.tariff.create') }}" title="Создать новый тарифный план">
            <i class="cil-plus"></i>
        </a>
    </div>
@endsection

@section('body')
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 50px;"></th>
                <th class="align-middle text-center" style="width: 50px;">Вкл</th>
                <th class="align-middle text-center">По умолчанию</th>
                <th class="align-middle text-center" style="min-width: 120px;">Тип</th>
                <th class="align-middle text-center" style="min-width: 100px;">Название</th>
                <th class="align-middle">Описание</th>
                <th class="align-middle" style="min-width: 70px;">Цена тг.</th>
                <th class="align-middle text-center" style="width: 150px;">Дата создания</th>
                <th class="align-middle text-center" style="width: 100px;">Действия</th>
            </tr>
        </thead>
        <tbody>
            @php $iteration = 30 * ($tariffs->currentPage() - 1); @endphp

            @forelse($tariffs as $tariff)
                <tr>
                    <td class="text-center">{{ ++$iteration }}</td>

                    <td class="text-center">{{ $tariff->good ? 'Да' : 'Нет' }}</td>

                    <td class="text-center">{{ $tariff->default ? 'Да' : 'Нет' }}</td>

                    <td class="text-center">{{ config('tariffs.types.' . $tariff->type) }}</td>

                    <td class="text-center">{{ $tariff->name }}</td>

                    <td>{!! Str::limit(strip_tags($tariff->description), 150) !!}</td>

                    <td style="white-space: nowrap;">{{ number_format($tariff->price, 0, null, ' ') }}</td>

                    <td class="text-center">{{ $tariff->created_at->format('Y-m-d H:i') }}</td>

                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <a
                                class="btn btn-success"
                                href="{{ route('backend.modules.tariff.edit', ['tariff' => $tariff->id]) }}"
                            ><i class="cil-pencil"></i></a>

                            <a
                                class="btn btn-danger"
                                href="{{ route('backend.modules.tariff.delete', ['tariff' => $tariff->id]) }}"
                                data-toggle="modal"
                                data-target="#modal-remove-tariff"
                            ><i class="cil-trash"></i></a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Нет тарифных планов</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$tariffs"/>
@endsection

@push('modals')
    <div class="modal fade" id="modal-remove-tariff" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-danger" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="modal-header">
                        <h4 class="modal-title">Удаление тарифного плана</h4>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы действительно желаете удалить данный тарифный план?</p>
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
                var form = $("#modal-remove-tariff").find('form');

                form.attr('action', $(this).attr('href'));
            });

            var modalRemoveRole = document.getElementById('modal-remove-tariff');
            modalRemoveRole.addEventListener('hidden.coreui.modal', function (event) {
                var form = modalRemoveRole.querySelector('form');

                form.setAttribute('action', '');
            });
        });
    </script>
@endpush
