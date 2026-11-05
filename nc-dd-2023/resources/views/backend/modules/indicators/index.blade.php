@extends('backend.layouts.base')

@section('pageTitle', 'Показатели')

@section('cardHeaderActions')
    <div class="card-header-actions">
        <a
            class="card-header-action border-right mr-2 pr-3 pl-3 btn"
            href="{{ route('backend.indicators.getIndicators') }}"
            title="Получить показатель"
        ><i class="cil-sync"></i></a>
        <a
            class="card-header-action btn"
            href="{{ route('backend.indicators.create') }}"
            title="Создать показатель"
        ><i class="cil-plus"></i></a>
    </div>
@endsection

@section('body')
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 50px;"></th>
                <th class="text-center align-middle" style="width: 150px;">Актуально</th>
                <th class="text-center align-middle">Цель по инфляции</th>
                <th class="text-center align-middle">Годовая инфляция</th>
                <th class="text-center align-middle">Базовая ставка</th>
                <th class="text-center align-middle">TONIA</th>
                <th class="text-center align-middle" style="width: 150px;">Получено</th>
                <th class="text-center align-middle" style="width: 100px;">Действия</th>
            </tr>
        </thead>
        <tbody>
            @php $iteration = 20 * ($indicators->currentPage() - 1); @endphp

            @forelse($indicators as $indicator)
                <tr>
                    <td class="text-center align-middle">{{ ++$iteration }}</td>
                    <td class="text-center align-middle">{{ date('d.m.Y', strtotime($indicator->relevant)) }}</td>
                    <td class="text-center align-middle">{{ $indicator->inflation }}%</td>
                    <td class="text-center align-middle">{{ $indicator->annualInflation }}%</td>
                    <td class="text-center align-middle">{{ $indicator->baseRate }}%</td>
                    <td class="text-center align-middle">{{ $indicator->tonia }}%</td>
                    <td class="text-center align-middle">{{ date('d.m.Y H:i:s', strtotime($indicator->created_at)) }}</td>
                    <td class="text-center align-middle">
                        <div class="btn-group btn-group-sm" role="group">
                            <a
                                class="btn btn-success"
                                href="{{ route('backend.indicators.edit', ['indicator' => $indicator->id]) }}"
                            ><i class="cil-pencil"></i></a>
                            <a
                                class="btn btn-danger"
                                href="{{ route('backend.indicators.destroy', ['indicator' => $indicator->id]) }}"
                                data-toggle="modal" data-target="#modal-remove-indicator"
                            ><i class="cil-trash"></i></a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center align-middle" colspan="7">В данный момент нет показателей</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$indicators"/>
@endsection

@push('modals')
    <div class="modal fade" id="modal-remove-indicator" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md modal-danger" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="modal-header">
                        <h4 class="modal-title">Удаление показателя</h4>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы действительно желаете удалить показатель?</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Отменить</button>
                        <button class="btn btn-danger" type="submit">Удалить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('js')
    <script>
        $(document).ready(function() {
            var modal = document.getElementById('modal-remove-indicator');
            var form = $('#modal-remove-indicator').find('form');

            if (!form || !modal) return;

            $('a[data-toggle=modal]').click(function () {
                form.attr('action', $(this).attr('href'));
            });

            modal.addEventListener('hidden.coreui.modal', function () {
                form.attr('action', '');
            });
        });
    </script>
@endpush
