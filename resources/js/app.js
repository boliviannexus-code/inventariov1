import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import DataTable from 'datatables.net-bs5';
import TomSelect from 'tom-select';
import 'datatables.net-responsive-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css';
import 'sweetalert2/dist/sweetalert2.min.css';
import 'tom-select/dist/css/tom-select.bootstrap5.min.css';

window.Swal = Swal;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const ajaxModalElement = document.getElementById('ajaxModal');
const ajaxModal = ajaxModalElement ? new bootstrap.Modal(ajaxModalElement) : null;
const ajaxModalTitle = document.getElementById('ajaxModalTitle');
const ajaxModalBody = ajaxModalElement?.querySelector('[data-modal-body]');

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2600,
    timerProgressBar: true,
});

function showInitialAlerts() {
    const success = document.querySelector('[data-swal-success]')?.dataset.swalSuccess;
    const error = document.querySelector('[data-swal-error]')?.dataset.swalError;

    if (success) {
        toast.fire({ icon: 'success', title: success });
    }

    if (error) {
        Swal.fire({ icon: 'error', title: 'Atencion', text: error });
    }
}

async function fetchHtml(url) {
    const response = await fetch(url, {
        headers: {
            Accept: 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('No se pudo cargar el contenido solicitado.');
    }

    return response.text();
}

function openAjaxModal(trigger) {
    if (!ajaxModal || !ajaxModalBody || !ajaxModalTitle) {
        window.location.href = trigger.href;

        return;
    }

    ajaxModalTitle.textContent = trigger.dataset.modalTitle ?? 'Detalle';
    ajaxModalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    ajaxModal.show();

    fetchHtml(trigger.dataset.modalUrl ?? trigger.href)
        .then((html) => {
            ajaxModalBody.innerHTML = html;
            disableBusinessFormAutocomplete(ajaxModalBody);
            initTomSelects(ajaxModalBody);
            syncPointSaleWarehouse(ajaxModalBody);
            initDefragmentForms(ajaxModalBody);
        })
        .catch((error) => {
            ajaxModal.hide();
            Swal.fire({ icon: 'error', title: 'Error', text: error.message });
        });
}

function disableBusinessFormAutocomplete(scope = document) {
    scope.querySelectorAll('form[data-ajax-form], .form-panel form').forEach((form) => {
        form.setAttribute('autocomplete', 'off');
    });

    scope.querySelectorAll('form[data-ajax-form] input, form[data-ajax-form] textarea, .form-panel input, .form-panel textarea').forEach((field) => {
        if (['hidden', 'checkbox', 'radio', 'submit', 'button'].includes(field.type)) {
            return;
        }

        const shouldUsePasswordToken = field.name === 'name' || field.id.endsWith('-name');
        field.setAttribute('autocomplete', shouldUsePasswordToken ? 'new-password' : 'off');
        field.setAttribute('data-lpignore', 'true');
        field.setAttribute('data-1p-ignore', 'true');
    });
}

function clearFormErrors(form) {
    form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
    form.querySelectorAll('[data-error-for]').forEach((target) => {
        target.textContent = '';
    });
}

function showFormErrors(form, errors) {
    Object.entries(errors).forEach(([field, messages]) => {
        const input = form.querySelector(`[name="${field}"]`);
        const feedback = form.querySelector(`[data-error-for="${field}"]`);

        input?.classList.add('is-invalid');

        if (feedback) {
            feedback.textContent = messages[0] ?? 'Dato invalido.';
        }
    });
}

function setSubmitting(form, submitting) {
    const submit = form.querySelector('[type="submit"]');
    const spinner = form.querySelector('[data-submit-spinner]');

    if (submit) {
        submit.disabled = submitting;
    }

    spinner?.classList.toggle('d-none', !submitting);
}

async function refreshContainer(url) {
    const current = document.querySelector('[data-refresh-container]');

    if (!current) {
        return;
    }

    const html = await fetchHtml(url ?? window.location.href);
    const documentFragment = new DOMParser().parseFromString(html, 'text/html');
    const fresh = documentFragment.querySelector('[data-refresh-container]');

    if (fresh) {
        current.replaceWith(fresh);
        initTomSelects(fresh);
        initAdminDataTables();
    }
}

async function submitAjaxForm(form) {
    clearFormErrors(form);
    setSubmitting(form, true);

    try {
        const response = await fetch(form.action, {
            method: form.method.toUpperCase(),
            body: new FormData(form),
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await response.json();

        if (response.status === 422) {
            showFormErrors(form, payload.errors ?? payload.data ?? {});
            Swal.fire({ icon: 'error', title: 'Validacion', text: payload.message ?? 'Revisa los datos ingresados.' });

            return;
        }

        if (!response.ok || payload.success === false) {
            throw new Error(payload.message ?? 'No se pudo completar la operacion.');
        }

        ajaxModal?.hide();
        await refreshContainer(form.dataset.refreshUrl);
        toast.fire({ icon: 'success', title: payload.message ?? 'Operacion realizada correctamente.' });
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error', text: error.message });
    } finally {
        setSubmitting(form, false);
    }
}

function confirmDelete(form) {
    Swal.fire({
        icon: 'warning',
        title: form.dataset.confirmDelete ?? 'Confirmar eliminacion',
        text: 'Esta accion no se puede deshacer facilmente.',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

function initAdminDataTables() {
    document.querySelectorAll('[data-datatable]').forEach((table) => {
        if (table.dataset.datatableInitialized === '1') {
            return;
        }

        const columnsElement = document.getElementById(table.dataset.columnsId ?? '');
        const columns = JSON.parse(columnsElement?.textContent ?? table.dataset.columns ?? '[]');
        const filtersForm = table.dataset.filtersForm ? document.querySelector(table.dataset.filtersForm) : null;

        const dataTable = new DataTable(table, {
            ajax: {
                url: table.dataset.url,
                data(data) {
                    if (!filtersForm) {
                        return;
                    }

                    new FormData(filtersForm).forEach((value, key) => {
                        data[key] = value;
                    });
                },
            },
            columns,
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: Number(table.dataset.pageLength ?? 10),
            order: JSON.parse(table.dataset.order ?? '[[0,"desc"]]'),
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Sin registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                loadingRecords: 'Cargando...',
                processing: 'Procesando...',
                zeroRecords: 'No se encontraron registros',
                emptyTable: 'No hay datos disponibles',
                paginate: {
                    first: 'Primero',
                    previous: 'Anterior',
                    next: 'Siguiente',
                    last: 'Ultimo',
                },
            },
        });

        if (filtersForm) {
            let reloadTimeout;
            const reloadTable = () => {
                window.clearTimeout(reloadTimeout);
                reloadTimeout = window.setTimeout(() => dataTable.ajax.reload(), 180);
            };

            filtersForm.addEventListener('change', reloadTable);
            filtersForm.addEventListener('reset', () => {
                window.setTimeout(() => {
                    filtersForm.querySelectorAll('select[data-tom-select]').forEach((select) => {
                        select.tomselect?.clear(true);
                    });
                    dataTable.ajax.reload();
                }, 0);
            });
        }

        dataTable.on('xhr.dt', (_event, _settings, json, xhr) => {
            if (xhr.status === 401 || xhr.status === 403 || xhr.responseURL?.includes('/login')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin acceso',
                    text: 'No tienes permisos para cargar los datos de esta tabla o tu sesion expiro.',
                });
            }
        });
        table.dataset.datatableInitialized = '1';
    });
}

function initTomSelects(scope = document) {
    scope.querySelectorAll('select[data-tom-select]').forEach((select) => {
        if (select.tomselect) {
            return;
        }

        new TomSelect(select, {
            allowEmptyOption: true,
            create: false,
            dropdownParent: 'body',
            maxItems: select.multiple ? null : 1,
            placeholder: select.dataset.placeholder ?? 'Seleccionar',
            plugins: ['clear_button'],
            render: {
                no_results() {
                    return '<div class="no-results">Sin resultados</div>';
                },
            },
        });
    });
}

function selectedOption(select) {
    return select?.selectedOptions?.[0] ?? null;
}

function rowNumberValue(row, selector) {
    return Number(row.querySelector(selector)?.value || 0);
}

function updatePurchaseRow(row) {
    const product = row.querySelector('[data-purchase-product]');
    const presentation = row.querySelector('[data-purchase-presentation]');
    const unitPrice = row.querySelector('[data-unit-price]');
    const quantity = Math.max(0, rowNumberValue(row, '[data-package-quantity]'));
    let price = Math.max(0, rowNumberValue(row, '[data-unit-price]'));
    const productOption = selectedOption(product);
    const presentationOption = selectedOption(presentation);
    const unitsPerPackage = Number(presentationOption?.dataset.units || 0);
    const unitLabel = productOption?.dataset.unit || 'u.';
    const totalUnits = quantity * unitsPerPackage;
    const basePrice = Number(productOption?.dataset.price || 0);
    const shouldAutoPrice = unitPrice && (unitPrice.dataset.autoPrice === '1' || !unitPrice.value);

    if (unitPrice && shouldAutoPrice && basePrice >= 0 && unitsPerPackage > 0) {
        unitPrice.value = (basePrice * unitsPerPackage).toFixed(2);
        unitPrice.dataset.autoPrice = '1';
        price = Math.max(0, rowNumberValue(row, '[data-unit-price]'));
    }

    row.querySelector('[data-unit-calculation]').textContent = unitsPerPackage > 0
        ? `${quantity} x ${unitsPerPackage} = ${totalUnits} ${unitLabel}`
        : `0 ${unitLabel}`;
    row.querySelector('[data-line-subtotal]').textContent = (quantity * price).toFixed(2);
}

function updatePurchaseTotals(form) {
    let subtotal = 0;

    form.querySelectorAll('[data-purchase-item-row]').forEach((row) => {
        updatePurchaseRow(row);
        subtotal += Math.max(0, rowNumberValue(row, '[data-package-quantity]')) * Math.max(0, rowNumberValue(row, '[data-unit-price]'));
    });

    form.querySelector('[data-purchase-subtotal]').textContent = subtotal.toFixed(2);
    form.querySelector('[data-purchase-total]').textContent = subtotal.toFixed(2);
}

function refreshPurchaseReference(form) {
    const warehouse = form.querySelector('[name="warehouse_id"]');
    const preview = form.querySelector('[data-reference-preview]');
    const previews = JSON.parse(form.dataset.referencePreviews || '{}');

    if (!preview) {
        return;
    }

    preview.value = previews[warehouse?.value] || 'Se generara al seleccionar almacen';
}

function clearPurchaseRow(row) {
    row.querySelectorAll('select').forEach((select) => select.tomselect?.clear());
    row.querySelectorAll('input').forEach((input) => {
        input.value = input.matches('[data-package-quantity]') ? '1' : '';
    });
}

function initPurchaseForm() {
    document.querySelectorAll('[data-purchase-form]').forEach((form) => {
        if (form.dataset.purchaseInitialized === '1') {
            return;
        }

        const items = form.querySelector('[data-purchase-items]');
        const template = form.querySelector('[data-purchase-item-template]') ?? document.querySelector('[data-purchase-item-template]');
        form.dataset.purchaseItemIndex = String(items?.querySelectorAll('[data-purchase-item-row]').length || 0);

        form.addEventListener('change', (event) => {
            if (event.target.closest('[name="warehouse_id"]')) {
                refreshPurchaseReference(form);
            }

            if (event.target.closest('[data-purchase-product], [data-purchase-presentation]')) {
                const row = event.target.closest('[data-purchase-item-row]');
                const unitPrice = row?.querySelector('[data-unit-price]');

                if (unitPrice) {
                    unitPrice.dataset.autoPrice = '1';
                }
            }

            if (event.target.closest('[data-purchase-product], [data-purchase-presentation], [data-package-quantity], [data-unit-price]')) {
                updatePurchaseTotals(form);
            }
        });

        form.addEventListener('input', (event) => {
            const unitPrice = event.target.closest('[data-unit-price]');

            if (unitPrice) {
                unitPrice.dataset.autoPrice = '0';
            }

            if (event.target.closest('[data-package-quantity], [data-unit-price]')) {
                updatePurchaseTotals(form);
            }
        });

        form.querySelector('[data-add-purchase-item]')?.addEventListener('click', () => {
            if (!items || !template) {
                return;
            }

            const index = Number(form.dataset.purchaseItemIndex || 0);
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index)).trim();
            const row = wrapper.firstElementChild;

            items.append(row);
            form.dataset.purchaseItemIndex = String(index + 1);
            initTomSelects(row);
            updatePurchaseTotals(form);
        });

        form.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove-purchase-item]');

            if (!remove) {
                return;
            }

            const row = remove.closest('[data-purchase-item-row]');
            const rows = items?.querySelectorAll('[data-purchase-item-row]') ?? [];

            if (rows.length <= 1) {
                clearPurchaseRow(row);
            } else {
                row.remove();
            }

            updatePurchaseTotals(form);
        });

        refreshPurchaseReference(form);
        updatePurchaseTotals(form);
        form.dataset.purchaseInitialized = '1';
    });
}

function syncPointSaleWarehouse(scope = document) {
    scope.querySelectorAll('[data-point-sale-branch]').forEach((branchSelect) => {
        const form = branchSelect.closest('form') ?? branchSelect.closest('.card') ?? document;
        const warehouseSelect = form.querySelector('[data-point-sale-warehouse]');

        if (!warehouseSelect) {
            return;
        }

        const branchId = branchSelect.value;
        let selectedStillVisible = true;

        warehouseSelect.querySelectorAll('option[data-branch-id]').forEach((option) => {
            const visible = !branchId || option.dataset.branchId === branchId;
            option.hidden = !visible;
            option.disabled = !visible;

            if (option.selected && !visible) {
                selectedStillVisible = false;
            }
        });

        if (!selectedStillVisible) {
            warehouseSelect.value = '';
        }
    });
}

function initPosSaleForm() {
    document.querySelectorAll('[data-pos-sale-form]').forEach((form) => {
        if (form.dataset.posInitialized === '1') {
            return;
        }

        const productPicker = form.querySelector('[data-pos-product-picker]');
        const presentationPicker = form.querySelector('[data-pos-presentation-picker]');
        const quantityPicker = form.querySelector('[data-pos-quantity-picker]');
        const items = form.querySelector('[data-pos-items]');
        const template = form.querySelector('[data-pos-line-template]');
        const empty = form.querySelector('[data-pos-empty]');
        const submit = form.querySelector('[data-pos-submit]');
        const stockAvailability = JSON.parse(form.dataset.posStock || '{}');
        const customers = JSON.parse(form.dataset.posCustomers || '[]');
        const payments = form.querySelector('[data-pos-payments]');
        const paymentTemplate = form.querySelector('[data-pos-payment-template]');
        const paymentMode = form.querySelector('[data-pos-payment-mode]');
        const cashPanel = form.querySelector('[data-pos-cash-panel]');
        const mixedPanel = form.querySelector('[data-pos-mixed-panel]');
        const cashReceived = form.querySelector('[data-pos-cash-received]');
        const cashChange = form.querySelector('[data-pos-cash-change]');
        const useCash = form.querySelector('[data-pos-use-cash]');
        const useMixed = form.querySelector('[data-pos-use-mixed]');

        const focusTomSelect = (select) => {
            select?.tomselect?.focus();
            select?.tomselect?.open();
        };

        const tomOption = (select) => {
            const value = select?.value;

            return value && select?.tomselect ? select.tomselect.options[value] : null;
        };

        const selectedPackagesInCart = (productId, presentationId) => Array.from(items.querySelectorAll('[data-pos-row]'))
            .filter((row) => row.dataset.productId === String(productId) && row.dataset.presentationId === String(presentationId))
            .reduce((total, row) => total + Math.max(1, Number(row.querySelector('[data-pos-line-quantity]').value || 1)), 0);

        const refreshPresentationOptions = () => {
            const product = selectedOption(productPicker);
            const tom = presentationPicker?.tomselect;
            const availability = stockAvailability[product?.value]?.presentations || [];

            if (!tom) {
                return;
            }

            tom.clear(true);
            tom.clearOptions();

            if (availability.length === 0) {
                tom.addOption({ value: '', text: product?.value ? 'Sin presentaciones con stock' : 'Selecciona producto' });
                tom.refreshOptions(false);
                return;
            }

            availability.forEach((presentation) => {
                tom.addOption({
                    value: String(presentation.id),
                    text: `${presentation.name} - ${presentation.packages} disp. (${presentation.units} u.)`,
                    units: presentation.units_per_package,
                    packages: presentation.packages,
                    unitsAvailable: presentation.units,
                    baseName: presentation.name,
                });
            });
            tom.refreshOptions(false);
        };

        const focusPresentation = () => {
            window.setTimeout(() => focusTomSelect(presentationPicker), 60);
        };

        const focusQuantity = () => {
            window.setTimeout(() => {
                quantityPicker?.focus();
                quantityPicker?.select();
            }, 60);
        };

        const updateNames = () => {
            items.querySelectorAll('[data-pos-row]').forEach((row, index) => {
                row.querySelector('[data-pos-product-input]').name = `items[${index}][product_id]`;
                row.querySelector('[data-pos-presentation-input]').name = `items[${index}][presentation_id]`;
                row.querySelector('[data-pos-line-quantity]').name = `items[${index}][package_quantity]`;
                row.querySelector('[data-pos-line-price]').name = `items[${index}][unit_price]`;
                row.querySelector('[data-pos-line-discount]').name = `items[${index}][discount]`;
            });
        };

        const updatePaymentNames = () => {
            if (paymentMode?.value !== 'mixed') {
                payments?.querySelectorAll('[data-pos-payment-row]').forEach((row) => {
                    row.querySelector('[data-pos-payment-method]').removeAttribute('name');
                    row.querySelector('[data-pos-payment-amount]').removeAttribute('name');
                    row.querySelector('[data-pos-payment-reference]').removeAttribute('name');
                });

                return;
            }

            payments?.querySelectorAll('[data-pos-payment-row]').forEach((row, index) => {
                row.querySelector('[data-pos-payment-method]').name = `payments[${index}][payment_method_id]`;
                row.querySelector('[data-pos-payment-amount]').name = `payments[${index}][amount]`;
                row.querySelector('[data-pos-payment-reference]').name = `payments[${index}][reference]`;
            });
        };

        const updateCashPayment = (total) => {
            const received = Math.max(0, Number(cashReceived?.value || 0));
            const change = Math.max(0, received - total);
            const complete = total > 0 && received >= total;

            if (cashChange) {
                cashChange.textContent = change.toFixed(2);
                cashChange.classList.toggle('text-success', complete);
                cashChange.classList.toggle('text-danger', total > 0 && !complete);
            }

            return complete;
        };

        const updatePayments = (total) => {
            const rows = payments?.querySelectorAll('[data-pos-payment-row]') ?? [];
            const paidTarget = form.querySelector('[data-pos-paid]');
            const dueTarget = form.querySelector('[data-pos-due]');

            if (rows.length === 1) {
                const amount = rows[0].querySelector('[data-pos-payment-amount]');
                if (amount && (amount.dataset.autoAmount === '1' || !amount.value)) {
                    amount.value = total > 0 ? total.toFixed(2) : '';
                    amount.dataset.autoAmount = '1';
                }
            }

            let paid = 0;
            rows.forEach((row) => {
                paid += Math.max(0, Number(row.querySelector('[data-pos-payment-amount]').value || 0));
            });

            const due = Math.max(0, total - paid);
            if (paidTarget) {
                paidTarget.textContent = paid.toFixed(2);
            }
            if (dueTarget) {
                dueTarget.textContent = due.toFixed(2);
                dueTarget.classList.toggle('text-danger', Math.abs(total - paid) >= 0.01);
                dueTarget.classList.toggle('text-success', total > 0 && Math.abs(total - paid) < 0.01);
            }

            updatePaymentNames();

            return Math.abs(total - paid) < 0.01 && rows.length > 0;
        };

        const updateRow = (row) => {
            const quantity = Math.max(1, Number(row.querySelector('[data-pos-line-quantity]').value || 1));
            const price = Math.max(0, Number(row.querySelector('[data-pos-line-price]').value || 0));
            const discount = Math.max(0, Number(row.querySelector('[data-pos-line-discount]').value || 0));
            const units = Number(row.dataset.units || 1);
            const unitLabel = row.dataset.unit || 'u';
            const subtotal = Math.max(0, (quantity * price) - discount);
            const available = Number(row.dataset.availablePackages || 0);

            row.querySelector('[data-pos-calculation]').textContent = `${quantity} x ${units} = ${quantity * units} ${unitLabel}`;
            row.querySelector('[data-pos-line-subtotal]').textContent = subtotal.toFixed(2);
            row.classList.toggle('table-warning', available > 0 && quantity > available);
        };

        const updateTotals = () => {
            let subtotal = 0;
            let discount = 0;
            const rows = items.querySelectorAll('[data-pos-row]');

            rows.forEach((row) => {
                updateRow(row);
                subtotal += Math.max(1, Number(row.querySelector('[data-pos-line-quantity]').value || 1)) * Math.max(0, Number(row.querySelector('[data-pos-line-price]').value || 0));
                discount += Math.max(0, Number(row.querySelector('[data-pos-line-discount]').value || 0));
            });

            form.querySelector('[data-pos-subtotal]').textContent = subtotal.toFixed(2);
            form.querySelector('[data-pos-discount]').textContent = discount.toFixed(2);
            const total = Math.max(0, subtotal - discount);
            form.querySelector('[data-pos-total]').textContent = total.toFixed(2);
            empty?.classList.toggle('d-none', rows.length > 0);
            const paymentsComplete = paymentMode?.value === 'mixed'
                ? updatePayments(total)
                : updateCashPayment(total);
            if (submit) {
                submit.disabled = rows.length === 0 || !paymentsComplete;
            }
            if (paymentMode?.value !== 'mixed') {
                updatePaymentNames();
            }
            updateNames();
        };

        const addLine = () => {
            const product = selectedOption(productPicker);
            const presentation = selectedOption(presentationPicker);
            const presentationData = tomOption(presentationPicker);
            const quantity = Math.max(1, Number(quantityPicker?.value || 1));

            if (!product?.value || !presentation?.value || !template) {
                Swal.fire({ icon: 'warning', title: 'Falta informacion', text: 'Selecciona producto y presentacion.' });
                return;
            }

            const availablePackages = Number(presentationData?.packages ?? presentation.dataset.packages ?? 0);
            const alreadySelected = selectedPackagesInCart(product.value, presentation.value);

            if (quantity + alreadySelected > availablePackages) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Stock insuficiente',
                    text: `Disponible: ${availablePackages} presentaciones. Ya agregaste ${alreadySelected}.`,
                });
                return;
            }

            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = template.innerHTML.trim();
            const row = wrapper.firstElementChild;
            const units = Number(presentationData?.units ?? presentation.dataset.units ?? 1);
            const basePrice = Number(product.dataset.price || 0);

            row.dataset.productId = product.value;
            row.dataset.presentationId = presentation.value;
            row.dataset.units = String(units);
            row.dataset.unit = product.dataset.unit || 'u';
            row.dataset.availablePackages = String(availablePackages);
            row.querySelector('[data-pos-product-input]').value = product.value;
            row.querySelector('[data-pos-presentation-input]').value = presentation.value;
            row.querySelector('[data-pos-product-name]').textContent = product.textContent.trim();
            row.querySelector('[data-pos-presentation-name]').textContent = presentationData?.baseName || presentation.dataset.baseName || presentation.textContent.trim();
            row.querySelector('[data-pos-line-quantity]').value = String(quantity);
            row.querySelector('[data-pos-line-quantity]').max = String(availablePackages);
            row.querySelector('[data-pos-line-price]').value = (basePrice * units).toFixed(2);

            items.append(row);
            productPicker.tomselect?.clear();
            presentationPicker.tomselect?.clear();
            presentationPicker.tomselect?.clearOptions();
            if (quantityPicker) {
                quantityPicker.value = '1';
            }
            updateTotals();
        };

        const syncCustomer = () => {
            const documentInput = form.querySelector('[data-pos-customer-document]');
            const nameInput = form.querySelector('[data-pos-customer-name]');
            const customerIdInput = form.querySelector('[data-pos-customer-id]');
            const status = form.querySelector('[data-pos-customer-status]');
            const documentNumber = documentInput?.value.trim() || '';
            const customerName = nameInput?.value.trim() || '';
            const customer = customers.find((item) => String(item.document_number || '').trim() === documentNumber);
            const autoFilledName = nameInput?.dataset.autoFilledName || '';

            const clearAutoFilledName = () => {
                if (nameInput && autoFilledName && nameInput.value.trim() === autoFilledName) {
                    nameInput.value = '';
                    nameInput.dataset.autoFilledName = '';
                }
            };

            if (!documentNumber) {
                if (customerIdInput) {
                    customerIdInput.value = '';
                }
                clearAutoFilledName();
                if (status) {
                    status.textContent = nameInput?.value.trim()
                        ? 'Se guardara el nombre solo en esta venta, sin registrar cliente.'
                        : 'Sin cliente asociado.';
                }
                return;
            }

            if (customer) {
                if (customerIdInput) {
                    customerIdInput.value = String(customer.id);
                }
                if (nameInput && (!nameInput.value.trim() || nameInput.value.trim() === autoFilledName)) {
                    nameInput.value = customer.name || '';
                    nameInput.dataset.autoFilledName = customer.name || '';
                }
                if (status) {
                    const sales = Number(customer.sales_count || 0);
                    status.textContent = `Cliente encontrado: ${customer.name}. Historial: ${sales} venta(s).`;
                }
                return;
            }

            if (customerIdInput) {
                customerIdInput.value = '';
            }
            clearAutoFilledName();
            if (status) {
                status.textContent = 'Documento nuevo. Ingresa el nombre para registrar el cliente.';
            }
        };

        form.querySelectorAll('[data-add-pos-item]').forEach((button) => {
            button.addEventListener('click', addLine);
        });

        form.addEventListener('input', (event) => {
            if (event.target.closest('[data-pos-line-quantity], [data-pos-line-price], [data-pos-line-discount]')) {
                const quantityInput = event.target.closest('[data-pos-line-quantity]');
                if (quantityInput) {
                    const row = quantityInput.closest('[data-pos-row]');
                    const available = Number(row?.dataset.availablePackages || 0);
                    const value = Math.max(1, Number(quantityInput.value || 1));
                    if (available > 0 && value > available) {
                        quantityInput.value = String(available);
                        Swal.fire({ icon: 'warning', title: 'Stock maximo', text: `Disponible: ${available} presentaciones.` });
                    }
                }
                updateTotals();
            }

            const paymentAmount = event.target.closest('[data-pos-payment-amount]');
            if (paymentAmount) {
                paymentAmount.dataset.autoAmount = '0';
                updateTotals();
            }

            if (event.target.closest('[data-pos-cash-received]')) {
                updateTotals();
            }
        });

        productPicker?.addEventListener('change', () => {
            refreshPresentationOptions();

            if (productPicker.value) {
                focusPresentation();
            }
        });
        presentationPicker?.addEventListener('change', () => {
            if (presentationPicker.value) {
                focusQuantity();
            }
        });
        quantityPicker?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addLine();
                window.setTimeout(() => focusTomSelect(productPicker), 80);
            }
        });
        form.querySelector('[data-pos-customer-document]')?.addEventListener('input', syncCustomer);
        form.querySelector('[data-pos-customer-document]')?.addEventListener('change', syncCustomer);
        form.querySelector('[data-pos-customer-name]')?.addEventListener('input', syncCustomer);

        const setPaymentMode = (mode) => {
            if (paymentMode) {
                paymentMode.value = mode;
            }
            cashPanel?.classList.toggle('d-none', mode !== 'cash');
            mixedPanel?.classList.toggle('d-none', mode !== 'mixed');
            useCash?.classList.toggle('btn-primary', mode === 'cash');
            useCash?.classList.toggle('btn-outline-primary', mode !== 'cash');
            useMixed?.classList.toggle('btn-primary', mode === 'mixed');
            useMixed?.classList.toggle('btn-outline-primary', mode !== 'mixed');
            updateTotals();
        };

        useCash?.addEventListener('click', () => setPaymentMode('cash'));
        useMixed?.addEventListener('click', () => setPaymentMode('mixed'));

        if (document.body.dataset.posQuickAddBound !== '1') {
            document.addEventListener('keydown', (event) => {
                if (event.key.toLowerCase() !== 'n' || !event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) {
                    return;
                }

                const activeForm = document.querySelector('[data-pos-sale-form]');
                const activeProductPicker = activeForm?.querySelector('[data-pos-product-picker]');

                if (!activeProductPicker) {
                    return;
                }

                event.preventDefault();
                focusTomSelect(activeProductPicker);
            });

            document.body.dataset.posQuickAddBound = '1';
        }

        form.addEventListener('click', (event) => {
            const addPayment = event.target.closest('[data-add-pos-payment]');
            if (addPayment && payments && paymentTemplate) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = paymentTemplate.innerHTML.trim();
                const row = wrapper.firstElementChild;
                const amount = row.querySelector('[data-pos-payment-amount]');

                if (amount) {
                    amount.dataset.autoAmount = '0';
                }

                payments.append(row);
                updateTotals();
                amount?.focus();

                return;
            }

            const remove = event.target.closest('[data-pos-remove]');
            if (remove) {
                remove.closest('[data-pos-row]')?.remove();
                updateTotals();

                return;
            }

            const removePayment = event.target.closest('[data-remove-pos-payment]');
            if (removePayment) {
                const rows = payments?.querySelectorAll('[data-pos-payment-row]') ?? [];
                const row = removePayment.closest('[data-pos-payment-row]');

                if (rows.length <= 1) {
                    row?.querySelectorAll('input').forEach((input) => {
                        input.value = '';
                        input.dataset.autoAmount = input.matches('[data-pos-payment-amount]') ? '1' : '0';
                    });
                } else {
                    row?.remove();
                }

                updateTotals();
            }
        });

        updateTotals();
        setPaymentMode(paymentMode?.value || 'cash');
        syncCustomer();
        form.dataset.posInitialized = '1';
    });
}

function initDefragmentForms(scope = document) {
    scope.querySelectorAll('[data-defragment-form]').forEach((form) => {
        if (form.dataset.defragmentInitialized === '1') {
            return;
        }

        const presentation = form.querySelector('[data-defragment-presentation]');
        const quantity = form.querySelector('[data-defragment-quantity]');
        const preview = form.querySelector('[data-defragment-preview]');

        const syncLimits = () => {
            const option = selectedOption(presentation);
            const max = Math.max(1, Number(option?.dataset.max || 1));
            const units = Math.max(1, Number(option?.dataset.units || 1));
            const value = Math.min(max, Math.max(1, Number(quantity?.value || 1)));

            if (quantity) {
                quantity.max = String(max);
                quantity.value = String(value);
            }

            if (preview) {
                preview.textContent = `Se convertiran ${value} empaque(s) en ${value * units} unidad(es). Disponible: ${max}.`;
            }
        };

        presentation?.addEventListener('change', syncLimits);
        quantity?.addEventListener('input', syncLimits);
        syncLimits();
        form.dataset.defragmentInitialized = '1';
    });
}

function initUserDropdowns() {
    document.querySelectorAll('[data-user-dropdown-toggle]').forEach((toggle) => {
        if (toggle.dataset.dropdownInitialized === '1') {
            return;
        }

        const dropdown = bootstrap.Dropdown.getOrCreateInstance(toggle, {
            autoClose: true,
            popperConfig: {
                strategy: 'fixed',
            },
        });

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            dropdown.toggle();
        });

        toggle.dataset.dropdownInitialized = '1';
    });
}

function initCashExpenseModal() {
    const modal = document.querySelector('[data-show-cash-expense-modal]');

    if (!modal) {
        return;
    }

    bootstrap.Modal.getOrCreateInstance(modal).show();
}

function initCashCloseModal() {
    const modal = document.querySelector('[data-show-cash-close-modal]');

    if (!modal) {
        return;
    }

    bootstrap.Modal.getOrCreateInstance(modal).show();
}

showInitialAlerts();
disableBusinessFormAutocomplete();
initTomSelects();
initPurchaseForm();
syncPointSaleWarehouse();
initPosSaleForm();
initDefragmentForms();
initUserDropdowns();
initCashExpenseModal();
initCashCloseModal();
initAdminDataTables();

document.addEventListener('click', (event) => {
    const modalTrigger = event.target.closest('[data-modal-url]');

    if (modalTrigger) {
        event.preventDefault();
        openAjaxModal(modalTrigger);

        return;
    }
});

document.addEventListener('change', (event) => {
    if (event.target.closest('[data-point-sale-branch]')) {
        syncPointSaleWarehouse(event.target.closest('form') ?? document);
    }
});

document.addEventListener('submit', (event) => {
    const ajaxForm = event.target.closest('[data-ajax-form]');
    const deleteForm = event.target.closest('[data-confirm-delete]');

    if (ajaxForm) {
        event.preventDefault();
        submitAjaxForm(ajaxForm);

        return;
    }

    if (deleteForm) {
        event.preventDefault();
        confirmDelete(deleteForm);
    }
});
