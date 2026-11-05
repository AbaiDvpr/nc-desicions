@include('backend.blocks._header')
@include('backend.blocks.sidebar')

<div class="c-wrapper c-fixed-components">
    @include('backend.blocks.header')

    <main class="c-main">
        <div class="container-fluid">
            <div class="fade-in">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="cil-home mr-2"></i>
                                Главная
                            </div>

                            <div class="card-body">
                                Добро пожаловать в Админ панель!
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        @include('backend.blocks.rates')
                    </div>
                    <div class="col-lg-6">
                        @include('backend.blocks.users')
                    </div>
                    <div class="col-lg-6">
                        @include('backend.blocks.projects')
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('backend.blocks.footer')
</div>

@include('backend.blocks._footer')
