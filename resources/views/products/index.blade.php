@extends('layouts.admin')

@section('title', 'Productos | Inventario POS')
@section('page-title', 'Productos')
@section('page-subtitle', 'Catalogo comercial para inventario y POS')

@section('content')
    <x-ui.table-card title="Listado de productos" data-refresh-container>
        <x-slot:actions>
            @can('products.create')
                <a
                    class="btn btn-primary btn-sm"
                    href="{{ route('products.create') }}"
                    data-modal-url="{{ route('products.create') }}"
                    data-modal-title="Nuevo producto"
                >
                    Nuevo producto
                </a>
            @endcan
        </x-slot:actions>

        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoria</th>
                    <th>Barcode</th>
                    <th>Compra</th>
                    <th>Venta</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category?->name }}</td>
                        <td>{{ $product->barcode ?: '-' }}</td>
                        <td>{{ money_format_decimal($product->purchase_price) }}</td>
                        <td>{{ money_format_decimal($product->sale_price) }}</td>
                        <td><span class="badge text-bg-{{ $product->is_active ? 'success' : 'secondary' }}">{{ $product->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                        <td class="text-end">
                            <a
                                class="btn btn-outline-secondary btn-sm"
                                href="{{ route('products.show', $product) }}"
                                data-modal-url="{{ route('products.show', $product) }}"
                                data-modal-title="Detalle de producto"
                            >
                                Ver
                            </a>
                            @can('products.update')
                                <a
                                    class="btn btn-outline-primary btn-sm"
                                    href="{{ route('products.edit', $product) }}"
                                    data-modal-url="{{ route('products.edit', $product) }}"
                                    data-modal-title="Editar producto"
                                >
                                    Editar
                                </a>
                            @endcan
                            @can('products.delete')
                                <form class="d-inline" method="POST" action="{{ route('products.destroy', $product) }}" data-confirm-delete="Eliminar producto?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="7" message="No hay productos registrados." />
                @endforelse
            </tbody>
        </table>

        <x-slot:footer>
            {{ $products->links() }}
        </x-slot:footer>
    </x-ui.table-card>
@endsection
