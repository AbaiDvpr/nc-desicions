@extends('backend.layouts.base')

@section('pageTitle', 'Обратная связь')

@section('body')
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 50px;"></th>
                <th class="text-center align-middle" style="width: 120px;">Статус</th>
                <th class="text-center align-middle" style="width: 120px;">Язык заявки</th>
                <th class="text-center align-middle">Имя</th>
                <th class="text-center align-middle">Телефон</th>
                <th class="text-center align-middle" style="width: 150px;">Дата создания</th>
                <th class="text-center align-middle" style="width: 100px;">Действия</th>
            </tr>
        </thead>

        <tbody>
        @php $iteration = 20 * ($feedbacks->currentPage() - 1); @endphp

        @forelse ($feedbacks as $feedback)
            <tr>
                <td class="align-middle text-center">{{ ++$iteration }}</td>

                <td class="align-middle text-center">
                    {{ config('feedbacks.statuses')[$feedback->status] }}
                </td>

                <td class="align-middle text-center">{{ $feedback->lang }}</td>

                <td class="align-middle">{{ $feedback->name }}</td>

                <td class="align-middle">{{ $feedback->phone }}</td>

                <td class="align-middle text-center">{{ $feedback->created_at }}</td>

                <td class="align-middle text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a
                            class="btn btn-success"
                            href="{{ route('backend.modules.feedbacks.edit', ['feedback' => $feedback->id]) }}"
                        ><i class="cil-pencil"></i></a>

                        <a
                            class="btn btn-danger"
                            href="{{ route('backend.modules.feedbacks.destroy', ['feedback' => $feedback->id]) }}"
                            data-toggle="modal"
                            data-target="#modal-remove-feedback"
                            data-name="{{ $feedback->name }}"
                        ><i class="cil-trash"></i></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-center" colspan="7">На данный момент заявок нет</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$feedbacks" />
@endsection

@push('modals')
    <div class="modal fade" id="modal-remove-feedback" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md modal-danger" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}

                    <div class="modal-header">
                        <h4 class="modal-title">Удаление заявки</h4>

                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        Вы действительно желаете удалить заявку от <b id="modal-remove-feedback-name"></b>?
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
        /**
         * Модальное окно для удаления заявки
         */
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('modal-remove-feedback');

            if (!modal) return;

            var form = modal.querySelector('form');
            var name = modal.querySelector('#modal-remove-feedback-name');

            document.addEventListener('click', function(event) {
                var element = event.target.closest('a[data-target="#modal-remove-feedback"]');

                if (!element) return;

                form.setAttribute('action', element.href);
                name.textContent = element.dataset.name;
            });

            modal.addEventListener('hidden.coreui.modal', function () {
                form.setAttribute('action', '');
                name.textContent = '';
            });
        });
    </script>
@endpush
