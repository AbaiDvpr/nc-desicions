@include('backend.blocks._header')
    @include('backend.blocks.sidebar')

    <div class="c-wrapper c-fixed-components">
        @include('backend.blocks.header')

        <main class="c-main">
            <div class="container-fluid">
                <div class="fade-in">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i> @yield('pageTitle' , 'Название страницы')

                            @yield('cardHeaderActions')
                        </div>
                        <div class="card-body table-responsive">
                            @yield('body')
                        </div>
                    </div>
                </div>
            </div>
        </main>

        @include('backend.blocks.footer')
    </div>

@include('backend.blocks._footer')
