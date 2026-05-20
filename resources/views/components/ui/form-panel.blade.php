@props([
    'action',
    'method' => 'POST',
])

<div class="card form-panel">
    <div class="card-body">
        <form method="POST" action="{{ $action }}" autocomplete="off" novalidate>
            @csrf
            @if (! in_array(strtoupper($method), ['GET', 'POST'], true))
                @method($method)
            @endif

            {{ $slot }}
        </form>
    </div>
</div>
