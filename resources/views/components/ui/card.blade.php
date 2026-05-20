@props([
    'title' => null,
    'actions' => null,
    'footer' => null,
    'class' => '',
])

<div {{ $attributes->merge(['class' => "card border-0 shadow-sm {$class}"]) }}>
    @if ($title || $actions)
        <div class="card-header bg-white d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
            @if ($title)
                <span class="fw-semibold">{{ $title }}</span>
            @endif

            @if ($actions)
                <div>{{ $actions }}</div>
            @endif
        </div>
    @endif

    {{ $slot }}

    @if ($footer)
        <div class="card-footer bg-white">
            {{ $footer }}
        </div>
    @endif
</div>
