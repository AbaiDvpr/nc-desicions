@include('frontend.blocks.base.header')
    @include('frontend.blocks.preloader')

    <div class="wrapper">
        @include('frontend.blocks.admin-mode')
        @include('frontend.blocks.header')

        <main class="main">
            @yield('main')
        </main>

        @include('frontend.blocks.footer')
    </div>

    @include('frontend.blocks.sorry')
    @include('frontend.blocks.cookie-alert')

    @stack('modal')
@include('frontend.blocks.base.footer')
