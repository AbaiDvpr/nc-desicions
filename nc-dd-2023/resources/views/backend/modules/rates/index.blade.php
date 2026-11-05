@extends('backend.layouts.base')

@section('pageTitle') Курсы валют @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <a class="card-header-action border-right mr-2 pr-3 pl-3 btn" href="{{ route('backend.rates.getrates') }}" title="Получить курсы валют">
            <i class="cil-sync"></i>
        </a>

        <a class="card-header-action btn" href="{{ route('backend.rates.create') }}" title="Создать валюты">
            <i class="cil-plus"></i>
        </a>
    </div>
@endsection

@section('body')
    <table class="table table-sm table-bordered">
        <thead>
        <tr>
            <th style="width: 50px;"></th>
            <th class="text-center align-middle" style="width: 150px;">Актуально</th>
            <th class="text-center align-middle">Доллар</th>
            <th class="text-center align-middle">Евро</th>
            <th class="text-center align-middle">Рубль</th>
            <th class="text-center align-middle" style="width: 150px;">Получено</th>
            <th class="text-center align-middle" style="width: 100px;">Действия</th>
        </tr>
        </thead>
        <tbody>
        @php $iteration = 30 * ($rates->currentPage() - 1); @endphp
        @forelse($rates as $rate)
            <tr>
                <td class="text-center">{{ ++$iteration }}</td>
                <td class="text-center">{{ $rate->relevant }}</td>
                <td class="text-center align-middle">{{ $rate->amount_USD }} тенге</td>
                <td class="text-center align-middle">{{ $rate->amount_EUR }} тенге</td>
                <td class="text-center align-middle">{{ $rate->amount_RUB }} тенге</td>
                <td class="text-center">{{ $rate->created_at }}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a class="btn btn-success" href="{{ route('backend.rates.edit', ['rate' => $rate->id]) }}">
                            <i class="cil-pencil"></i>
                        </a>

                        <a class="btn btn-danger" href="{{ route('backend.rates.delete', ['rate' => $rate->id]) }}"
                           data-toggle="modal" data-target="#modal-remove-rate">
                            <i class="cil-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Нет Курсов валют</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$rates"/>
@endsection

@push('modals')
    <div class="modal fade" id="modal-remove-rate" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-danger" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="modal-header">
                        <h4 class="modal-title">Удаление Курса</h4>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы действительно желаете удалить курс?</p>
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
                var form = $("#modal-remove-rate").find('form');

                form.attr('action', $(this).attr('href'));
            });

            var modalRemoveRole = document.getElementById('modal-remove-rate');
            modalRemoveRole.addEventListener('hidden.coreui.modal', function (event) {
                var form = modalRemoveRole.querySelector('form');

                form.setAttribute('action', '');
            });
        });
    </script>
@endpush
