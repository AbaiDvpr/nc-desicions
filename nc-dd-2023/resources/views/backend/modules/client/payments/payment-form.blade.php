@extends('backend.layouts.base')

@section('pageTitle') <b>{{ $client->name }}</b> - {{ $pageTitle }} @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button class="card-header-action btn" type="submit" form="payment-form"
                data-toggle="collapse" data-target="#collapseExample"
                aria-expanded="false" aria-controls="collapseExample">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ $route }}"
          id="payment-form" method="post">
        {{ csrf_field() }}

        <x-input::text :name="'balance'" :label="'Сумма транзакции'" />

        <x-input::text :name="'comment'" :label="'Комментарий'" />
    </form>
@endsection
