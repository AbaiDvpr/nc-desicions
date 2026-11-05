@extends('backend.layouts.base')

@section('pageTitle', 'Добавление показателя на следующий день')

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button
            class="btn card-header-action"
            form="store-indicator"
        ><i class="cil-save"></i></button>
    </div>
@endsection

@section('body')
    <form
        id="store-indicator"
        action="{{ route('backend.indicators.store') }}"
        method="post"
    >
        {{ csrf_field() }}

        <div class="row">
            <div class="col-12">
                <x-input::text :name="'relevant'" :value="$date" :label="'Актуальная дата'" />
            </div>

            <div class="col-12 col-md-3">
                <x-input::text :name="'inflation'" :label="'Цель по инфляции'" />
            </div>

            <div class="col-12 col-md-3">
                <x-input::text :name="'annualInflation'" :label="'Годовая инфляция'" />
            </div>

            <div class="col-12 col-md-3">
                <x-input::text :name="'baseRate'" :label="'Базовая ставка'" />
            </div>

            <div class="col-12 col-md-3">
                <x-input::text :name="'tonia'" :label="'TONIA'" />
            </div>
        </div>
    </form>
@endsection
