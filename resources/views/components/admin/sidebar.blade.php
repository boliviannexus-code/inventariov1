<aside class="sidebar sidebar-dark sidebar-fixed bg-dark app-sidebar" id="adminSidebar">
    <div class="sidebar-brand sidebar-brand-full px-3">
        <a class="text-white text-decoration-none fw-semibold" href="{{ route('dashboard') }}">
            Inventario POS
        </a>
    </div>

    <ul class="sidebar-nav" data-coreui="navigation">
        <x-admin.sidebar-item
            route="dashboard"
            icon="cil-speedometer"
            label="Dashboard"
            :active="request()->routeIs('dashboard')"
        />

        @can('products.view')
            <x-admin.sidebar-item
                route="products.index"
                icon="cil-tags"
                label="Productos"
                :active="request()->routeIs('products.*')"
            />
        @endcan

        @can('categories.view')
            <x-admin.sidebar-item
                route="categories.index"
                icon="cil-folder"
                label="Categorias"
                :active="request()->routeIs('categories.*')"
            />
        @endcan

        @can('users.view')
            <li class="nav-title">Administracion</li>
            <x-admin.sidebar-item
                route="users.index"
                icon="cil-people"
                label="Usuarios"
                :active="request()->routeIs('users.*')"
            />
            @can('roles.view')
                <x-admin.sidebar-item
                    route="roles.index"
                    icon="cil-lock-locked"
                    label="Roles"
                    :active="request()->routeIs('roles.*')"
                />
            @endcan
            @can('permissions.view')
                <x-admin.sidebar-item
                    route="permissions.index"
                    icon="cil-shield-alt"
                    label="Permisos"
                    :active="request()->routeIs('permissions.*')"
                />
            @endcan
        @endcan

        <li class="nav-title">Operaciones futuras</li>
        <x-admin.sidebar-item icon="cil-cart" label="Compras" disabled />
        <x-admin.sidebar-item icon="cil-cash" label="Ventas POS" disabled />
        <x-admin.sidebar-item icon="cil-storage" label="Inventario" disabled />
        <x-admin.sidebar-item icon="cil-chart-line" label="Reportes" disabled />
    </ul>
</aside>
