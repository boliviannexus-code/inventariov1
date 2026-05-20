@extends('layouts.admin')

@section('title', 'Kardex | Inventario POS')
@section('page-title', 'Kardex')
@section('page-subtitle', 'Movimientos historicos de inventario')

@section('content')
    <x-ui.table-card title="Kardex de productos">
        <table
            class="table table-hover align-middle"
            data-datatable
            data-url="{{ route('datatables.kardex') }}"
            data-order='[[0,"desc"]]'
            data-columns-id="kardex-table-columns"
        >
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Almacen</th>
                    <th>Sucursal</th>
                    <th>Producto</th>
                    <th>Presentacion</th>
                    <th class="text-end">Cantidad</th>
                    <th>Usuario</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <script type="application/json" id="kardex-table-columns">
            [
                {"data":"id","name":"inventory_movements.id"},
                {"data":"created_at","name":"inventory_movements.created_at"},
                {"data":"type","name":"inventory_movements.type"},
                {"data":"warehouse_name","name":"warehouses.name","defaultContent":"-"},
                {"data":"branch_name","name":"branches.name","defaultContent":"-"},
                {"data":"product_name","name":"products.name","defaultContent":"-"},
                {"data":"presentation","name":"presentation","orderable":false,"searchable":false},
                {"data":"quantity","name":"inventory_movements.quantity","className":"text-end"},
                {"data":"user_name","name":"users.name","defaultContent":"-"},
                {"data":"notes","name":"inventory_movements.notes","defaultContent":"-"}
            ]
        </script>
    </x-ui.table-card>
@endsection
