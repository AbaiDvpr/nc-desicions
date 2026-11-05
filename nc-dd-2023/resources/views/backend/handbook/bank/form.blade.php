@extends('backend.layouts.base')

@section('pageTitle') Создание банка @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="save-bank" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.handbook.bank.save', ['id' => $bank->getId()]) }}" method="post" id="save-bank">
        {!! csrf_field() !!}

        @php $presenter = $bank->getPresenter(); @endphp

        <div class="row">
            <div class="col-12 col-md-7">
                <x-input::text :name="'name'" :label="'Наименование банка'" :value="$presenter->getName()" />
            </div>

            <div class="col-12 col-md-5">
                <x-input::text :name="'iban'" :label="'Международный номер банковского счета - IBAN'" :value="$presenter->getIban()" />
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                Реквизиты
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <x-input::text :name="'bik'" :label="'SWIFT код или БИК (Банковский Идентификационный Код)'" :value="$presenter->getBik()"/>
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input::text :name="'rnn'" :label="'Регистрационный номер налогоплательщика - РНН'" :value="$presenter->getRnn()" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6">
                        <x-input::text :name="'bin'" :label="'Бизнес-Идентификационный Номер - БИН'" :value="$presenter->getBin()"/>
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input::text :name="'license'" :label="'Лицензия'" :value="$presenter->getLicense()"/>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6">
                        <x-input::text :name="'address'" :label="'Адрес'" :value="$presenter->getAddress()"/>
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input::text :name="'phones'" :label="'Номера телефонов (через запятую)'" :value="$presenter->getPhonesToString()" />
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
