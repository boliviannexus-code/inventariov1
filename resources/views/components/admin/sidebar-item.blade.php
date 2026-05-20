@props([
    'route' => null,
    'icon' => null,
    'label',
    'active' => false,
    'disabled' => false,
])

<li class="nav-item">
    <a
        class="nav-link {{ $active ? 'active' : '' }} {{ $disabled ? 'disabled text-white-50' : '' }}"
        href="{{ $route && ! $disabled ? route($route) : '#' }}"
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
    >
        @if ($icon)
            <i class="nav-icon {{ $icon }}"></i>
        @endif
        {{ $label }}
    </a>
</li>
