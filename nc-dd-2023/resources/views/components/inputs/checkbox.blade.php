<div class="form-check mb-1">
    {!! Form::checkbox($name, $value ?? '1', $checked ?? null,  ['class' => 'form-check-input', 'id' => 'check-box-' . $name]) !!}
    {!! Form::label('check-box-' . $name, $label, ['class' => 'form-check-label']) !!}
</div>
