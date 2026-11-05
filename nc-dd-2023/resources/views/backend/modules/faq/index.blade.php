@extends('backend.layouts.base')

@section('pageTitle') Вопросы и ответы @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button class="card-header-action btn" type="button"
                data-toggle="collapse" data-target="#collapseExample"
                aria-expanded="false" aria-controls="collapseExample">
            <i class="cil-filter"></i>
        </button>
    </div>
@endsection

@section('body')
    <div class="collapse {{ !request()->input() ?: ' show' }}" id="collapseExample">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('backend.modules.faq.index') }}" method="get" id="filter-faq">
                    <div class="row">
                        <div class="col-6">
                            <x-input::text :name="'email'" :value="request()->input('email', null)" :label="'E-mail'"/>
                        </div>

                        <div class="col-6">
                            <x-input::select :name="'lang'" :data="[null => 'Все', 'en' => 'Eng', 'ru' => 'Rus', 'kk' => 'Kaz']" :value="request()->input('lang', null)" :label="'Язык вопроса'"/>
                        </div>

                        <div class="col-6">
                            <x-input::select :name="'main'" :data="[null => 'Все', 1 => 'Да', 0 => 'Нет']" :value="request()->input('main', null)" :label="'На главной'"/>
                        </div>

                        <div class="col-6">
                            <x-input::select :name="'published'" :data="[null => 'Все', 1 => 'Опубликован', 0 => 'Не опубликован']" :value="request()->input('published', null)" :label="'Статус'"/>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-footer bg-light">
                <div class="btn-group btn-group-sm float-right">
                    <a href="{{ route('backend.modules.faq.index') }}" class="btn btn-danger">Очистить</a>
                    <button type="submit" class="btn btn-primary" form="filter-faq">Показать</button>
                </div>
            </div>
        </div>
    </div>
    <table class="table table-sm table-bordered">
        <thead>
        <tr>
            <th style="width: 50px;"></th>
            <th class="text-center align-middle" style="width: 70px;">Статус</th>
            <th class="text-center align-middle" style="width: 70px;">Язык вопроса</th>
            <th class="text-center align-middle">ФИО</th>
            <th class="text-center align-middle">E-mail</th>
            <th class="text-center align-middle" style="width: 150px;">Дата создания</th>
            <th class="text-center align-middle" style="width: 100px;">Действия</th>
        </tr>
        </thead>
        <tbody>
        @php $iteration = 30 * ($faq->currentPage() - 1); @endphp
        @forelse($faq as $fa)
            <tr class="{{ $fa->main ? 'table-success' : '' }}">
                <td class="text-center">{{ ++$iteration }}</td>
                <td class="text-center align-middle">
                    <i class="cib-discover text-{{ $fa->published ? 'success' : 'danger' }}"></i>
                </td>
                <td class="align-middle">{{ $fa->lang }}</td>
                <td class="align-middle">{{ $fa->name }} {{ $fa->surname }}</td>
                <td class="align-middle">{{ $fa->email }}</td>
                <td class="text-center">{{ $fa->created_at }}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a class="btn btn-success" href="{{ route('backend.modules.faq.edit', ['faq' => $fa->id]) }}">
                            <i class="cil-pencil"></i>
                        </a>

                        <a class="btn btn-danger" href="{{ route('backend.modules.faq.delete', ['faq' => $fa->id]) }}"
                           data-toggle="modal" data-target="#modal-remove-faq">
                            <i class="cil-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Еще не создано не одного вопроса</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$faq"/>
@endsection

@push('modals')
    <div class="modal fade" id="modal-remove-faq" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-danger" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="modal-header">
                        <h4 class="modal-title">Удаление страницы</h4>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы действительно желаете удалить страницу?</p>
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
                var form = $("#modal-remove-faq").find('form');

                form.attr('action', $(this).attr('href'));
            });

            var modalRemoveRole = document.getElementById('modal-remove-faq');
            modalRemoveRole.addEventListener('hidden.coreui.modal', function (event) {
                var form = modalRemoveRole.querySelector('form');

                form.setAttribute('action', '');
            });
        });
    </script>
@endpush
