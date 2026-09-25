@php
    $currentStatus = \App\Enums\AttendanceStatus::fromCode($current);
    $options = \App\Enums\AttendanceStatus::selectable();
    if (!in_array($currentStatus, $options, true)) {
        // Starší stav mimo matice ponecháme vybraný, aby sa uložením nestratil.
        $options[] = $currentStatus;
    }
@endphp
@foreach($options as $option)
    <option value="{{ $option->value }}" {{ $option === $currentStatus ? 'selected' : '' }}>{{ $option->label() }}</option>
@endforeach
