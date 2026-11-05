@extends('backend.layouts.base')

@section('pageTitle')
    Редактирование показателя <b>{{ date('d.m.Y', strtotime($indicator->relevant)) }}</b>
@endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button
            class="btn card-header-action"
            form="update-indicator"
        ><i class="cil-save"></i></button>
    </div>
@endsection

@section('body')
    <form
        id="update-indicator"
        action="{{ route('backend.indicators.update', ['indicator' => $indicator->id]) }}"
        method="post"
    >
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        <div class="row">
            <div class="col-12 col-lg-6 col-xl-3">
                <x-input::text :name="'inflation'" :value="$indicator->inflation" :label="'Цель по инфляции'" />
            </div>

            <div class="col-12 col-lg-6 col-xl-3">
                <x-input::text :name="'annualInflation'" :value="$indicator->annualInflation" :label="'Годовая инфляция'" />
            </div>

            <div class="col-12 col-lg-6 col-xl-3">
                <x-input::text :name="'baseRate'" :value="$indicator->baseRate" :label="'Базовая ставка'" />
            </div>

            <div class="col-12 col-lg-6 col-xl-3">
                <x-input::text :name="'tonia'" :value="$indicator->tonia" :label="'TONIA'" />
            </div>
        </div>
    </form>
@endsection
