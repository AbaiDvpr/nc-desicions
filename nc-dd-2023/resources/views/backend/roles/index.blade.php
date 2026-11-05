@extends('backend.layouts.base')

@section('pageTitle') Список ролей @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <a class="card-header-action" href="{{ route('backend.roles.create') }}">
            <i class="cil-plus"></i>
        </a>
    </div>
@endsection

@section('body')
    <table class="table table-sm table-bordered">
        <thead>
        <tr>
            <th style="width: 50px;"></th>
            <th>Имя роли</th>
            <th>Имя охранника</th>
            <th class="text-center" style="width: 100px">Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse($roles as $role)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ $role->name }}</td>
                <td>{{ $role->guard_name }}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a class="btn btn-success" href="{{ route('backend.roles.edit', ['role' => $role->id]) }}">
                            <i class="cil-pencil"></i>
                        </a>

                        <a class="btn btn-danger" href="{{ route('backend.roles.delete', ['role' => $role->id]) }}"
                           data-toggle="modal" data-target="#modal-remove-role">
                            <i class="cil-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">Нет записей</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection

@push('modals')
    <div class="modal fade" id="modal-remove-role" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-danger" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="modal-header">
                        <h4 class="modal-title">Удаление роли</h4>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы действительно желаете удалить роль?</p>
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
                var form = $("#modal-remove-role").find('form');

                form.attr('action', $(this).attr('href'));
            });

            var modalRemoveRole = document.getElementById('modal-remove-role');
            modalRemoveRole.addEventListener('hidden.coreui.modal', function (event) {
                var form = modalRemoveRole.querySelector('form');

                form.setAttribute('action', '');
            });
        });
    </script>
@endpush
