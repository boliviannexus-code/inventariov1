@extends('layouts.admin')

@section('title', 'Dashboard | Inventario POS')
@section('page-title', 'Dashboard')
@section('page-subtitle', $dashboardCompany ? 'Resumen de '.$dashboardCompany->name : 'Resumen global de todas las empresas')

@section('content')
    <div class="row g-3 mb-1">
        <div class="col-12">
            <x-ui.card>
                <div class="card-body d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div>
                        <div class="text-body-secondary small">Empresa activa</div>
                        <div class="h3 mb-0">{{ $dashboardCompany?->name ?? 'Todas las empresas' }}</div>
                    </div>
                    <span class="badge text-bg-{{ $dashboardCompany ? 'primary' : 'purple' }}">
                        {{ $dashboardCompany ? 'Contexto de empresa' : 'Contexto global' }}
                    </span>
                </div>
            </x-ui.card>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <x-ui.stat-card label="Productos" :value="$totalProducts" icon="ti ti-package" tone="primary" />
        </div>
        <div class="col-md-3">
            <x-ui.stat-card label="Productos activos" :value="$activeProducts" icon="ti ti-circle-check" tone="success" />
        </div>
        <div class="col-md-3">
            <x-ui.stat-card label="Categorias" :value="$totalCategories" icon="ti ti-category" tone="info" />
        </div>
        <div class="col-md-3">
            <x-ui.stat-card label="Categorias activas" :value="$activeCategories" icon="ti ti-checks" tone="warning" />
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
