<header class="c-header c-header-light c-header-fixed c-header-with-subheader">
    <button class="c-header-toggler c-class-toggler d-lg-none mfe-auto" type="button" data-target="#sidebar"
            data-class="c-sidebar-show">
        <svg class="c-icon c-icon-lg">
            <use xlink:href="/admin/assets/icons/sprites/free.svg#cil-menu"></use>
        </svg>
    </button>
    <a class="c-header-brand d-lg-none" href="{{ route('frontend.index') }}">
        <img src="/static/images/common/header/logo.svg" alt="NC decision" width="118" height="46">
    </a>
    <button class="c-header-toggler c-class-toggler mfs-3 d-md-down-none" type="button" data-target="#sidebar"
            data-class="c-sidebar-lg-show" responsive="true">
        <svg class="c-icon c-icon-lg">
            <use xlink:href="/admin/assets/icons/sprites/free.svg#cil-menu"></use>
        </svg>
    </button>
    <ul class="c-header-nav d-md-down-none">
        <li class="c-header-nav-item px-3">
            <a class="c-header-nav-link" href="{{ route('backend.index') }}">Главная</a>
        </li>

        <li class="c-header-nav-item dropdown px-3">
            <a class="c-header-nav-link" data-toggle="dropdown" href="#">Настройки <i class="cil-chevron-bottom ml-2"></i></a>

            <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ route('backend.seo.index') }}">
                    <i class="cil-settings mr-2"></i> Настройки сайта
                </a>

                <a class="dropdown-item" href="{{ route('backend.users.index') }}">
                    <i class="cil-user mr-2"></i> Пользователи
                </a>

                <a class="dropdown-item" href="{{ route('backend.roles.index') }}">
                    <i class="cil-account-logout mr-2"></i> Роли пользователей
                </a>

                <a class="dropdown-item" href="{{ route('backend.roles.permissions.index') }}">
                    <i class="cil-account-logout mr-2"></i> Правила для ролей
                </a>
            </div>
        </li>

        <li class="c-header-nav-item dropdown px-3">
            <a class="c-header-nav-link" data-toggle="dropdown" href="#">Нацбанк <i class="cil-chevron-bottom ml-2"></i></a>

            <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ route('backend.rates.index') }}">
                    Курсы валют
                </a>
                <a class="dropdown-item" href="{{ route('backend.indicators.index') }}">
                    Показатели
                </a>
            </div>
        </li>

        <li class="c-header-nav-item dropdown px-3">
            <a class="c-header-nav-link" data-toggle="dropdown" href="#">Справочники <i class="cil-chevron-bottom ml-2"></i></a>

            <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ route('backend.handbook.bank.index') }}">
                    <i class="cil-bank mr-2"></i> Банки
                </a>
            </div>
        </li>

        <li class="c-header-nav-item px-3">
            <a class="c-header-nav-link" href="{{ route('backend.modules.categories.index') }}">Категории для новостей</a>
        </li>

        <li class="c-header-nav-item dropdown px-3">
            <a class="c-header-nav-link" data-toggle="dropdown" href="#">
                <i class="cil-book mr-2"></i>
                Обучение
                <i class="cil-chevron-bottom ml-2"></i>
            </a>

            <div class="dropdown-menu">
                <a class="dropdown-item" href="{{ route('backend.courses.modules.index') }}">
                    <i class="cil-view-module mr-2"></i> Список модулей
                </a>

                <a class="dropdown-item" href="{{ route('backend.courses.lessons.index') }}">
                    <i class="cil-book mr-2"></i> Список уроков
                </a>
            </div>
        </li>
    </ul>
    <ul class="c-header-nav ml-auto mr-4">
{{--        <li class="c-header-nav-item d-md-down-none mx-2">--}}
{{--            <a class="c-header-nav-link" href="#">--}}
{{--                <svg class="c-icon">--}}
{{--                    <use xlink:href="/admin/assets/icons/sprites/free.svg#cil-bell"></use>--}}
{{--                </svg>--}}
{{--            </a>--}}
{{--        </li>--}}
{{--        <li class="c-header-nav-item d-md-down-none mx-2">--}}
{{--            <a class="c-header-nav-link" href="#">--}}
{{--                <svg class="c-icon">--}}
{{--                    <use xlink:href="/admin/assets/icons/sprites/free.svg#cil-list-rich"></use>--}}
{{--                </svg>--}}
{{--            </a>--}}
{{--        </li>--}}
        <li class="c-header-nav-item dropdown d-md-down-none mx-2">
            <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="true">
                <svg class="c-icon">
                    <use xlink:href="/admin/assets/icons/sprites/free.svg#cil-bell"></use>
                </svg>
                <span class="badge badge-pill badge-info">{{ $notifications_head->count() }}</span>
            </a>

            <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg py-0" style="min-width: 300px;">
                <div class="dropdown-header bg-light">
                    <strong>У вас {{ $notifications_head->count() }} уведомлений</strong>
                </div>
                @php $notifications_head = $notifications_head->take(5) @endphp
                @forelse($notifications_head as $notification_head)
                    <div class="dropdown-item border-bottom py-2" style="white-space: inherit;">
                        <div class="message w-100">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Уведомление</small>
                                <small class="text-muted">{{ $notification_head->created_at }}</small>
                            </div>

                            <div class="small">
                                <a class="text-{{ $notification_head->data['alert'] }}"
                                   title="Отметить прочитанным"
                                   href="{{ route('backend.notifications.mark-as-read', ['notify_id' => $notification_head->id]) }}">
                                    {{ Str::limit($notification_head->data['message'], 80) }}
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="m-3 text-muted">На данный момент нет уведомлений</div>
                @endforelse

                <div class="dropdown-header bg-light">
                    <a class="text-muted" href="{{ route('backend.notifications.index') }}">Посмотреть все уведомления</a>
                </div>
            </div>
        </li>

        <li class="c-header-nav-item dropdown">
            <a class="c-header-nav-link" data-toggle="dropdown" href="#">
                <div class="c-avatar">
                    <img class="c-avatar-img" src="{{ $profileSmallThumb }}" alt="user@email.com">
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-right pt-0">
                <div class="dropdown-header bg-light py-2"><strong>Account</strong></div>
                <a class="dropdown-item" href="#">
                    <i class="cil-user mr-2"></i> Profile
                </a>

                <div class="dropdown-divider"></div>

                <a class="dropdown-item" href="{{ route('auth.exit') }}">
                    <i class="cil-account-logout mr-2"></i> Выйти
                </a>
            </div>
        </li>
    </ul>

    <div class="c-subheader px-3">
        <!-- Breadcrumb-->
        <ol class="breadcrumb border-0 m-0">
            <li class="breadcrumb-item"><a href="{{ config('app.url') }}" target="_blank">Сайт</a></li>
            <li class="breadcrumb-item"><a href="{{ route('backend.index') }}">Admin</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
            <!-- Breadcrumb Menu-->
        </ol>
    </div>
</header>
