@extends('backend.layouts.base')

@section('pageTitle') Банки @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <a class="card-header-action btn" href="{{ route('backend.handbook.bank.create') }}" title="Создать банк">
            <i class="cil-plus"></i>
        </a>
    </div>
@endsection

@section('body')
    <table class="table table-sm table-bordered">
        <thead>
        <tr>
            <th style="width: 70px;"></th>
            <th class="text-center align-middle">Название банка</th>
            <th class="text-center align-middle">IBAN</th>
            <th class="text-center align-middle">БИН</th>
            <th class="text-center align-middle">SWIFT/БИК</th>
            <th class="text-center align-middle" style="width: 150px;">Дата создания</th>
            <th class="text-center align-middle"></th>
        </tr>
        </thead>
        <tbody>
        @php $iteration = $banks->perPage() * ($banks->currentPage() - 1); @endphp
        @forelse($banks as $bank)
            @php $presenter = $bank->getPresenter(); @endphp

            <tr>
                <td class="text-center">{{ ++$iteration }}</td>
                <td class="align-middle">{{ $bank->getName() }}</td>
                <td class="align-middle">{{ $presenter->getIban() }}</td>
                <td class="align-middle">{{ $presenter->getBin() }}</td>
                <td class="align-middle">{{ $presenter->getBik() }}</td>
                <td class="text-center">{{ $bank->created_at }}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a class="btn btn-success" href="{{ route('backend.handbook.bank.edit', ['id' => $bank->id]) }}">
                            <i class="cil-pencil"></i>
                        </a>

                        <a class="btn btn-danger" href="{{ route('backend.handbook.bank.edit', ['id' => $bank->id]) }}"
                           data-toggle="modal" data-target="#remove-bank">
                            <i class="cil-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Справочник пуст</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$banks"/>
@endsection

@push('modals')
    <div class="modal fade" id="remove-bank" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-danger" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="modal-header">
                        <h4 class="modal-title">Удаление банка</h4>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы действительно желаете удалить банк из справочника?</p>
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
                var form = $("#remove-bank").find('form');

                form.attr('action', $(this).attr('href'));
            });

            var modalRemoveRole = document.getElementById('modal-remove-category');
            modalRemoveRole.addEventListener('hidden.coreui.modal', function (event) {
                var form = modalRemoveRole.querySelector('form');

                form.setAttribute('action', '');
            });
        });
    </script>
@endpush
