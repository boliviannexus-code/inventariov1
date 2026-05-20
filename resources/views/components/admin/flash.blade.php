@if (session('success'))
    <div data-swal-success="{{ session('success') }}"></div>
@endif

@if (($errors ?? null)?->any())
    <div data-swal-error="Revisa los datos ingresados."></div>
@endif
