import * as coreui from '@coreui/coreui';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const ajaxModalElement = document.getElementById('ajaxModal');
const ajaxModal = ajaxModalElement ? new coreui.Modal(ajaxModalElement) : null;
const ajaxModalTitle = document.getElementById('ajaxModalTitle');
const ajaxModalBody = ajaxModalElement?.querySelector('[data-modal-body]');

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2600,
    timerProgressBar: true,
});

function isDesktop() {
    return window.matchMedia('(min-width: 992px)').matches;
}

function toggleSidebar() {
    if (isDesktop()) {
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('adminSidebarCollapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');

        return;
    }

    document.body.classList.toggle('sidebar-mobile-open');
}

function applyStoredSidebarState() {
    if (isDesktop() && localStorage.getItem('adminSidebarCollapsed') === '1') {
        document.body.classList.add('sidebar-collapsed');
    }
}

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
        })
        .catch((error) => {
            ajaxModal.hide();
            Swal.fire({ icon: 'error', title: 'Error', text: error.message });
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

applyStoredSidebarState();
showInitialAlerts();

document.addEventListener('click', (event) => {
    const sidebarToggle = event.target.closest('[data-admin-sidebar-toggle]');
    const modalTrigger = event.target.closest('[data-modal-url]');

    if (sidebarToggle) {
        event.preventDefault();
        toggleSidebar();

        return;
    }

    if (modalTrigger) {
        event.preventDefault();
        openAjaxModal(modalTrigger);

        return;
    }

    if (
        document.body.classList.contains('sidebar-mobile-open')
        && !event.target.closest('#adminSidebar')
        && !event.target.closest('[data-admin-sidebar-toggle]')
    ) {
        document.body.classList.remove('sidebar-mobile-open');
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

window.addEventListener('resize', () => {
    if (isDesktop()) {
        document.body.classList.remove('sidebar-mobile-open');
    }
});
