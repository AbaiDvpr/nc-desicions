@extends('backend.layouts.base')

@section('pageTitle') Редактирование категории @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="update-category" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.modules.categories.update', ['category' => $category->id]) }}" method="post" id="update-category">
        {!! csrf_field() !!}
        {!! method_field('PUT') !!}

        <div class="row">
            <div class="col-12 col-md-1">
                <label class="c-switch c-switch-primary mt-4 pt-2">
                    {{ Form::checkbox('good', 1, $category->good ?? false, ['class' => 'c-switch-input']) }}
                    <span class="c-switch-slider"></span>
                </label>
            </div>

            <div class="col-12 col-md-11">
                <x-input::text :name="'slug'" :class="' section_alias_translate'" :value="$category->slug" :label="'Алиас'" />
            </div>

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
                                <x-input::text :name="'title['.$localeCode.']'" :class="' translate--title-'.$localeCode" :value="$category->getTranslation('title', $localeCode) ?? null" :label="'Название 1'" />

                                <x-input::textarea :name="'description['.$localeCode.']'" :value="$category->getTranslation('description', $localeCode) ?? null" :class="' tinymce'" :label="null"/>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script type="text/javascript">
        $(document).ready(function () {
            $('body').on('change', '.translate--title-ru', function () {
                $(".section_alias_translate").val(translite($(this).val()));
            });
        });

        function translite(text) {
            return text.replace(/([а-яё])|([\s_-])|([^a-z\d])/gi,
                function (all, ch, space, words, i) {
                    if (space || words) {
                        return space ? '-' : '';
                    }
                    var code = ch.charCodeAt(0),
                        index = code == 1025 || code == 1105 ? 0 :
                            code > 1071 ? code - 1071 : code - 1039,
                        t = ['yo', 'a', 'b', 'v', 'g', 'd', 'e', 'zh',
                            'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p',
                            'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh',
                            'shch', '', 'y', '', 'e', 'yu', 'ya'
                        ];
                    return t[index];
                });
        };
    </script>
@endpush
