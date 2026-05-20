@props([
    'label',
    'value',
    'icon' => 'cil-speedometer',
    'tone' => 'primary',
])

<div class="card border-0 shadow-sm h-100">
    <div class="card-body d-flex align-items-center gap-3">
        <div class="bg-{{ $tone }} bg-opacity-10 text-{{ $tone }} rounded-3 d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
            <i class="{{ $icon }} fs-4"></i>
        </div>
        <div>
            <div class="text-body-secondary small">{{ $label }}</div>
            <div class="fs-3 fw-semibold lh-1">{{ $value }}</div>
        </div>
    </div>
</div>
