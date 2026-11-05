@extends('backend.layouts.base')

@section('pageTitle') Редактирование курсов за <b>{{ $rate->relevant }}</b> @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="update-rate" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.rates.update', ['rate' => $rate->id]) }}" method="post" id="update-rate">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        <div class="row">
            <div class="col-12 col-md-4">
                <x-input::text :name="'amount_USD'" :value="$rate->amount_USD" :label="'Доллар'" />
            </div>

            <div class="col-12 col-md-4">
                <x-input::text :name="'amount_EUR'" :value="$rate->amount_EUR" :label="'Евро'" />
            </div>

            <div class="col-12 col-md-4">
                <x-input::text :name="'amount_RUB'" :value="$rate->amount_RUB" :label="'Рубль'" />
            </div>
        </div>
    </form>
@endsection
