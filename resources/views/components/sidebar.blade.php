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
            <li class="nav-item {{ Request::segment(1) == 'dashboard' ? 'active' : '' }}">
                <a wire:navigate href="{{ route('dashboard') }}" class="nav-link">
                    <i class="fas fa-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="menu-header">Data Pegawai</li>
            <li class="nav-item {{ Request::segment(1) == 'pegawai' ? 'active' : '' }}">
                <a wire:navigate href="{{ route('pegawai') }}" class="nav-link">
                    <i class="far fa-solid fa-user"></i><span>Pegawai</span></a>
            </li>
            {{-- @endif --}}

            {{-- Menu SKPD --}}
            @if (auth()->user()->role === 'admin')
                <li class="nav-item dropdown">
                    <a href="" class="nav-link"><i class="far fa-solid fa-city"></i><span>SKPD</span></a>
                </li>
            @endif

            {{-- Menu Pengajuan Cuti --}}
            {{-- @if (auth()->user()->role === 'admin' || auth()->user()->role === 'pns' || auth()->user()->role === 'kepala_skpd') --}}
            <li class="nav-item dropdown">
                <a href="" class="nav-link"><i class="far fa-solid fa-city"></i><span>Permohonan Cuti</span></a>
            </li>
            {{-- @endif --}}

            {{-- Menu Hari Libur nasional dan sabtu minggu --}}
            @if (auth()->user()->role === 'admin')
                <li class="nav-item dropdown">
                    <a href="" class="nav-link"><i class="far fa-solid fa-city"></i><span>Hari
                            Libur</span></a>
                </li>
            @endif
            {{-- Menu Saldo Cuti per Jenis --}}
            @if (auth()->user()->role === 'admin')
                <li class="nav-item dropdown">
                    <a href="" class="nav-link"><i class="far fa-solid fa-city"></i><span>Saldo Cuti</span></a>
                </li>
            @endif
            {{-- Menu Setting kuota Cuti per Jenis --}}
            @if (auth()->user()->role === 'admin')
                <li class="nav-item dropdown">
                    <a href="" class="nav-link"><i class="far fa-solid fa-city"></i><span>Setting Kuota Cuti</span></a>
                </li>
            @endif






            <div class="hide-sidebar-mini mt-4 mb-4 p-3">
                <a href="#" class="btn btn-primary btn-lg btn-block btn-icon-split">
                    <i class="fas fa-rocket"></i> Documentation
                </a>
            </div>
    </aside>
</div>
