@extends('backend.layouts.base')

@section('pageTitle') Редактирование Новости @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="update-news" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.modules.news.update', ['news' => $record->id]) }}" method="post" id="update-news">
        {!! csrf_field() !!}
        {!! method_field('PUT') !!}

        <div class="row">
            <div class="col-12 col-md-1">
                <label class="c-switch c-switch-primary mt-4 pt-2">
                    {{ Form::checkbox('good', 1, $record->good ?? false, ['class' => 'c-switch-input']) }}
                    <span class="c-switch-slider"></span>
                </label>
            </div>

            <div class="col-12 col-md-11">
                <div class="form-group">
                    <label for="category-id">Категория</label>
                    <select name="category_id" id="category-id" class="form-control">
                        <option value="0">Выберите категорию новости</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $record->category_id === $category->id ? 'selected' : '' }}>
                                {{ $category->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

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

                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#cover" role="tab" aria-controls="profile">Обложка</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                            <div class="tab-pane {{ $localeCode === config('app.locale') ? 'active' : '' }}" id="locale-{{ $localeCode }}" role="tabpanel">
                                <x-input::text :name="'title['.$localeCode.']'" :value="$record->getTranslation('title', $localeCode) ?? null" :label="'Название'" />

                                <x-input::textarea :name="'short['.$localeCode.']'" :value="$record->getTranslation('short', $localeCode) ?? null" :class="' tinymce'" :label="'Короткая запись'"/>

                                <x-input::textarea :name="'full['.$localeCode.']'" :value="$record->getTranslation('full', $localeCode) ?? null" :class="' tinymce'" :label="'Полная запись'"/>
                            </div>
                        @endforeach

                        <div class="tab-pane" id="cover" role="tabpanel">
                            <div class="row">
                                <div class="col-3">
                                    <div class="card">
                                        @php
                                            $photo = null ;
                                            $media = $record->getFirstMedia(\App\Models\News::COVER_COLLECTION_NAME) ?? null;
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
            'id' => $record->id,
            'model' => \App\Models\News::class,
            'collection' => \App\Models\News::COVER_COLLECTION_NAME,
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
