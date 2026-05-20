<header class="header header-sticky bg-white border-bottom px-3">
    <button
        class="header-toggler"
        type="button"
        data-admin-sidebar-toggle
        aria-label="Mostrar u ocultar menu"
    >
        <i class="cil-menu"></i>
    </button>

    <div class="container-fluid">
        <div>
            <h1 class="h5 mb-0">@yield('page-title', 'Panel administrativo')</h1>
            @hasSection('page-subtitle')
                <div class="text-body-secondary small">@yield('page-subtitle')</div>
            @endif
        </div>

        <div class="dropdown">
            <button class="btn btn-light border dropdown-toggle" type="button" data-coreui-toggle="dropdown" aria-expanded="false">
                <i class="cil-user me-1"></i>{{ auth()->user()->name ?? 'Usuario' }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-body-secondary">{{ auth()->user()->email ?? '' }}</span></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item" type="submit">
                            <i class="cil-account-logout me-2"></i>Salir
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
