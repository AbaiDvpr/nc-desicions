<div class="form-group">
    @if ($label)
        {!! Form::label($name, $label) !!}
    @endif
    {!! Form::textarea($name, $value ?? null, ['class' => 'form-control' . ($class ?? '') . (!$errors->has($name) ? '' : ' is-invalid'), 'placeholder' => $placeholder ?? null]) !!}
    @if (isset($helpBlock))
        <span class="help-block">{!! $helpBlock !!}</span>
    @endif
</div>
