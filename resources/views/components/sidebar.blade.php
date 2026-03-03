<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand text-center">
            <a href="">
                <img src="{{ asset('stisla/img/logoTapin.svg') }}" alt="logo" width="80" class="shadow-light rounded-circle mt-3">
            </a>
            <div style="font-weight: bold; font-size: 20px;">Kabupaten Tapin</div>
        </div>
        <ul class="sidebar-menu mt-3">
            <li class="menu-header">Beranda</li>

            {{-- Dashboard Static --}}
            <li class="nav-item {{ Request::segment(1) == 'dashboard' ? 'active' : '' }}">
                <a wire:navigate href="{{ route('dashboard') }}" class="nav-link">
                    <i class="fas fa-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            @foreach ($sidebarMenus->where('parent_id', null) as $menu)
                <li class="menu-header">{{ $menu->menu }}</li>
                {{-- @if ($menu->children->isEmpty()) --}}
                @foreach ($menu->children as $child)
                    <li class="nav-item {{ Request::segment(2) == strtolower($child->menu) ? 'active' : '' }}">
                        <a wire:navigate href="{{ route(strtolower($menu->menu . '.' . $child->menu)) }}" class="nav-link">
                            <i class="fas {{ $child->icon }}"></i>
                            <span>{{ $child->menu }}</span>
                        </a>
                    </li>
                @endforeach
            @endforeach
            <div class="hide-sidebar-mini mt-4 mb-4 p-3">
                <a href="#" class="btn btn-primary btn-lg btn-block btn-icon-split">
                    <i class="fas fa-rocket"></i> Documentation
                </a>
            </div>
        </ul>
    </aside>
</div>
