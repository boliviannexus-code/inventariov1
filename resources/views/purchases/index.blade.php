@extends('layouts.admin')

@section('title', 'Compras | Inventario POS')
@section('page-title', 'Compras')
@section('page-subtitle', 'Listado administrativo de compras')

@section('content')
    <x-ui.table-card title="Listado de compras">
        <x-slot:actions>
            @can('purchases.create')
                <a class="btn btn-primary btn-sm" href="{{ route('purchases.create') }}">
                    Nueva compra
                </a>
            @endcan
        </x-slot:actions>

        <table
            class="table table-hover align-middle"
            data-datatable
            data-url="{{ route('datatables.purchases') }}"
            data-order='[[0,"desc"]]'
            data-columns-id="purchases-table-columns"
        >
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Referencia</th>
                    <th>Proveedor</th>
                    <th>Almacen</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <script type="application/json" id="purchases-table-columns">
            [
                {"data":"id","name":"purchases.id"},
                {"data":"purchase_date","name":"purchases.purchase_date"},
                {"data":"reference","name":"purchases.reference","defaultContent":"-"},
                {"data":"supplier_name","name":"suppliers.name","defaultContent":"-"},
                {"data":"warehouse_name","name":"warehouses.name"},
                {"data":"user_name","name":"users.name","defaultContent":"-"},
                {"data":"status","name":"purchases.status"},
                {"data":"total","name":"purchases.total","className":"text-end"},
                {"data":"actions","name":"actions","orderable":false,"searchable":false,"className":"text-end"}
            ]
        </script>
    </x-ui.table-card>
@endsection
