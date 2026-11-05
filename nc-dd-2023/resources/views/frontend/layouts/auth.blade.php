@include('frontend.blocks.base.header')
    @include('frontend.blocks.preloader')

    <div class="wrapper">
        <main class="main">
            @yield('main')
        </main>
    </div>

    @include('frontend.blocks.sorry')
    @include('frontend.blocks.cookie-alert')
@include('frontend.blocks.base.footer')
