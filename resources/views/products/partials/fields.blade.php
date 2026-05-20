<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="modal-product-name">Nombre</label>
        <input class="form-control" id="modal-product-name" name="name" value="{{ old('name', $product->name ?? '') }}" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="modal-product-barcode">Codigo de barras</label>
        <input class="form-control" id="modal-product-barcode" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}">
        <div class="invalid-feedback" data-error-for="barcode"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="modal-product-category">Categoria</label>
        <select class="form-select" id="modal-product-category" name="category_id" required>
            <option value="">Seleccionar</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id ?? 0) === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="category_id"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="modal-product-purchase-price">Precio compra</label>
        <input class="form-control" id="modal-product-purchase-price" name="purchase_price" type="number" step="0.01" min="0" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" required>
        <div class="invalid-feedback" data-error-for="purchase_price"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="modal-product-sale-price">Precio venta</label>
        <input class="form-control" id="modal-product-sale-price" name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price ?? 0) }}" required>
        <div class="invalid-feedback" data-error-for="sale_price"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="modal-product-minimum-stock">Stock minimo</label>
        <input class="form-control" id="modal-product-minimum-stock" name="minimum_stock" type="number" min="0" value="{{ old('minimum_stock', $product->minimum_stock ?? 0) }}" required>
        <div class="invalid-feedback" data-error-for="minimum_stock"></div>
    </div>

    <div class="col-md-9">
        <label class="form-label" for="modal-product-description">Descripcion</label>
        <textarea class="form-control" id="modal-product-description" name="description" rows="3">{{ old('description', $product->description ?? '') }}</textarea>
        <div class="invalid-feedback" data-error-for="description"></div>
    </div>

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="modal-product-is-active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->is_active ?? true))>
            <label class="form-check-label" for="modal-product-is-active">Activo</label>
            <div class="invalid-feedback d-block" data-error-for="is_active"></div>
        </div>
    </div>
</div>
