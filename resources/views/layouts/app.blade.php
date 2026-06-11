<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Warehouse Inventory') - Antigravity Systems</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="/styles.css">
    @yield('styles')
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">
                    <i data-lucide="package-open"></i>
                </div>
                <span class="brand-name">Gudang<span class="dot">.</span>io</span>
            </div>

            <nav class="sidebar-menu">
                <!-- Main Menu -->
                <div class="menu-group">
                    <span class="menu-label">NAVIGASI UTAMA</span>
                    <ul>
                        <li>
                            <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i data-lucide="layout-dashboard"></i>
                                Rangkuman Gudang
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('inventory.index') }}" class="menu-item {{ request()->routeIs('inventory.index') ? 'active' : '' }}">
                                <i data-lucide="package"></i>
                                Data Inventory
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('orders.index') }}" class="menu-item {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                                <i data-lucide="shopping-cart"></i>
                                Orders & Transaksi
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('history.index') }}" class="menu-item {{ request()->routeIs('history.index') ? 'active' : '' }}">
                                <i data-lucide="history"></i>
                                Riwayat Log
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Admin Action Menu Group -->
                @if(Auth::user()->role === 'Admin')
                <div class="menu-group" id="liAdminUsers">
                    <span class="menu-label">MANAJEMEN SISTEM</span>
                    <ul>
                        <li>
                            <a href="{{ route('users.index') }}" class="menu-item {{ request()->routeIs('users.index') ? 'active' : '' }}">
                                <i data-lucide="users"></i>
                                Pendaftaran Akun
                            </a>
                        </li>
                    </ul>
                </div>
                @endif
            </nav>

            <!-- Sidebar Footer Active Profile -->
            <div class="sidebar-footer" id="activeUserCard" onclick="document.getElementById('logout-form').submit();" title="Klik untuk Logout">
                <div class="user-avatar" id="activeAvatar">
                    {{ strtoupper(substr(Auth::user()->username, 0, 1)) }}
                </div>
                <div class="user-info">
                    <span class="role-name" id="activeRoleName">{{ '@' . Auth::user()->username }}</span>
                    <span class="session-id" id="activeSessionId">Role: {{ Auth::user()->role }}</span>
                </div>
                <i data-lucide="log-out" style="width: 16px; height: 16px; margin-left: auto; color: var(--text-dimmed);"></i>
            </div>
            
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </aside>

        <!-- Sidebar Mobile Overlay Backdrop -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Header Top Bar -->
            <header class="header">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i data-lucide="menu"></i>
                </button>
                <h1 class="page-title">@yield('page_title', 'Warehouse Inventory')</h1>
                
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                        <span class="dot" style="background-color: var(--instock); width: 8px; height: 8px;"></span>
                        Server: PHP/Laravel
                    </div>
                </div>
            </header>

            <!-- Page Specific Main Contents -->
            <div class="content-body">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();

        // Mobile Sidebar Drawer toggles
        document.getElementById('mobileMenuToggle').addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelector('.sidebar').classList.add('active');
            document.getElementById('sidebarOverlay').classList.add('active');
        });

        document.getElementById('sidebarOverlay').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });
    </script>
    @yield('scripts')
</body>
</html>
