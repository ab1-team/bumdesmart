<!-- BEGIN NAVBAR  -->
<header class="navbar navbar-expand-md d-print-none">
    <div class="container-xl">
        <!-- BEGIN NAVBAR TOGGLER -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
            aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle primary navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- END NAVBAR TOGGLER -->

        <!-- BEGIN NAVBAR LOGO -->
        <div class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="/dashboard" aria-label="Tabler">
                <img src="{{ asset('assets/img/logo/logo-transparent.png') }}" alt="{{ env('APP_NAME') }}"
                    width="40">
            </a>
        </div>
        <!-- END NAVBAR LOGO -->

        <div class="navbar-nav flex-row order-md-last">
            <!-- BEGIN USER MENU -->
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 p-0 px-2" data-bs-toggle="dropdown"
                    aria-label="Open user menu">
                    <span class="avatar avatar-sm"
                        @if (auth()->user()->foto) style="background-image: url({{ asset('storage/' . auth()->user()->foto) }})" @endif>
                        @if (!auth()->user()->foto)
                            {{ strtoupper(substr(auth()->user()->nama_lengkap ?? 'U', 0, 2)) }}
                        @endif
                    </span>
                    <div class="d-none d-xl-block ps-2">
                        <div>{{ auth()->user()->nama_lengkap }}</div>
                        <div class="mt-1 small text-secondary">{{ auth()->user()->no_hp }}</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a class="dropdown-item"
                        href="/profile"><!-- Download SVG icon from http://tabler.io/icons/icon/user -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" aria-hidden="true" focusable="false"
                            class="icon dropdown-item-icon icon-2">
                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                        </svg>
                        Profile</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" id="logout">Logout</a>
                </div>
            </div>
            <!-- END USER MENU -->
        </div>
    </div>
</header>
<!-- END NAVBAR  -->
