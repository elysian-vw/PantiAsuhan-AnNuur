@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => false])
<div class="field">
<label for="{{ $name }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label>
@if($type === 'textarea')
<textarea id="{{ $name }}" name="{{ $name }}" rows="5" {{ $required ? 'required' : '' }} {{ $attributes }}>{{ old($name, $value) }}</textarea>
@else
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" {{ $required ? 'required' : '' }} {{ $attributes }}>
@endif
@error($name)<span class="field-error">{{ $message }}</span>@enderror
</div>
