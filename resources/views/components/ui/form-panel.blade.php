@props([
    'action',
    'method' => 'POST',
])

<div class="card border-0 shadow-sm form-panel">
    <div class="card-body">
        <form method="POST" action="{{ $action }}" novalidate>
            @csrf
            @if (! in_array(strtoupper($method), ['GET', 'POST'], true))
                @method($method)
            @endif

            {{ $slot }}
        </form>
    </div>
</div>
