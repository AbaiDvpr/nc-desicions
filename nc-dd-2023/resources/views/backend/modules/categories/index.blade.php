@extends('backend.layouts.base')

@section('pageTitle') Категории @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button class="card-header-action border-right mr-2 pr-3 pl-3 btn" type="button"
                data-toggle="collapse" data-target="#collapseExample"
                aria-expanded="false" aria-controls="collapseExample">
            <i class="cil-filter"></i>
        </button>

        <a class="card-header-action btn" href="{{ route('backend.modules.categories.create') }}" title="Добавить Категорию">
            <i class="cil-plus"></i>
        </a>
    </div>
@endsection

@section('body')
    <div class="collapse {{ !request()->input() ?: ' show' }}" id="collapseExample">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('backend.modules.categories.index') }}" method="get" id="filter-categories">
                    <div class="row">
                        <div class="col-6">
                            <x-input::text :name="'name'" :value="request()->input('name', null)" :label="'Наименование'"/>
                        </div>
                        <div class="col-6">
                            <x-input::text :name="'slug'" :value="request()->input('slug', null)" :label="'Алиас'"/>
                        </div>

                        <div class="col-6">
                            <x-input::select :name="'draft'" :data="[null => 'Все', 1 => 'Да', 0 => 'Нет']" :value="request()->input('draft', null)" :label="'Черновик'"/>
                        </div>

                        <div class="col-6">
                            <x-input::select :name="'good'" :data="[null => 'Все', 1 => 'Да', 0 => 'Нет']" :value="request()->input('good', null)" :label="'Включена'"/>
                        </div>

                        <div class="col-6">
                            <x-input::select :name="'deleted'" :data="[null => 'Все', 1 => 'Да', 0 => 'Нет']" :value="request()->input('deleted', null)" :label="'Удаленные'"/>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-footer bg-light">
                <div class="btn-group btn-group-sm float-right">
                    <a href="{{ route('backend.modules.categories.index') }}" class="btn btn-danger">Очистить</a>
                    <button type="submit" class="btn btn-primary" form="filter-categories">Показать</button>
                </div>
            </div>
        </div>
    </div>
    <table class="table table-sm table-bordered">
        <thead>
        <tr>
            <th style="width: 70px;"></th>
            <th class="text-center align-middle" style="width: 70px;">On/Off</th>
            <th class="text-center align-middle">Название категории</th>
            <th class="text-center align-middle">Алиас</th>
            <th class="text-center align-middle" style="width: 150px;">Дата создания</th>
            <th class="text-center align-middle" style="width: 100px;">Действия</th>
        </tr>
        </thead>
        <tbody>
        @php $iteration = 30 * ($categories->currentPage() - 1); @endphp
        @forelse($categories as $category)
            <tr class="{{ $category->draft ? 'table-warning' : '' }}">
                <td class="text-center">{{ ++$iteration }}</td>
                <td class="text-center align-middle">
                    <i class="cib-discover text-{{ $category->good ? 'success' : 'danger' }}"></i>
                </td>
                <td class="align-middle">{{ $category->draft ? 'Черновик ' : '' }}{{ $category->title }}</td>
                <td class="align-middle">{{ $category->slug }}</td>
                <td class="text-center">{{ $category->created_at }}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a class="btn btn-success" href="{{ route('backend.modules.categories.edit', ['category' => $category->id]) }}">
                            <i class="cil-pencil"></i>
                        </a>

                        <a class="btn btn-danger" href="{{ route('backend.modules.categories.delete', ['category' => $category->id]) }}"
                           data-toggle="modal" data-target="#modal-remove-category">
                            <i class="cil-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Еще не создано не одной категории</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$categories"/>
@endsection

@push('modals')
    <div class="modal fade" id="modal-remove-category" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-danger" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="modal-header">
                        <h4 class="modal-title">Удаление категории</h4>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы действительно желаете удалить категорию?</p>
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
                var form = $("#modal-remove-category").find('form');

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
