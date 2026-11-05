<div class="form-group">
    @if ($label) {!! Form::label($name, $label) !!} @endif
    {!! Form::select($name, $data ?? [], $value ?? null, [
            'class' => 'form-control' . (!$errors->has($name) ? '' : ' is-invalid'),
            'placeholder' => $placeholder ?? null
        ]) !!}
    @if (isset($helpBlock))
        <span class="help-block">{!! $helpBlock !!}</span>
    @endif
</div>
