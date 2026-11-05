<header class="header">
    <div class="container">
        <div class="header__body">
            <a href="{{ route('frontend.index') }}" class="header__logo">
                <img src="/static/images/common/header/logo.svg" alt="NC decision" width="135" height="30">
            </a>
                @include('frontend.blocks.header.header-nav')
                @include('frontend.blocks.header.header-login')

            <button class="burger burger--light">
                <span class="burger__line"></span>
            </button>
        </div>
    </div>
</header>
