@extends('layouts.app')

@section('title', 'Rangkuman Gudang')
@section('page_title', 'Warehouse Summary Dashboard')

@section('content')
<!-- Dashboard Grid Layout -->
<div class="dashboard-grid" style="display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 24px;">
    
    <!-- 1. Category Summary Section -->
    <section class="dashboard-section">
        <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="layout-grid" style="color: var(--primary); width: 20px; height: 20px;"></i>
            Jumlah Barang per Kategori
        </h2>
        <div id="homeCategoriesGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            @foreach($categorySummary as $catName => $data)
            <div class="metric-card" style="padding: 20px; border-top: 3px solid var(--primary); border-radius: 12px; background-color: var(--bg-card); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); border-left: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="product-img-box" style="width:36px; height:36px; display: flex; align-items: center; justify-content: center; background-color: var(--primary-glow); border-radius: 8px; color: var(--primary);">
                        <i data-lucide="{{ $data['icon'] }}" style="width:18px; height:18px;"></i>
                    </div>
                    <strong style="color:var(--text-main); font-size:0.95rem;">{{ $catName }}</strong>
                </div>
                <div style="display:flex; flex-direction:column; gap:4px; margin-top: 4px;">
                    <span style="font-size:0.8rem; color:var(--text-muted);">Jenis Item Unik: <strong style="color:var(--text-main);">{{ $data['unique_count'] }}</strong></span>
                    <span style="font-size:0.8rem; color:var(--text-muted);">Total Stok Unit: <strong style="color:var(--text-main);">{{ $data['total_qty'] }} Unit</strong></span>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    <!-- 2. Bottom Grid split into Order Stats and Recent Orders -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
        
        <!-- Order Stats Section -->
        <section class="dashboard-section" style="background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 14px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <i data-lucide="bar-chart-3" style="color: var(--primary); width: 20px; height: 20px;"></i>
                Ringkasan Status Order
            </h2>
            <div id="homeOrdersStats" style="display: flex; flex-direction: column; gap: 14px;">
                @php
                    $statusRows = [
                        ['label' => 'Completed', 'count' => $orderSummary['Completed'], 'color' => '#10b981'],
                        ['label' => 'Processed', 'count' => $orderSummary['Processed'], 'color' => '#3b82f6'],
                        ['label' => 'Pending', 'count' => $orderSummary['Pending'], 'color' => '#f59e0b'],
                        ['label' => 'Cancelled', 'count' => $orderSummary['Cancelled'], 'color' => '#ef4444']
                    ];
                @endphp

                @foreach($statusRows as $row)
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.9rem; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    <span style="display:flex; align-items:center; gap:8px;">
                        <span class="dot" style="background-color:{{ $row['color'] }}; width:10px; height:10px; border-radius: 50%; display: inline-block;"></span>
                        <span style="color:var(--text-muted);">{{ $row['label'] }}</span>
                    </span>
                    <strong style="color:var(--text-main); font-size:1rem;">{{ $row['count'] }} Orders</strong>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Recent Orders Section -->
        <section class="dashboard-section" style="background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 14px; padding: 24px; box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <i data-lucide="shopping-bag" style="color: var(--primary); width: 20px; height: 20px;"></i>
                Pesanan Pelanggan Terbaru
            </h2>
            <ul id="homeRecentOrdersList" style="list-style: none; display: flex; flex-direction: column; gap: 12px; padding: 0;">
                @forelse($recentOrders as $order)
                    @php
                        $item = $barang->firstWhere('id_barang', $order->id_barang);
                        $namaBarang = $item ? $item->nama_barang : 'Barang Terhapus';
                        
                        $badgeColor = '#f59e0b';
                        if ($order->status_order === 'Completed') $badgeColor = '#10b981';
                        elseif ($order->status_order === 'Processed') $badgeColor = '#3b82f6';
                        elseif ($order->status_order === 'Cancelled') $badgeColor = '#ef4444';
                    @endphp
                    <li class="user-list-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background-color: var(--bg-input); border-radius: 10px; border: 1px solid var(--border-color);">
                        <div style="display:flex; flex-direction:column; gap:3px;">
                            <span style="font-weight:700; color:var(--text-main); font-size:0.85rem;">{{ $order->id_order }} - {{ $order->nama_pelanggan }}</span>
                            <span style="font-size:0.75rem; color:var(--text-muted);">{{ $namaBarang }} ({{ $order->jumlah_order }} Unit)</span>
                        </div>
                        <span class="badge" style="background-color:{{ $badgeColor }}15; color:{{ $badgeColor }}; border:1px solid {{ $badgeColor }}30; padding:4px 10px; font-size:0.7rem; border-radius:6px; font-weight: 600;">
                            {{ $order->status_order }}
                        </span>
                    </li>
                @empty
                    <li style="color:var(--text-dimmed); font-style:italic; font-size:0.85rem; padding: 15px 0; text-align: center;">Belum ada riwayat pesanan.</li>
                @endforelse
            </ul>
        </section>

    </div>
</div>
@endsection
