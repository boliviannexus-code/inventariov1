@extends('layouts.admin')

@section('title', 'Punto de venta | Inventario POS')
@section('page-title', 'Punto de venta')
@section('page-subtitle', 'Inicio de caja para operar ventas')

@section('content')
    @if ($openRegister)
        <form class="pos-sale-form" method="POST" action="{{ route('pos.sales.store') }}" data-pos-sale-form data-pos-stock='@json($stockAvailability)' autocomplete="off" novalidate>
            @csrf

            <div class="pos-workspace">
                <section class="pos-main">
                    <div class="card mb-3">
                        <div class="card-body py-3">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <div class="text-body-secondary">Punto de venta</div>
                                    <div class="fw-semibold">{{ $openRegister->pointOfSale?->name }}</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-body-secondary">Almacen</div>
                                    <div class="fw-semibold">{{ $openRegister->pointOfSale?->warehouse?->name }}</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-body-secondary">Apertura</div>
                                    <div class="fw-semibold">{{ $openRegister->opened_at?->format('Y-m-d H:i') }}</div>
                                </div>
                                <div class="col-md-2 text-md-end">
                                    <div class="text-body-secondary">Base caja</div>
                                    <div class="fw-semibold">{{ money_format_decimal($openRegister->opening_amount) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Producto</h3>
                            <div class="card-actions">
                                <button class="btn btn-primary btn-sm" type="button" data-add-pos-item>
                                    <i class="ti ti-plus"></i>
                                    Agregar
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 align-items-end" data-pos-picker>
                                <div class="col-lg-5">
                                    <label class="form-label" for="pos-product-picker">Producto</label>
                                    <select class="form-select" id="pos-product-picker" data-tom-select data-pos-product-picker data-placeholder="Buscar producto">
                                        <option value="">Seleccionar</option>
                                        @foreach ($products as $product)
                                            @php $available = $stockAvailability[$product->id]['stock'] ?? 0; @endphp
                                            <option value="{{ $product->id }}" data-price="{{ $product->sale_price }}" data-unit="{{ $product->measurementUnit?->abbreviation ?? 'u' }}" data-stock="{{ $available }}">
                                                {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label class="form-label" for="pos-presentation-picker">Presentacion</label>
                                    <select class="form-select" id="pos-presentation-picker" data-tom-select data-pos-presentation-picker data-placeholder="Presentacion">
                                        <option value="">Selecciona producto</option>
                                    </select>
                                </div>
                                <div class="col-6 col-lg-2">
                                    <label class="form-label" for="pos-quantity-picker">Cantidad</label>
                                    <input class="form-control text-end" id="pos-quantity-picker" type="number" min="1" step="1" value="1" data-pos-quantity-picker>
                                </div>
                                <div class="col-6 col-lg-2">
                                    <button class="btn btn-primary w-100" type="button" data-add-pos-item>
                                        <i class="ti ti-shopping-cart-plus"></i>
                                        Agregar
                                    </button>
                                </div>
                            </div>
                            @error('items')<div class="text-danger small mt-3">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter pos-lines-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Presentacion</th>
                                        <th class="text-end">Cant.</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-end">Desc.</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody data-pos-items></tbody>
                            </table>
                        </div>
                        <div class="card-body py-4 text-center text-body-secondary" data-pos-empty>
                            Agrega productos para iniciar la venta.
                        </div>
                    </div>
                </section>

                <aside class="pos-checkout">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Cobro</h3>
                        </div>
                        <div class="card-body">
                            @if (empty($stockAvailability))
                                <div class="alert alert-warning mb-3">
                                    El almacen vinculado a esta caja no tiene productos disponibles para vender.
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label" for="customer_id">Cliente</label>
                                <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" data-tom-select data-placeholder="Consumidor final">
                                    <option value="">Consumidor final</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                            {{ $customer->name }}{{ $customer->document_number ? ' - '.$customer->document_number : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="pos-total-row">
                                <span>Subtotal</span>
                                <strong data-pos-subtotal>0.00</strong>
                            </div>
                            <div class="pos-total-row">
                                <span>Descuento</span>
                                <strong data-pos-discount>0.00</strong>
                            </div>
                            <div class="pos-grand-total">
                                <span>Total</span>
                                <strong data-pos-total>0.00</strong>
                            </div>

                            <div class="mt-3">
                                <label class="form-label" for="notes">Observaciones</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="card-footer d-grid gap-2">
                            <button class="btn btn-success btn-lg" type="submit" data-pos-submit disabled>
                                <i class="ti ti-cash"></i>
                                Registrar venta
                            </button>
                        </div>
                    </div>
                </aside>
            </div>

            <template data-pos-line-template>
                <tr data-pos-row>
                    <td>
                        <input type="hidden" data-pos-product-input>
                        <div class="fw-semibold" data-pos-product-name></div>
                    </td>
                    <td>
                        <input type="hidden" data-pos-presentation-input>
                        <span class="fw-semibold" data-pos-presentation-name></span>
                        <div class="text-body-secondary small" data-pos-calculation></div>
                    </td>
                    <td class="pos-number-cell"><input class="form-control form-control-sm text-end" type="number" min="1" step="1" data-pos-line-quantity></td>
                    <td class="pos-money-cell"><input class="form-control form-control-sm text-end" type="number" min="0" step="0.01" data-pos-line-price></td>
                    <td class="pos-money-cell"><input class="form-control form-control-sm text-end" type="number" min="0" step="0.01" value="0" data-pos-line-discount></td>
                    <td class="text-end fw-semibold pos-subtotal-cell" data-pos-line-subtotal>0.00</td>
                    <td class="text-end">
                        <button class="btn btn-outline-danger btn-icon" type="button" data-pos-remove title="Quitar">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
            </template>
        </form>
    @else
        <div class="card form-panel">
            <div class="card-header">
                <h3 class="card-title">Abrir caja</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('pos.open') }}" autocomplete="off" novalidate>
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="point_of_sale_id">Punto de venta</label>
                            <select class="form-select @error('point_of_sale_id') is-invalid @enderror" id="point_of_sale_id" name="point_of_sale_id" data-tom-select data-placeholder="Seleccionar punto de venta" required>
                                <option value="">Seleccionar</option>
                                @foreach ($pointOfSales as $pointOfSale)
                                    <option value="{{ $pointOfSale->id }}" @selected(old('point_of_sale_id') == $pointOfSale->id)>
                                        {{ $pointOfSale->code }} - {{ $pointOfSale->name }} / {{ $pointOfSale->warehouse?->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('point_of_sale_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                            @if ($pointOfSales->isEmpty())
                                <div class="text-body-secondary small mt-2">No tienes puntos de venta activos asignados.</div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="opening_amount">Monto inicial</label>
                            <input class="form-control text-end @error('opening_amount') is-invalid @enderror" id="opening_amount" name="opening_amount" type="number" min="0" step="0.01" value="{{ old('opening_amount', '0.00') }}" required>
                            @error('opening_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button class="btn btn-primary" type="submit" @disabled($pointOfSales->isEmpty())>Abrir caja</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
