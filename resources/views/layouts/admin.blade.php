<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inventario POS')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
<div class="app-shell d-flex">
    <x-admin.sidebar />

    <div class="wrapper d-flex flex-column flex-grow-1 app-wrapper">
        <x-admin.header />

        <main class="body flex-grow-1 app-content">
            <div class="container-lg px-3 px-lg-4 py-4">
                <x-admin.flash />

                <div wire:loading.class="opacity-75">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="ajaxModal" tabindex="-1" aria-labelledby="ajaxModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="ajaxModalTitle">Detalle</h2>
                <button class="btn-close" type="button" data-coreui-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" data-modal-body></div>
        </div>
    </div>
</div>

@stack('scripts')
</body>
</html>
