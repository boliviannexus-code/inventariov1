@extends('layouts.admin')

@section('title', 'Ventas | Inventario POS')
@section('page-title', 'Ventas')
@section('page-subtitle', 'Listado administrativo de ventas')

@section('content')
    <x-ui.table-card title="Listado de ventas">
        <table
            class="table table-hover align-middle"
            data-datatable
            data-url="{{ route('datatables.sales') }}"
            data-order='[[0,"desc"]]'
            data-columns-id="sales-table-columns"
        >
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Comprobante</th>
                    <th>Cliente</th>
                    <th>Sucursal</th>
                    <th>Almacen</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <script type="application/json" id="sales-table-columns">
            [
                {"data":"id","name":"sales.id"},
                {"data":"sale_date","name":"sales.sale_date"},
                {"data":"receipt_number","name":"sales.receipt_number"},
                {"data":"customer_name","name":"customers.name","defaultContent":"-"},
                {"data":"branch_name","name":"branches.name"},
                {"data":"warehouse_name","name":"warehouses.name"},
                {"data":"user_name","name":"users.name","defaultContent":"-"},
                {"data":"status","name":"sales.status"},
                {"data":"total","name":"sales.total","className":"text-end"}
            ]
        </script>
    </x-ui.table-card>
@endsection
