@php
    $user = auth()->user();
    if ($user && !$user->relationLoaded('modules')) {
        $user->load('modules');
    }
    $role = $user->role ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if($appSettings->site_description)
        <meta name="description" content="{{ \Illuminate\Support\Str::limit($appSettings->site_description, 150) }}">
    @endif
    @php
        $defaultSiteName = $appSettings->site_name ?? config('app.name', 'Laptop Management');
    @endphp
    <title>@yield('title', $defaultSiteName) · {{ $defaultSiteName }}</title>
    <link rel="icon" href="{{ asset('images/logo-sabira.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-sabira.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@1.13.1/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="sidebar-mini layout-fixed layout-footer-fixed layout-navbar-fixed">
@if(auth()->check())
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                    <i class="fas fa-th-large"></i>
                </a>
            </li>
            <li class="nav-item">
                <img src="{{ auth()->user()->avatar_url ?? 'Guest' }}" alt="Avatar" class="h-10 w-10 img-circle elevation-3" style="opacity:.8">
            </li>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle text-sm font-weight-semibold" data-toggle="dropdown">
                    {{ auth()->user()->name ?? 'Guest' }} <span class="text-muted">({{ ucfirst($role ?? '-') }})</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow">
                    @if(in_array($role, ['admin','staff']))
                        <a href="{{ route('profile.edit') }}" class="dropdown-item">
                            <i class="fas fa-user mr-2"></i> Profil
                        </a>
                        <div class="dropdown-divider"></div>
                    @endif
                    <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt mr-2"></i> Keluar
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    @php
        $brandLogoPath = $appSettings->logo_path
            ? asset('storage/' . $appSettings->logo_path)
            : asset('images/logo-sabira.png');
        $brandName = $appSettings->site_name ?? config('app.name', 'Laptop Management');
    @endphp
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('dashboard') }}" class="brand-link">
            <img src="{{ $brandLogoPath }}" alt="Logo" class="brand-image img-circle elevation-3" style="opacity:.8">
            <span class="brand-text font-weight-light">{{ $brandName }}</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="#" class="text-muted">{{ auth()->user()->name ?? 'Guest' }} <span class="text-muted">({{ ucfirst($role ?? '-') }})</span></a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-collapse-hide-child nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                    @if($user?->hasModule('dashboard'))
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link @if(request()->routeIs('dashboard')) active @endif">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p class="font-weight-medium">Dashboard</p>
                            </a>
                        </li>
                    @endif
                    @php
                        $hasSystemSettings = $user?->hasModule('admin.settings');
                        $hasMasterData = $user?->hasModule('admin.users')
                            || $user?->hasModule('admin.students')
                            || $user?->hasModule('admin.laptops')
                            || $user?->hasModule('admin.laptop-requests');
                        $masterMenuOpen = request()->routeIs('admin.users.*')
                            || request()->routeIs('admin.students.*')
                            || request()->routeIs('admin.laptops.*')
                            || request()->routeIs('admin.laptop-requests.*');
                        $monitoringMenuOpen = request()->routeIs('admin.violations.*')
                            || request()->routeIs('admin.sanctions.*')
                            || request()->routeIs('admin.reports.*');
                        $transactionsMenuOpen = request()->routeIs('staff.transactions.*')
                            || request()->routeIs('admin.transactions.mobile.*')
                            || request()->routeIs('staff.borrow.*')
                            || request()->routeIs('staff.return.*');
                        $operationsMenuOpen = request()->routeIs('staff.checklist.*')
                            || request()->routeIs('chatbot.*');
                        $studentMenuOpen = request()->routeIs('student.laptops.*')
                            || request()->routeIs('student.history');
                    @endphp

                    @if($hasSystemSettings || $hasMasterData)
                        @if($hasSystemSettings)
                            @php
                                $settingsMenuOpen = request()->routeIs('admin.settings.*');
                            @endphp
                            <li class="nav-item has-treeview {{ $settingsMenuOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $settingsMenuOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-cogs"></i>
                                    <p>Pengaturan Sistem<i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.settings.application') }}" class="nav-link @if(request()->routeIs('admin.settings.application*')) active @endif">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Identitas Aplikasi</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.settings.lending') }}" class="nav-link @if(request()->routeIs('admin.settings.lending*')) active @endif">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Peraturan Laptop</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.settings.mail') }}" class="nav-link @if(request()->routeIs('admin.settings.mail*')) active @endif">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Pengaturan Email</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.settings.safe-exam-browser') }}" class="nav-link @if(request()->routeIs('admin.settings.safe-exam-browser*')) active @endif">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Safe Exam Browser</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.settings.ai') }}" class="nav-link @if(request()->routeIs('admin.settings.ai*')) active @endif">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Integrasi AI</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if($hasMasterData)
                            <li class="nav-item has-treeview {{ $masterMenuOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $masterMenuOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-table"></i>
                                    <p>MASTER DATA<i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @if($user?->hasModule('admin.users'))
                                        <li class="nav-item">
                                            <a href="{{ route('admin.users.index') }}" class="nav-link @if(request()->routeIs('admin.users.*')) active @endif">
                                                <i class="nav-icon fas fa-users-cog"></i>
                                                <p>Manajemen User</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($user?->hasModule('admin.students'))
                                        <li class="nav-item">
                                            <a href="{{ route('admin.students.index') }}" class="nav-link @if(request()->routeIs('admin.students.*')) active @endif">
                                                <i class="nav-icon fas fa-user-graduate"></i>
                                                <p>Data Siswa</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($user?->hasModule('admin.laptops'))
                                        <li class="nav-item">
                                            <a href="{{ route('admin.laptops.index') }}" class="nav-link @if(request()->routeIs('admin.laptops.*')) active @endif">
                                                <i class="nav-icon fas fa-laptop"></i>
                                                <p>Data Laptop</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($user?->hasModule('admin.laptop-requests'))
                                        <li class="nav-item">
                                            <a href="{{ route('admin.laptop-requests.index') }}" class="nav-link @if(request()->routeIs('admin.laptop-requests.*')) active @endif">
                                                <i class="nav-icon fas fa-edit"></i>
                                                <p>Validasi Laptop</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @php
                            $hasMonitoring = $user?->hasModule('admin.violations')
                                || $user?->hasModule('admin.sanctions')
                                || $user?->hasModule('admin.reports');
                        @endphp
                        @if($hasMonitoring)
                            <li class="nav-item has-treeview {{ $monitoringMenuOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $monitoringMenuOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chart-line"></i>
                                    <p>Monitoring<i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @if($user?->hasModule('admin.violations'))
                                        <li class="nav-item">
                                            <a href="{{ route('admin.violations.index') }}" class="nav-link @if(request()->routeIs('admin.violations.*')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Pelanggaran</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($user?->hasModule('admin.sanctions'))
                                        <li class="nav-item">
                                            <a href="{{ route('admin.sanctions.index') }}" class="nav-link @if(request()->routeIs('admin.sanctions.*')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Sanksi</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($user?->hasModule('admin.reports'))
                                        <li class="nav-item">
                                            <a href="{{ route('admin.reports.index') }}" class="nav-link @if(request()->routeIs('admin.reports.*')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Laporan</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    @endif
                    @if(in_array($role, ['staff','admin']))
                        @php
                            $hasTransactions = $user?->hasModule('staff.transactions')
                                || $user?->hasModule('admin.transactions.mobile')
                                || $user?->hasModule('staff.borrow')
                                || $user?->hasModule('staff.return');
                        @endphp
                        @if($hasTransactions)
                            <li class="nav-item has-treeview {{ $transactionsMenuOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $transactionsMenuOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-exchange-alt"></i>
                                    <p>Transaksi<i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @if($user?->hasModule('staff.transactions'))
                                        <li class="nav-item">
                                            <a href="{{ route('staff.transactions.index') }}" class="nav-link @if(request()->routeIs('staff.transactions.*')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Transaksi Laptop</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($user?->hasModule('admin.transactions.mobile'))
                                        <li class="nav-item">
                                            <a href="{{ route('admin.transactions.mobile.index') }}" class="nav-link @if(request()->routeIs('admin.transactions.mobile.*')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Transaksi Mobile</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($user?->hasModule('staff.borrow'))
                                        <li class="nav-item">
                                            <a href="{{ route('staff.borrow.create') }}" class="nav-link @if(request()->routeIs('staff.borrow.*')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Form Peminjaman</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($user?->hasModule('staff.return'))
                                        <li class="nav-item">
                                            <a href="{{ route('staff.return.create') }}" class="nav-link @if(request()->routeIs('staff.return.*')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Form Pengembalian</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @php
                            $hasOperations = $user?->hasModule('staff.checklist')
                                || $user?->hasModule('chatbot');
                        @endphp
                        @if($hasOperations)
                            <li class="nav-item has-treeview {{ $operationsMenuOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $operationsMenuOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-toolbox"></i>
                                    <p>Operasional<i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @if($user?->hasModule('staff.checklist'))
                                        <li class="nav-item">
                                            <a href="{{ route('staff.checklist.create') }}" class="nav-link @if(request()->routeIs('staff.checklist.*')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Checklist Laptop</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($user?->hasModule('chatbot'))
                                        <li class="nav-item">
                                            <a href="{{ route('chatbot.index') }}" class="nav-link @if(request()->routeIs('chatbot.*')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Chatbot Peminjaman</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    @endif
                    @if($role === 'student')
                        @php
                            $hasStudentMenu = $user?->hasModule('student.laptops')
                                || $user?->hasModule('student.history');
                        @endphp
                        @if($hasStudentMenu)
                            <li class="nav-item has-treeview {{ $studentMenuOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $studentMenuOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-graduate"></i>
                                    <p>Akses Siswa<i class="fas fa-angle-left right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @if($user?->hasModule('student.laptops'))
                                        <li class="nav-item">
                                            <a href="{{ route('student.laptops.index') }}" class="nav-link @if(request()->routeIs('student.laptops.*')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Laptop Saya</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($user?->hasModule('student.history'))
                                        <li class="nav-item">
                                            <a href="{{ route('student.history') }}" class="nav-link @if(request()->routeIs('student.history')) active @endif">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Riwayat Peminjaman</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    @endif
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                @yield('content_header')
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Terjadi kesalahan:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer text-sm">
        <a>&copy; {{ now()->year }} Sistem Peminjaman Laptop - <a href="https://www.linkedin.com/in/ryand-arifriantoni">Ryand Arifriantoni</a></a>
        <div class="float-right d-none d-sm-inline-block">
            <b>Laravel</b> 12
        </div>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <div class="p-3 control-sidebar-content os-host os-theme-light os-host-resize-disabled os-host-scrollbar-horizontal-hidden os-host-transition os-host-overflow os-host-overflow-y">
            <h5>Customize Design</h5>
            <hr class="mb-2">
            <div class="mb-4">
                <input type="checkbox" class="mr-1" id="setting-dark-mode"
                       data-setting="darkMode" data-target="body" data-class="dark-mode">
                <label for="setting-dark-mode" class="mb-0">Dark Mode</label>
            </div>
            <h6 class="font-weight-bold text-muted text-uppercase text-xs">Header Options</h6>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-navbar-fixed"
                       data-setting="navbarFixed" data-target="body" data-class="layout-navbar-fixed">
                <label for="setting-navbar-fixed" class="mb-0">Fixed</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-navbar-dropdown-legacy"
                       data-setting="navbarDropdownLegacy" data-target="body" data-class="dropdown-legacy">
                <label for="setting-navbar-dropdown-legacy" class="mb-0">Dropdown Legacy Offset</label>
            </div>
            <div class="mb-4">
                <input type="checkbox" class="mr-1" id="setting-navbar-no-border"
                       data-setting="navbarNoBorder" data-target="body" data-class="navbar-no-border">
                <label for="setting-navbar-no-border" class="mb-0">No Border</label>
            </div>

            <h6 class="font-weight-bold text-muted text-uppercase text-xs">Sidebar Options</h6>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-sidebar-collapse"
                       data-setting="sidebarCollapse" data-target="body" data-class="sidebar-collapse">
                <label for="setting-sidebar-collapse" class="mb-0">Collapsed</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-sidebar-fixed"
                       data-setting="sidebarFixed" data-target="body" data-class="layout-fixed">
                <label for="setting-sidebar-fixed" class="mb-0">Fixed</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-sidebar-mini"
                       data-setting="sidebarMini" data-target="body" data-class="sidebar-mini"
                       data-conflicts="sidebar-mini-md sidebar-mini-xs">
                <label for="setting-sidebar-mini" class="mb-0">Sidebar Mini</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-sidebar-mini-md"
                       data-setting="sidebarMiniMd" data-target="body" data-class="sidebar-mini-md"
                       data-conflicts="sidebar-mini sidebar-mini-xs">
                <label for="setting-sidebar-mini-md" class="mb-0">Sidebar Mini MD</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-sidebar-mini-xs"
                       data-setting="sidebarMiniXs" data-target="body" data-class="sidebar-mini-xs"
                       data-conflicts="sidebar-mini sidebar-mini-md">
                <label for="setting-sidebar-mini-xs" class="mb-0">Sidebar Mini XS</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-nav-flat"
                       data-setting="navFlat" data-target=".nav-sidebar" data-class="nav-flat">
                <label for="setting-nav-flat" class="mb-0">Nav Flat Style</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-nav-legacy"
                       data-setting="navLegacy" data-target=".nav-sidebar" data-class="nav-legacy">
                <label for="setting-nav-legacy" class="mb-0">Nav Legacy Style</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-nav-compact"
                       data-setting="navCompact" data-target=".nav-sidebar" data-class="nav-compact">
                <label for="setting-nav-compact" class="mb-0">Nav Compact</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-nav-child-indent"
                       data-setting="navChildIndent" data-target=".nav-sidebar" data-class="nav-child-indent">
                <label for="setting-nav-child-indent" class="mb-0">Nav Child Indent</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-nav-child-hide"
                       data-setting="navChildHide" data-target=".nav-sidebar" data-class="nav-collapse-hide-child">
                <label for="setting-nav-child-hide" class="mb-0">Nav Child Hide on Collapse</label>
            </div>
            <div class="mb-4">
                <input type="checkbox" class="mr-1" id="setting-sidebar-no-expand"
                       data-setting="sidebarNoExpand" data-target="body" data-class="sidebar-no-expand">
                <label for="setting-sidebar-no-expand" class="mb-0">Disable Hover/Focus Auto-Expand</label>
            </div>

            <h6 class="font-weight-bold text-muted text-uppercase text-xs">Footer Options</h6>
            <div class="mb-4">
                <input type="checkbox" class="mr-1" id="setting-footer-fixed"
                       data-setting="footerFixed" data-target="body" data-class="layout-footer-fixed">
                <label for="setting-footer-fixed" class="mb-0">Fixed</label>
            </div>

            <h6 class="font-weight-bold text-muted text-uppercase text-xs">Small Text Options</h6>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-small-body"
                       data-setting="smallBody" data-target="body" data-class="text-sm">
                <label for="setting-small-body" class="mb-0">Body</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-small-navbar"
                       data-setting="smallNavbar" data-target=".main-header" data-class="text-sm">
                <label for="setting-small-navbar" class="mb-0">Navbar</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-small-brand"
                       data-setting="smallBrand" data-target=".brand-link" data-class="text-sm">
                <label for="setting-small-brand" class="mb-0">Brand</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" class="mr-1" id="setting-small-sidebar"
                       data-setting="smallSidebar" data-target=".nav-sidebar" data-class="text-sm">
                <label for="setting-small-sidebar" class="mb-0">Sidebar Nav</label>
            </div>
            <div class="mb-4">
                <input type="checkbox" class="mr-1" id="setting-small-footer"
                       data-setting="smallFooter" data-target=".main-footer" data-class="text-sm">
                <label for="setting-small-footer" class="mb-0">Footer</label>
            </div>

            <h6 class="font-weight-bold text-muted text-uppercase text-xs">Navbar Variants</h6>
            <div class="d-flex">
                <select id="setting-navbar-variant" class="custom-select mb-3 text-light border-0 bg-white"
                        data-setting="navbarVariant" data-target=".main-header"
                        data-remove="navbar-primary navbar-secondary navbar-info navbar-success navbar-danger navbar-indigo navbar-purple navbar-pink navbar-navy navbar-lightblue navbar-teal navbar-cyan navbar-dark navbar-gray-dark navbar-gray navbar-light navbar-warning navbar-white navbar-orange">
                    <option value="navbar-white navbar-light">Default (White)</option>
                    <option value="navbar-primary navbar-dark" class="bg-primary">Primary</option>
                    <option value="navbar-secondary navbar-dark" class="bg-secondary">Secondary</option>
                    <option value="navbar-info navbar-dark" class="bg-info">Info</option>
                    <option value="navbar-success navbar-dark" class="bg-success">Success</option>
                    <option value="navbar-danger navbar-dark" class="bg-danger">Danger</option>
                    <option value="navbar-indigo navbar-dark" class="bg-indigo">Indigo</option>
                    <option value="navbar-purple navbar-dark" class="bg-purple">Purple</option>
                    <option value="navbar-pink navbar-dark" class="bg-pink">Pink</option>
                    <option value="navbar-navy navbar-dark" class="bg-navy">Navy</option>
                    <option value="navbar-lightblue navbar-dark" class="bg-lightblue">Lightblue</option>
                    <option value="navbar-teal navbar-dark" class="bg-teal">Teal</option>
                    <option value="navbar-cyan navbar-dark" class="bg-cyan">Cyan</option>
                    <option value="navbar-dark" class="bg-dark">Dark</option>
                    <option value="navbar-gray-dark navbar-dark" class="bg-gray-dark">Gray Dark</option>
                    <option value="navbar-gray navbar-light" class="bg-gray">Gray</option>
                    <option value="navbar-light navbar-light" class="bg-light">Light</option>
                    <option value="navbar-warning navbar-light" class="bg-warning">Warning</option>
                    <option value="navbar-white navbar-light" class="bg-white">White</option>
                    <option value="navbar-orange navbar-dark" class="bg-orange">Orange</option>
                </select>
            </div>

            <h6 class="font-weight-bold text-muted text-uppercase text-xs">Accent Color Variants</h6>
            <div class="d-flex mb-3">
                <select id="setting-accent-color" class="custom-select border-0"
                        data-setting="accentColor" data-target="body"
                        data-remove="accent-primary accent-warning accent-info accent-danger accent-success accent-indigo accent-lightblue accent-navy accent-purple accent-fuchsia accent-pink accent-maroon accent-orange accent-lime accent-teal accent-olive">
                    <option value="">None Selected</option>
                    <option value="accent-primary" class="bg-primary">Primary</option>
                    <option value="accent-warning" class="bg-warning">Warning</option>
                    <option value="accent-info" class="bg-info">Info</option>
                    <option value="accent-danger" class="bg-danger">Danger</option>
                    <option value="accent-success" class="bg-success">Success</option>
                    <option value="accent-indigo" class="bg-indigo">Indigo</option>
                    <option value="accent-lightblue" class="bg-lightblue">Lightblue</option>
                    <option value="accent-navy" class="bg-navy">Navy</option>
                    <option value="accent-purple" class="bg-purple">Purple</option>
                    <option value="accent-fuchsia" class="bg-fuchsia">Fuchsia</option>
                    <option value="accent-pink" class="bg-pink">Pink</option>
                    <option value="accent-maroon" class="bg-maroon">Maroon</option>
                    <option value="accent-orange" class="bg-orange">Orange</option>
                    <option value="accent-lime" class="bg-lime">Lime</option>
                    <option value="accent-teal" class="bg-teal">Teal</option>
                    <option value="accent-olive" class="bg-olive">Olive</option>
                </select>
            </div>

            <h6 class="font-weight-bold text-muted text-uppercase text-xs">Dark Sidebar Variants</h6>
            <div class="d-flex mb-3">
                <select id="setting-sidebar-dark" class="custom-select text-light border-0 bg-primary"
                        data-setting="sidebarDarkVariant" data-target=".main-sidebar"
                        data-remove="sidebar-dark-primary sidebar-dark-warning sidebar-dark-info sidebar-dark-danger sidebar-dark-success sidebar-dark-indigo sidebar-dark-lightblue sidebar-dark-navy sidebar-dark-purple sidebar-dark-fuchsia sidebar-dark-pink sidebar-dark-maroon sidebar-dark-orange sidebar-dark-lime sidebar-dark-teal sidebar-dark-olive sidebar-light-primary sidebar-light-warning sidebar-light-info sidebar-light-danger sidebar-light-success sidebar-light-indigo sidebar-light-lightblue sidebar-light-navy sidebar-light-purple sidebar-light-fuchsia sidebar-light-pink sidebar-light-maroon sidebar-light-orange sidebar-light-lime sidebar-light-teal sidebar-light-olive sidebar-dark sidebar-light">
                    <option value="sidebar-dark-primary">Default (Primary)</option>
                    <option value="sidebar-dark-primary">Primary</option>
                    <option value="sidebar-dark-warning">Warning</option>
                    <option value="sidebar-dark-info">Info</option>
                    <option value="sidebar-dark-danger">Danger</option>
                    <option value="sidebar-dark-success">Success</option>
                    <option value="sidebar-dark-indigo">Indigo</option>
                    <option value="sidebar-dark-lightblue">Lightblue</option>
                    <option value="sidebar-dark-navy">Navy</option>
                    <option value="sidebar-dark-purple">Purple</option>
                    <option value="sidebar-dark-fuchsia">Fuchsia</option>
                    <option value="sidebar-dark-pink">Pink</option>
                    <option value="sidebar-dark-maroon">Maroon</option>
                    <option value="sidebar-dark-orange">Orange</option>
                    <option value="sidebar-dark-lime">Lime</option>
                    <option value="sidebar-dark-teal">Teal</option>
                    <option value="sidebar-dark-olive">Olive</option>
                </select>
            </div>

            <h6 class="font-weight-bold text-muted text-uppercase text-xs">Light Sidebar Variants</h6>
            <div class="d-flex mb-3">
                <select id="setting-sidebar-light" class="custom-select border-0"
                        data-setting="sidebarLightVariant" data-target=".main-sidebar"
                        data-remove="sidebar-dark-primary sidebar-dark-warning sidebar-dark-info sidebar-dark-danger sidebar-dark-success sidebar-dark-indigo sidebar-dark-lightblue sidebar-dark-navy sidebar-dark-purple sidebar-dark-fuchsia sidebar-dark-pink sidebar-dark-maroon sidebar-dark-orange sidebar-dark-lime sidebar-dark-teal sidebar-dark-olive sidebar-light-primary sidebar-light-warning sidebar-light-info sidebar-light-danger sidebar-light-success sidebar-light-indigo sidebar-light-lightblue sidebar-light-navy sidebar-light-purple sidebar-light-fuchsia sidebar-light-pink sidebar-light-maroon sidebar-light-orange sidebar-light-lime sidebar-light-teal sidebar-light-olive sidebar-dark sidebar-light">
                    <option value="">None Selected</option>
                    <option value="sidebar-light-primary">Primary</option>
                    <option value="sidebar-light-warning">Warning</option>
                    <option value="sidebar-light-info">Info</option>
                    <option value="sidebar-light-danger">Danger</option>
                    <option value="sidebar-light-success">Success</option>
                    <option value="sidebar-light-indigo">Indigo</option>
                    <option value="sidebar-light-lightblue">Lightblue</option>
                    <option value="sidebar-light-navy">Navy</option>
                    <option value="sidebar-light-purple">Purple</option>
                    <option value="sidebar-light-fuchsia">Fuchsia</option>
                    <option value="sidebar-light-pink">Pink</option>
                    <option value="sidebar-light-maroon">Maroon</option>
                    <option value="sidebar-light-orange">Orange</option>
                    <option value="sidebar-light-lime">Lime</option>
                    <option value="sidebar-light-teal">Teal</option>
                    <option value="sidebar-light-olive">Olive</option>
                </select>
            </div>

            <h6 class="font-weight-bold text-muted text-uppercase text-xs">Brand Logo Variants</h6>
            <div class="d-flex">
                <select id="setting-brand-variant" class="custom-select border-0 w-100"
                        data-setting="brandVariant" data-target=".brand-link"
                        data-remove="bg-primary bg-secondary bg-info bg-success bg-danger bg-indigo bg-purple bg-pink bg-navy bg-lightblue bg-teal bg-cyan bg-dark bg-gray-dark bg-gray bg-light bg-warning bg-white bg-orange">
                    <option value="">None Selected</option>
                    <option value="bg-primary">Primary</option>
                    <option value="bg-secondary">Secondary</option>
                    <option value="bg-info">Info</option>
                    <option value="bg-success">Success</option>
                    <option value="bg-danger">Danger</option>
                    <option value="bg-indigo">Indigo</option>
                    <option value="bg-purple">Purple</option>
                    <option value="bg-pink">Pink</option>
                    <option value="bg-navy">Navy</option>
                    <option value="bg-lightblue">Lightblue</option>
                    <option value="bg-teal">Teal</option>
                    <option value="bg-cyan">Cyan</option>
                    <option value="bg-dark">Dark</option>
                    <option value="bg-gray-dark">Gray Dark</option>
                    <option value="bg-gray">Gray</option>
                    <option value="bg-light">Light</option>
                    <option value="bg-warning">Warning</option>
                    <option value="bg-white">White</option>
                    <option value="bg-orange">Orange</option>
                </select>
            </div>
            <button type="button" id="setting-brand-clear" class="btn btn-link btn-sm px-0 mt-1">Clear</button>
        </div>
    </aside>
    <!-- /.control-sidebar -->
@else
    <div class="auth-wrapper d-flex align-items-center justify-content-center min-vh-100 bg-light">
        <div class="auth-content w-100" style="max-width: 420px;">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Terjadi kesalahan:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </div>
@endif

@includeWhen(config('app.debug'), 'layouts.debug')

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@1.13.1/js/jquery.overlayScrollbars.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script>
    $(function () {
        const STORAGE_KEY = 'ui:layout-settings';

        const loadSettings = () => {
            try {
                const raw = localStorage.getItem(STORAGE_KEY);
                if (!raw) {
                    return {};
                }
                const parsed = JSON.parse(raw);
                return typeof parsed === 'object' && parsed !== null ? parsed : {};
            } catch (error) {
                console.warn('Failed to read layout settings:', error);
                return {};
            }
        };

        const settings = loadSettings();

        const persistSettings = () => {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
            } catch (error) {
                console.warn('Failed to store layout settings:', error);
            }
        };

        const updateSetting = (key, value) => {
            if (value === '' || value === null || value === undefined) {
                if (key in settings) {
                    delete settings[key];
                    persistSettings();
                }
                return;
            }
            settings[key] = value;
            persistSettings();
        };

        const parseList = (value) => {
            if (!value) {
                return [];
            }
            if (Array.isArray(value)) {
                return value.filter(Boolean);
            }
            if (typeof value === 'string') {
                return value
                    .split(/\s+/)
                    .map((item) => item.trim())
                    .filter(Boolean);
            }
            return [];
        };

        const resolveTargets = (selector) => {
            if (!selector || selector === 'body') {
                return $('body');
            }
            return $(selector);
        };

        const initCheckbox = ($input) => {
            const key = $input.data('setting');
            const classNames = parseList($input.data('class'));
            if (!key || !classNames.length) {
                return;
            }

            const targetSelector = $input.data('target') || 'body';
            const conflicts = parseList($input.data('conflicts'));
            const defaultAttr = $input.data('default');
            const defaultState = defaultAttr !== undefined ? String(defaultAttr) === '1' : undefined;

            const computeCurrentState = () => {
                const $targets = resolveTargets(targetSelector);
                if (!$targets.length) {
                    return false;
                }
                return classNames.every((className) => $targets.first().hasClass(className));
            };

            const storedValue = settings[key];
            let enabled;
            if (typeof storedValue === 'boolean') {
                enabled = storedValue;
            } else if (storedValue === '1' || storedValue === 1) {
                enabled = true;
            } else if (storedValue === '0' || storedValue === 0) {
                enabled = false;
            } else if (defaultState !== undefined) {
                enabled = defaultState;
            } else {
                enabled = computeCurrentState();
            }

            const apply = (value) => {
                const $targets = resolveTargets(targetSelector);
                if (!$targets.length) {
                    return;
                }
                if (conflicts.length) {
                    $targets.removeClass(conflicts.join(' '));
                }
                classNames.forEach((className) => {
                    $targets.toggleClass(className, value);
                });
                updateSetting(key, value);
            };

            apply(enabled);
            $input.prop('checked', enabled);

            $input.on('change', function () {
                const value = $(this).is(':checked');
                apply(value);
            });
        };

        const initSelect = ($select) => {
            const key = $select.data('setting');
            if (!key) {
                return null;
            }

            const targetSelector = $select.data('target') || 'body';
            const $targets = resolveTargets(targetSelector);
            if (!$targets.length) {
                return null;
            }

            const optionClasses = new Set();
            $select.find('option').each(function () {
                parseList($(this).val()).forEach((cls) => optionClasses.add(cls));
            });
            parseList($select.data('remove')).forEach((cls) => optionClasses.add(cls));
            const classesToRemove = Array.from(optionClasses);

            const storedValue = settings[key];
            const defaultValueAttr = $select.data('default');
            const initialValue =
                storedValue !== undefined
                    ? storedValue
                    : defaultValueAttr !== undefined
                        ? defaultValueAttr
                        : $select.val() || '';

            const applyValue = (value, { save } = { save: true }) => {
                const sanitized = typeof value === 'string' ? value : '';
                if (classesToRemove.length) {
                    $targets.removeClass(classesToRemove.join(' '));
                }
                parseList(sanitized).forEach((cls) => $targets.addClass(cls));
                if (save) {
                    updateSetting(key, sanitized);
                }
            };

            applyValue(initialValue, { save: false });
            if ($select.val() !== initialValue) {
                $select.val(initialValue);
            }

            $select.on('change', function () {
                applyValue($select.val() || '');
            });

            return {
                setValue(value, options = {}) {
                    const sanitized = typeof value === 'string' ? value : '';
                    applyValue(sanitized, { save: options.save !== false });
                    if ($select.val() !== sanitized) {
                        $select.val(sanitized);
                    }
                    if (options.trigger) {
                        $select.trigger('change');
                    }
                },
                getValue() {
                    return $select.val() || '';
                },
            };
        };

        $('[data-setting][type="checkbox"]').each(function () {
            initCheckbox($(this));
        });

        const selectControllers = {
            navbarVariant: initSelect($('#setting-navbar-variant')),
            accentColor: initSelect($('#setting-accent-color')),
            sidebarDark: initSelect($('#setting-sidebar-dark')),
            sidebarLight: initSelect($('#setting-sidebar-light')),
            brandVariant: initSelect($('#setting-brand-variant')),
        };

        if (selectControllers.sidebarDark && selectControllers.sidebarLight) {
            $('#setting-sidebar-dark').on('change', function () {
                if ($(this).val()) {
                    selectControllers.sidebarLight.setValue('', { save: true });
                }
            });
            $('#setting-sidebar-light').on('change', function () {
                if ($(this).val()) {
                    selectControllers.sidebarDark.setValue('', { save: true });
                }
            });
        }

        $('#setting-brand-clear').on('click', function (event) {
            event.preventDefault();
            if (selectControllers.brandVariant) {
                selectControllers.brandVariant.setValue('', { save: true });
            }
        });

        const defaultDom = '<"row mb-3 align-items-center"<"col-sm-12 col-md-6 d-flex align-items-center gap-2"Bl><"col-sm-12 col-md-6"f>>' +
            '<"row"<"col-sm-12"tr>>' +
            '<"row mt-3 align-items-center"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>';

        const defaultButtons = [
            { extend: 'copy', text: 'Copy' },
            { extend: 'csv', text: 'CSV' },
            { extend: 'excel', text: 'Excel' },
            { extend: 'pdf', text: 'PDF' },
            { extend: 'print', text: 'Print' },
            { extend: 'colvis', text: 'Column visibility' },
        ];

        $('.datatable-default').each(function () {
            const tableElement = this;
            const $table = $(tableElement);
            if ($.fn.DataTable.isDataTable(tableElement)) {
                return;
            }

            const selectColumnIndex = $table.find('thead th.select-checkbox').index();
            const columnDefs = [];
            if (selectColumnIndex >= 0) {
                columnDefs.push({
                    targets: selectColumnIndex,
                    orderable: false,
                    searchable: false,
                    className: 'text-center align-middle',
                });
            }

            const dataTable = $table.DataTable({
                dom: defaultDom,
                buttons: defaultButtons,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json',
                },
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    ['10', '25', '50', '100', 'Semua'],
                ],
                autoWidth: false,
                columnDefs,
            });

            const $buttonContainer = $(dataTable.buttons().container());
            $buttonContainer.addClass('dt-buttons btn-group flex-wrap btn-group-sm');
        });
    });

    $(document).on('change', '.js-select-all', function () {
        const targetSelector = $(this).data('target');
        const $table = $(targetSelector);
        if (!$table.length) {
            return;
        }
        const isChecked = $(this).is(':checked');
        $table.find('tbody .js-row-checkbox').prop('checked', isChecked);
    });

    $(document).on('change', '.js-row-checkbox', function () {
        const $table = $(this).closest('table');
        if (!$table.length) {
            return;
        }
        const tableId = $table.attr('id');
        if (!tableId) {
            return;
        }
        const selector = `input.js-select-all[data-target="#${tableId}"]`;
        const $master = $(selector);
        if (!$master.length) {
            return;
        }
        const total = $table.find('tbody .js-row-checkbox').length;
        const checked = $table.find('tbody .js-row-checkbox:checked').length;
        $master.prop('checked', total > 0 && total === checked);
    });

    const toggleBulkTarget = ($actionSelect) => {
        const targetSelector = $actionSelect.data('toggle-target');
        if (!targetSelector) {
            return;
        }
        const $target = $(targetSelector);
        if (!$target.length) {
            return;
        }

        if ($actionSelect.val() === 'status') {
            $target.removeClass('d-none');
            $target.find('select, input').prop('disabled', false);
        } else {
            $target.addClass('d-none');
            $target.find('select, input').prop('disabled', true);
        }
    };

    $(document).on('change', '.js-bulk-action', function () {
        toggleBulkTarget($(this));
    });

    $('.js-bulk-action').each(function () {
        toggleBulkTarget($(this));
    });

    $(document).on('submit', '.js-bulk-form', function (event) {
        const $form = $(this);
        const formId = $form.attr('id');
        const tableSelector = $form.data('table');
        const $table = tableSelector ? $(tableSelector) : $form.find('table');
        const checkedCount = $table.find('tbody .js-row-checkbox:checked').length;
        if (checkedCount === 0) {
            event.preventDefault();
            alert('Pilih minimal satu data sebelum melakukan aksi.');
            return;
        }

        const $actionSelect = formId ? $(`.js-bulk-action[form="${formId}"]`) : $form.find('.js-bulk-action');
        const actionValue = $actionSelect.val();
        if (!actionValue) {
            event.preventDefault();
            alert('Pilih aksi yang ingin dijalankan.');
            return;
        }

        if (actionValue === 'status') {
            const $statusSelect = formId
                ? $(`select[name="status"][form="${formId}"]:enabled`)
                : $form.find('select[name="status"]:enabled');
            if ($statusSelect.length && !$statusSelect.val()) {
                event.preventDefault();
                alert('Pilih status baru yang ingin diterapkan.');
                return;
            }
        }

        const confirmAction = $actionSelect.data('confirm-action');
        const confirmMessage = $actionSelect.data('confirm-message');
        if (confirmAction && confirmMessage && actionValue === confirmAction) {
            if (!confirm(confirmMessage)) {
                event.preventDefault();
            }
        }
    });

    (function () {
        if (window.__lookupSuggestionsInitialised) {
            return;
        }
        window.__lookupSuggestionsInitialised = true;

        const setupLookupInput = (input) => {
            const endpoint = input.dataset.endpoint;
            const suggestions = document.getElementById(input.dataset.suggestions);
            const helper = document.getElementById(input.dataset.helper);
            const type = input.dataset.lookup;

            if (!endpoint || !suggestions) {
                return;
            }

            let controller;
            let debounceTimer;
            let results = [];
            let selectedIndex = -1;
            let isOpen = false;

            const hideSuggestions = () => {
                suggestions.classList.add('d-none');
                suggestions.innerHTML = '';
                results = [];
                selectedIndex = -1;
                isOpen = false;
            };

            const showSuggestions = () => {
                suggestions.classList.remove('d-none');
                isOpen = true;
            };

            const formatStudent = (item) => {
                const nis = item.student_number ?? '-';
                const card = item.card_code ? `Kode kartu: ${item.card_code}` : 'Kode kartu belum ditetapkan';
                const kelas = item.classroom ? `Kelas ${item.classroom}` : 'Kelas belum ditetapkan';
                return `<strong>${item.name}</strong><div class="meta">NIS: ${nis} • ${kelas}</div><div class="meta">${card}</div>`;
            };

            const formatLaptop = (item) => {
                const status = item.status ? item.status.toUpperCase() : 'UNKNOWN';
                const ownerNis = item.owner_student_number ? `Pemilik NIS: ${item.owner_student_number}` : 'Pemilik belum ditetapkan';
                const ownerName = item.owner_name ? `Nama Pemilik: ${item.owner_name}` : '';
                const lines = [
                    `<strong>${item.code} • ${item.name}</strong>`,
                    `<div class="meta">${ownerNis}</div>`,
                ];
                if (ownerName) {
                    lines.push(`<div class="meta">${ownerName}</div>`);
                }
                lines.push(`<div class="meta">Status: ${status}</div>`);
                return lines.join('');
            };

            const highlightSelection = () => {
                const buttons = suggestions.querySelectorAll('button[data-index]');
                buttons.forEach((button) => {
                    const index = Number(button.dataset.index);
                    if (index === selectedIndex) {
                        button.classList.add('active');
                        button.scrollIntoView({ block: 'nearest' });
                    } else {
                        button.classList.remove('active');
                    }
                });
            };

            const renderSuggestions = (items) => {
                suggestions.innerHTML = '';
                selectedIndex = -1;
                items.forEach((item, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.index = String(index);
                    button.innerHTML = type === 'students' ? formatStudent(item) : formatLaptop(item);
                    suggestions.appendChild(button);
                });
                showSuggestions();
                highlightSelection();
            };

            const updateHelper = (item) => {
                if (!helper) {
                    return;
                }
                if (type === 'students') {
                    const nis = item.student_number ?? '-';
                    const card = item.card_code ?? '-';
                    helper.textContent = `${item.name} • NIS: ${nis} • Kode: ${card}`;
                } else {
                    const owner = item.owner_student_number ? ` • Pemilik NIS: ${item.owner_student_number}` : '';
                    helper.textContent = `${item.code} • ${item.name} (${(item.status || '').toUpperCase()})${owner}`;
                }
            };

            const resolveValue = (item) => item.qr_code || item.card_code || item.student_number || item.owner_student_number || item.code || item.name;

            const selectItem = (item) => {
                if (!item) {
                    return;
                }

                const value = resolveValue(item);
                if (value) {
                    input.value = value;
                }
                updateHelper(item);
                hideSuggestions();

                const selectionEvent = new CustomEvent('lookup:selected', {
                    detail: { item },
                    bubbles: true,
                });
                input.dispatchEvent(selectionEvent);

                const nextFieldId = input.dataset.next;
                if (nextFieldId) {
                    const nextInput = document.getElementById(nextFieldId);
                    if (nextInput) {
                        nextInput.focus();
                        if (typeof nextInput.select === 'function') {
                            nextInput.select();
                        }
                    }
                }
            };

            const performLookup = (term) => {
                if (!term) {
                    hideSuggestions();
                    if (helper) {
                        helper.textContent = helper.dataset.default ?? '';
                    }
                    return;
                }

                if (controller) {
                    controller.abort();
                }

                controller = new AbortController();

                const separator = endpoint.includes('?') ? '&' : '?';
                fetch(`${endpoint}${separator}q=${encodeURIComponent(term)}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (!Array.isArray(data) || data.length === 0) {
                            hideSuggestions();
                            return;
                        }
                        results = data;
                        renderSuggestions(data);
                    })
                    .catch(() => {
                        // silently ignore aborted/failed requests
                    });
            };

            input.addEventListener('input', (event) => {
                const term = event.target.value.trim();
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => performLookup(term), 200);
            });

            input.addEventListener('focus', (event) => {
                const term = event.target.value.trim();
                if (term) {
                    performLookup(term);
                }
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown') {
                    if (!results.length) {
                        return;
                    }
                    event.preventDefault();
                    if (!isOpen) {
                        showSuggestions();
                    }
                    selectedIndex = selectedIndex + 1 >= results.length ? 0 : selectedIndex + 1;
                    highlightSelection();
                    return;
                }

                if (event.key === 'ArrowUp') {
                    if (!results.length) {
                        return;
                    }
                    event.preventDefault();
                    if (!isOpen) {
                        showSuggestions();
                    }
                    selectedIndex = selectedIndex <= 0 ? results.length - 1 : selectedIndex - 1;
                    highlightSelection();
                    return;
                }

                if (event.key === 'Enter') {
                    if (!results.length) {
                        return;
                    }
                    const term = input.value.trim();

                    if (selectedIndex >= 0 && results[selectedIndex]) {
                        event.preventDefault();
                        selectItem(results[selectedIndex]);
                        return;
                    }

                    if (term !== '' && results[0]) {
                        const lowerTerm = term.toLowerCase();
                        const topItem = results[0];
                        const isExactMatch = [topItem.qr_code, topItem.card_code, topItem.student_number, topItem.owner_student_number, topItem.code]
                            .filter((value) => typeof value === 'string')
                            .some((value) => value.toLowerCase() === lowerTerm);

                        if (isExactMatch) {
                            event.preventDefault();
                            selectItem(topItem);
                        }
                    }
                }

                if (event.key === 'Escape' && isOpen) {
                    hideSuggestions();
                }
            });

            document.addEventListener('click', (event) => {
                if (event.target !== input && !suggestions.contains(event.target)) {
                    hideSuggestions();
                }
            });

            suggestions.addEventListener('mousedown', (event) => {
                const button = event.target.closest('button[data-index]');
                if (!button) {
                    return;
                }
                event.preventDefault();
                selectedIndex = Number(button.dataset.index);
                selectItem(results[selectedIndex] ?? null);
            });
        };

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-lookup]').forEach(setupLookupInput);
        });
    })();
</script>
@stack('scripts')
@yield('scripts')
</body>
</html>
