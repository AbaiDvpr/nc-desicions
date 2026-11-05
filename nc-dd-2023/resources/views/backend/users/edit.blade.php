@extends('backend.layouts.base')

@section('pageTitle') Редактирование пользователя @endsection

@section('cardHeaderActions')
    <div class="card-header-actions">
        <button form="update-user" class="btn card-header-action">
            <i class="cil-save"></i>
        </button>
    </div>
@endsection

@section('body')
    <form action="{{ route('backend.users.update', ['user' => $user->id]) }}" method="post" id="update-user">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        <div class="row">
            <div class="col-12 col-sm-7 col-md-9">
                <div class="row">
                    <div class="col-12 col-md-9">
                        <x-input::text :name="'name'" :value="$user->name" :label="'ФИО'" />
                    </div>

                    <div class="col-12 col-md-3">
                        <x-input::select :name="'admin'" :value="$user->admin" :data="[1 => 'Да', 0 => 'Нет']" :label="'Админ'" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6">
                        <x-input::text :name="'email'" :value="$user->email" :label="'E-mail'" />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-input::select :name="'roles'" :value="$user->roles->pluck('name')->first()" :data="$roles" :label="'Выберите роль'" />
                    </div>

                    <div class="col-12">
                        <x-input::text :name="'mobile'" :value="$user->mobile ?? null" :class="' mobile-number'" :label="'Мобильный телефон'" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <h4 class="border-bottom pb-2">Безопасность</h4>

                        <x-input::password :name="'password'" :label="'Пароль'" />

                        <x-input::password :name="'password_confirmation'" :label="'Повторите пароль'" />
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-5 col-md-3">
                <div class="card">
                    @php
                        $photo = null ;
                        $media = $user->getFirstMedia(\App\Models\User::PROFILE_COLLECTION_NAME) ?? null;
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
            'id' => $user->id,
            'model' => \App\Models\User::class,
            'collection' => \App\Models\User::PROFILE_COLLECTION_NAME,
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
    <script src="{{ mix('admin/js/users/users.min.js') }}"></script>
@endpush
