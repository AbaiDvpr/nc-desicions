@extends('backend.layouts.base')

@section('pageTitle') Редактирование страницы @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="update-page" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.modules.pages.update', ['page' => $page->id]) }}" method="post" id="update-page">
        {!! csrf_field() !!}
        {!! method_field('PUT') !!}

        <div class="row">
            <div class="col-12 col-md-1">
                <label class="c-switch c-switch-primary mt-4 pt-2">
                    {{ Form::checkbox('good', 1, $page->good ?? false, ['class' => 'c-switch-input']) }}
                    <span class="c-switch-slider"></span>
                </label>
            </div>

            <div class="col-12 col-md-11">
                <x-input::text :name="'alias'" :class="' section_alias_translate'" :value="$page->alias" :label="'Алиас'" />
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

                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#cover" role="tab" aria-controls="profile">Обложка</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                            <div class="tab-pane {{ $localeCode === config('app.locale') ? 'active' : '' }}" id="locale-{{ $localeCode }}" role="tabpanel">
                                <x-input::text :name="'title['.$localeCode.']'" :class="' translate--title-'.$localeCode.''" :value="$page->getTranslation('title', $localeCode) ?? null" :label="'Название'" />

                                <x-input::textarea :name="'text['.$localeCode.']'" :value="$page->getTranslation('text', $localeCode) ?? null" :class="' tinymce'" :label="null"/>
                            </div>
                        @endforeach

                        <div class="tab-pane" id="cover" role="tabpanel">
                            <div class="row">
                                <div class="col-3">
                                    <div class="card">
                                        @php
                                            $photo = null ;
                                            $media = $page->getFirstMedia(\App\Models\Pages::COVER_COLLECTION_NAME) ?? null;
                                            if (!is_null($media)) {
                                                $photo = $media->getUrl('mediumThumb') ?? null;
                                            }
                                        @endphp
                                        @if(!is_null($photo))
                                            <img alt="Profile picture" class="profile-pic card-img-top" src="{{ $photo }}">
                                        @else
                                            <img alt="Profile picture" class="profile-pic card-img-top" src="/admin/assets/img/avatars/no-photo.svg">
                                        @endif
                                        <div class="card-body bg-light d-flex {{ !is_null($photo) ? 'justify-content-between' : 'justify-content-end' }}">
                                            @if(!is_null($photo))
                                                <a href="{{ route('backend.ajax.delete.file', ['uuid' => $media->uuid]) }}"
                                                   class="text-black-50" data-toggle="modal" data-target="#modal-remove-photo">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            @endif
                                            <button class="btn text-black-50 btn-upload-photo" type="button"><i class="bi bi-upload"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('modals')
    <div class="modal fade" id="modal-remove-photo" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-danger" role="document">
            <div class="modal-content">
                <form action="" method="post">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="modal-header">
                        <h4 class="modal-title">Удаление фото</h4>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы действительно желаете удалить фото профиля?</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Отменить</button>
                        <button class="btn btn-danger" type="submit">Удалить</button>
                    </div>
                </form>
            </div>
            <!-- /.modal-content-->
        </div>
        <!-- /.modal-dialog-->
    </div>
@endpush

@push('before-js')
    @php
        $endpoint = route('backend.ajax.upload.file', [
            'id' => $page->id,
            'model' => \App\Models\Pages::class,
            'collection' => \App\Models\Pages::COVER_COLLECTION_NAME,
            'clearCollect' => true
        ])
    @endphp

    <script type="text/javascript">
        var uploadConfig = {
            endpoint: '{!! $endpoint !!}',
            bind: "img.profile-pic",
            call: "button.btn-upload-photo"
        };
    </script>
@endpush

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
