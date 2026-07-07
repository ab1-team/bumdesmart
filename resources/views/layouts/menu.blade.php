@php
    $isCentral = !tenancy()->initialized;

    if ($isCentral) {
        $listMenu = [
            [
                'title' => 'Dashboard',
                'url' => '/master/dashboard',
                'icon' => 'home',
            ],
            [
                'title' => 'Owners',
                'url'   => '/master/owner',
                'icon'  => 'person',
            ],
            [
                'title' => 'Invoice',
                'url'   => '/master/invoice',
                'icon'  => 'receipt_long',
            ],
        ];
    } else {
        $userRole = auth()->user()->role ?? null;
        if ($userRole) {
            $assignedMenuIds = $userRole->menus()->pluck('menus.id')->toArray();
            $menus = $userRole->menus()->whereNull('parent_id')->orderBy('order')->get();

            $listMenu = $menus->map(function ($menu) use ($assignedMenuIds) {
                $item = [
                    'title' => $menu->title,
                    'url'   => $menu->url,
                    'icon'  => $menu->icon,
                ];

                if ($menu->children->count() > 0) {
                    $item['child'] = $menu->children->whereIn('id', $assignedMenuIds)->map(function ($child) use ($assignedMenuIds) {
                        $subItem = [
                            'title' => $child->title,
                            'url'   => $child->url,
                        ];

                        if ($child->children->count() > 0) {
                            $subItem['child'] = $child->children->whereIn('id', $assignedMenuIds)->map(function ($subChild) {
                                return [
                                    'title' => $subChild->title,
                                    'url'   => $subChild->url,
                                ];
                            })->toArray();
                        }

                        return $subItem;
                    })->toArray();
                }

                return $item;
            })->toArray();
        } else {
            $listMenu = [];
        }
    } // end else (business user menu)

    $path = '/' . request()->path();
@endphp

<div class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar">
            <div class="container-xl">
                <div class="row flex-column flex-md-row flex-fill align-items-center">
                    <div class="col">
                        <!-- BEGIN NAVBAR MENU -->
                        <nav aria-label="Primary">
                            <!-- BEGIN NAVBAR MENU -->
                            <ul class="navbar-nav">
                                @foreach ($listMenu as $menu)
                                    @if (isset($menu['child']))
                                        @php
                                            $active = false;
                                            if (str_contains($path, $menu['url'])) {
                                                $active = true;
                                            }
                                        @endphp

                                        <li class="nav-item dropdown {{ $active ? 'active' : '' }}">
                                            <a class="nav-link dropdown-toggle" href="#navbar-form"
                                                data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button"
                                                aria-haspopup="true" aria-expanded="false">
                                                <span class="nav-icon d-md-none d-lg-inline-block">
                                                    <span class="material-symbols-outlined">
                                                        {{ $menu['icon'] }}
                                                    </span>
                                                </span>
                                                <span class="nav-link-title"> {{ $menu['title'] }} </span>
                                            </a>
                                            <div class="dropdown-menu">
                                                @foreach ($menu['child'] as $child)
                                                    @if (isset($child['child']))
                                                        @php
                                                            $subMenuActive = false;
                                                            if (str_contains($path, $child['url'])) {
                                                                $subMenuActive = true;
                                                            }
                                                        @endphp
                                                        {{-- Nested Dropdown --}}
                                                        <div class="dropdown-submenu">
                                                            <a class="dropdown-item {{ $subMenuActive ? 'active' : '' }} dropdown-toggle"
                                                                href="#">
                                                                {{ $child['title'] }}
                                                            </a>
                                                            <div class="dropdown-menu">
                                                                @foreach ($child['child'] as $subChild)
                                                                    <a class="dropdown-item {{ $path == $subChild['url'] ? 'active' : '' }}"
                                                                        href="{{ $subChild['url'] }}">
                                                                        {{ $subChild['title'] }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @else
                                                        {{-- Regular Dropdown Item --}}
                                                        <a class="dropdown-item {{ $path == $child['url'] ? 'active' : '' }}"
                                                            href="{{ $child['url'] }}">
                                                            {{ $child['title'] }}
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </li>
                                    @else
                                        <li class="nav-item {{ $path == $menu['url'] ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ $menu['url'] }}">
                                                <span class="nav-icon d-md-none d-lg-inline-block">
                                                    <span class="material-symbols-outlined">
                                                        {{ $menu['icon'] }}
                                                    </span>
                                                </span>
                                                <span class="nav-link-title"> {{ $menu['title'] }} </span>
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                            <!-- END NAVBAR MENU -->
                        </nav>
                        <!-- END NAVBAR MENU -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
