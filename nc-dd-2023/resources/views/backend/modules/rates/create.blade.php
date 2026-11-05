@extends('backend.layouts.base')

@section('pageTitle') Добавление курсов на следующий день @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="store-rate" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.rates.store') }}" method="post" id="store-rate">
        {{ csrf_field() }}

        <div class="row">
            <div class="col-12">
                <x-input::text :name="'relevant'" :value="$date" :label="'Актуальная дата'" />
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-4">
                <x-input::text :name="'amount_USD'" :label="'Доллар'" />
            </div>

            <div class="col-12 col-md-4">
                <x-input::text :name="'amount_EUR'" :label="'Евро'" />
            </div>

            <div class="col-12 col-md-4">
                <x-input::text :name="'amount_RUB'" :label="'Рубль'" />
            </div>
        </div>
    </form>
@endsection
