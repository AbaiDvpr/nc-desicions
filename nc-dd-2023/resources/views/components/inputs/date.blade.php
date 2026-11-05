<div class="form-group">
    {!! Form::label($name, $label) !!}
    {!! Form::date($name, $value ?? null, ['class' => 'form-control' . (!$errors->has($name) ? '' : ' is-invalid')]) !!}
    @if (isset($helpBlock))
        <span class="help-block">{!! $helpBlock !!}</span>
    @endif
</div>
