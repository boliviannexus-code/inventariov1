@extends('layouts.admin')

@section('title', 'Dashboard | Inventario POS')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Resumen inicial de catalogo y operacion')

@section('content')
    <div class="row g-3">
        <div class="col-md-3">
            <x-ui.stat-card label="Productos" :value="$totalProducts" icon="cil-tags" tone="primary" />
        </div>
        <div class="col-md-3">
            <x-ui.stat-card label="Productos activos" :value="$activeProducts" icon="cil-check-circle" tone="success" />
        </div>
        <div class="col-md-3">
            <x-ui.stat-card label="Categorias" :value="$totalCategories" icon="cil-folder" tone="info" />
        </div>
        <div class="col-md-3">
            <x-ui.stat-card label="Categorias activas" :value="$activeCategories" icon="cil-check-alt" tone="warning" />
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <x-ui.card title="Base operativa">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-body-secondary small">Compras</div>
                            <div class="fw-semibold">Preparado</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-body-secondary small">Ventas POS</div>
                            <div class="fw-semibold">Preparado</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-body-secondary small">Inventario</div>
                            <div class="fw-semibold">Preparado</div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
        <div class="col-lg-4">
            <x-ui.card title="Siguiente hito">
                <div class="card-body">
                    <p class="mb-0 text-body-secondary">
                        Implementar stock por almacen, Kardex y movimientos transaccionales.
                    </p>
                </div>
            </x-ui.card>
        </div>
    </div>
@endsection
