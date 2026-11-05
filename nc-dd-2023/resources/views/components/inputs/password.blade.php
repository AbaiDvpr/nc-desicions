<div class="form-group">
    @if ($label)
        {!! Form::label($name, $label) !!}
    @endif
    {!! Form::password($name, ['class' => 'form-control' . (!$errors->has($name) ? '' : ' is-invalid'), 'placeholder' => $placeholder ?? null]) !!}
</div>
