@extends('backend.layouts.base')

@section('pageTitle') История операций по балансу @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <a href="{{ route('backend.modules.client.payment.downPaymentForm', ['user' => $client->id]) }}"
           class="card-header-action btn">
            списать
        </a>

        <a href="{{ route('backend.modules.client.payment.upPaymentForm', ['user' => $client->id]) }}"
           class="card-header-action btn">
            пополнить
        </a>
    </div>
@endsection

@section('body')
    <table class="table table-sm table-bordered">
        <thead>
        <tr>
            <th class="text-center"></th>
            <th class="text-center">Тариф</th>
            <th class="text-center">Операция</th>
            <th class="text-center">Сумма</th>
            <th class="text-center">Остаток</th>
            <th class="text-center">Исполнитель</th>
            <th class="text-center" width="150">Дата транзакции</th>
        </tr>
        </thead>
        <tbody>
        @forelse($payments as $payment)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>
                    @if($payment->tariff)
                        {{ \Illuminate\Support\Str::limit($payment->tariff->name, 100) }}
                    @endif
                </td>
                <td class="text-center">{{ $payment->getOperationName() }}</td>
                <td class="text-right">{{ number_format($payment->paid, 2, '.', ' ') }}</td>
                <td class="text-right">{{ number_format($payment->residue, 2, '.', ' ') }}</td>
                <td class="text-center">{{ $payment->createdUser->name ?? null }}</td>
                <td class="text-center">{{ $payment->paid_at }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">Проведенных транзакция пока еще не было</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <x-paginate :paginate="$payments"/>
@endsection
