@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
])

@php
$sizeClass = [
    'sm' => 'modal-sm',
    'md' => '',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
    '2xl' => 'modal-xl',
][$maxWidth] ?? '';
@endphp

{{--
    Trigger from anywhere with `data-bs-toggle="modal" data-bs-target="#modal-{{ $name }}"`,
    or programmatically with `bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-{{ $name }}')).show()`.
--}}
<div class="modal modal-blur fade" id="modal-{{ $name }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $sizeClass }}" role="document">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>
</div>

@if($show)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-{{ $name }}')).show();
        });
    </script>
@endif
