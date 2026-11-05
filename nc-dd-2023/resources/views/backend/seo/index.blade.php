@extends('backend.layouts.base')

@section('pageTitle') Настройки сайта @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="update-seo" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.seo.update', ['seo' => $seo->id]) }}" method="post" id="update-seo">
        {!! csrf_field() !!}
        {!! method_field('PUT') !!}

        <div class="row">
            <div class="col-12">
                <div class="nav-tabs-boxed">
                    <ul class="nav nav-tabs" role="tablist">
                        @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                            <li class="nav-item">
                                <a class="nav-link {{ $localeCode === config('app.locale') ? 'active' : '' }}" data-toggle="tab" href="#locale-{{ $localeCode }}" role="tab" aria-controls="home">
                                    {{ $properties['name'] }}
                                </a>
                            </li>
                        @endforeach

                    </ul>

                    <div class="tab-content">
                        @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                            <div class="tab-pane {{ $localeCode === config('app.locale') ? 'active' : '' }}" id="locale-{{ $localeCode }}" role="tabpanel">
                                <x-input::text :name="'title['.$localeCode.']'" :class="' translate--title-'.$localeCode.''" :value="$seo->getTranslation('title', $localeCode) ?? null" :label="'Название'" />

                                <x-input::textarea :name="'description['.$localeCode.']'" :value="$seo->getTranslation('description', $localeCode) ?? null" :class="' tinymce'" :label="'Описание'"/>
                                <x-input::textarea :name="'keywords['.$localeCode.']'" :value="$seo->getTranslation('keywords', $localeCode) ?? null" :class="' tinymce'" :label="'Ключевые слова'"/>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection


