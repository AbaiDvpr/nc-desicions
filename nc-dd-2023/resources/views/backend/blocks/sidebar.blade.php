<div class="c-sidebar c-sidebar-dark c-sidebar-fixed c-sidebar-lg-show" id="sidebar">
    <div class="c-sidebar-brand d-lg-down-none">
        <img class="c-sidebar-brand-full" src="/static/images/common/header/logo.svg" alt="NC decision" width="118" height="46">

        <img class="c-sidebar-brand-minimized" src="/static/images/common/header/logo.svg" alt="NC decision" width="46" height="46">
    </div>

    <ul class="c-sidebar-nav">
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="{{ route('backend.index') }}">
                <i class="c-sidebar-nav-icon cil-home"></i> Главная
            </a>
        </li>

        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="{{ route('backend.modules.client.index') }}">
                <i class="c-sidebar-nav-icon cil-user"></i> Пользователи
            </a>
        </li>

        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="{{ route('backend.projects.project.index') }}">
                <i class="c-sidebar-nav-icon cil-casino"></i> Проекты
            </a>
        </li>

        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="{{ route('backend.companies.company.index') }}">
                <i class="c-sidebar-nav-icon cil-casino"></i> Управленческий учет
            </a>
        </li>

        <li class="c-sidebar-nav-title">Финансы</li>
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="{{ route('backend.modules.tariff.index') }}">
                <i class="c-sidebar-nav-icon cil-dollar"></i> Тарифы
            </a>
        </li>
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="{{ route('backend.request-refill.index') }}">
                <i class="c-sidebar-nav-icon cil-cash"></i>
                Запросы на пополнение
            </a>
        </li>

        <li class="c-sidebar-nav-title">Контент</li>
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="{{ route('backend.modules.news.index') }}">
                <i class="c-sidebar-nav-icon cil-newspaper"></i> Новости
            </a>
        </li>
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="{{ route('backend.modules.pages.index') }}">
                <i class="c-sidebar-nav-icon cil-file"></i> Страницы
            </a>
        </li>
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="{{ route('backend.modules.faq.index') }}">
                <i class="c-sidebar-nav-icon cil-comment-bubble"></i> FAQ
            </a>
        </li>
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="{{ route('backend.modules.feedbacks.index') }}">
                <i class="c-sidebar-nav-icon cil-comment-bubble"></i> Обратная связь
            </a>
        </li>

        {{--<li class="c-sidebar-nav-title">Components</li>
        <li class="c-sidebar-nav-item c-sidebar-nav-dropdown">
            <a class="c-sidebar-nav-link c-sidebar-nav-dropdown-toggle" href="#">
                <svg class="c-sidebar-nav-icon">
                    <use xlink:href="/admin/assets/icons/sprites/free.svg#cil-puzzle"></use>
                </svg> Base
            </a>
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="base/breadcrumb.html"><span class="c-sidebar-nav-icon"></span> Breadcrumb</a></li>
                <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="base/cards.html"><span class="c-sidebar-nav-icon"></span> Cards</a></li>
            </ul>
        </li>--}}
    </ul>
    <button class="c-sidebar-minimizer c-class-toggler" type="button" data-target="_parent" data-class="c-sidebar-minimized"></button>
</div>
