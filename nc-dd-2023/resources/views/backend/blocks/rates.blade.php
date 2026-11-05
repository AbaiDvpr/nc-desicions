@isset($rate)
    <div class="card">
        <div class="card-header">
            <i class="cil-bank mr-2"></i>
            Курсы валют на: {{ formatDate($rate->relevant) }}
        </div>

        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-sm-4">
                    <div class="c-callout c-callout-info">
                        <small class="text-muted">Доллар</small><br>
                        <strong class="h4">{{ $rate->amount_USD }}</strong>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="c-callout c-callout-danger">
                        <small class="text-muted">Евро</small><br>
                        <strong class="h4">{{ $rate->amount_EUR }}</strong>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="c-callout c-callout-warning">
                        <small class="text-muted">Рубль</small><br>
                        <strong class="h4">{{ $rate->amount_RUB }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endisset
