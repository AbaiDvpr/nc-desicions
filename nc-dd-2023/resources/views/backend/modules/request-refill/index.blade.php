@extends('backend.layouts.base')

@section('pageTitle') Запросы на пополнение @endsection

@section('body')
    <table class="table table-sm table-bordered">
        <thead>
        <tr>
            <th style="width: 50px;"></th>
            <th class="text-center align-middle">Исполнено</th>
            <th class="align-middle">№ кабинета</th>
            <th class="align-middle">Имя с формы</th>
            <th class="align-middle">Номер телефона</th>
            <th class="align-middle">Комментарий</th>
            <th class="text-center align-middle" style="width: 150px;">Дата создания</th>
        </tr>
        </thead>
        <tbody>
            @php $iteration = $records->perPage() * ($records->currentPage() - 1); @endphp
            @forelse($records as $record)
                @php $presenter = $record->getRefillData(); @endphp
                <tr>
                    <td class="text-center">{{ ++$iteration }}</td>
                    <td class="text-center">
                        <a class="set-paid-status {{ $record->isPaid ? 'text-success' : 'text-danger' }}"
                           href="#"
                           title="Пометить оплаченным"
                           data-id="{{ $record->id }}"
                        >
                            {{ $record->isPaid ? 'Да' : 'Нет' }}
                        </a>
                    </td>
                    <td>{{ $presenter->getCabinetId() }}</td>
                    <td>{{ $record->fullName }}</td>
                    <td>{{ $presenter->getPhone() }}</td>

                    <td>{{ $record->comment }}</td>
                    <td class="text-center">{{ $record->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Нет тарифных планов</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$records"/>
@endsection

@push('js')
    <script src="{{ mix('admin/js/users/requestRefill.min.js') }}"></script>
@endpush
