@extends('layouts.app')

@section('title', 'Orders & Transaksi')
@section('page_title', 'Warehouse Orders & Transaksi')

@section('content')
<!-- Orders Metrics Row -->
<div class="metrics-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <!-- Order Revenue Card -->
    <div class="metric-card" style="padding: 20px; border-radius: 12px; background-color: var(--bg-card); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 8px;">
        <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">ESTIMASI REVENUE</span>
        <strong id="totalOrderRevenue" style="font-size: 1.6rem; font-weight: 800; color: var(--text-main);">
            ${{ number_format($totalRevenue) }}
        </strong>
        <span id="totalOrdersCount" style="font-size: 0.75rem; color: var(--text-dimmed);">{{ $activeOrdersCount }} Active Orders</span>
    </div>
    
    <!-- Status Breakdown Card -->
    <div class="metric-card" style="padding: 20px; border-radius: 12px; background-color: var(--bg-card); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 12px; grid-column: span 2;">
        <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">METRIK STATUS PESANAN</span>
        
        <!-- Progress Bar Visualizer -->
        @php
            $totalOrders = $completedCount + $processedCount + $pendingCount + $cancelledCount ?: 1;
            $compPct = ($completedCount / $totalOrders) * 100;
            $procPct = ($processedCount / $totalOrders) * 100;
            $pendPct = ($pendingCount / $totalOrders) * 100;
            $cancPct = ($cancelledCount / $totalOrders) * 100;
        @endphp
        <div class="progress-bar-container" style="display: flex; height: 8px; border-radius: 4px; overflow: hidden; background-color: var(--bg-input);">
            <div id="barOrderCompleted" style="width: {{ $compPct }}%; background-color: var(--instock); transition: var(--transition);"></div>
            <div id="barOrderProcessed" style="width: {{ $procPct }}%; background-color: var(--info); transition: var(--transition);"></div>
            <div id="barOrderPending" style="width: {{ $pendPct }}%; background-color: var(--lowstock); transition: var(--transition);"></div>
            <div id="barOrderCancelled" style="width: {{ $cancPct }}%; background-color: var(--outofstock); transition: var(--transition);"></div>
        </div>

        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600; flex-wrap: wrap; gap: 10px;">
            <span style="display: flex; align-items: center; gap: 6px; color: var(--text-muted);">
                <span class="dot" style="background-color: var(--instock); width: 8px; height: 8px; border-radius: 50%;"></span>
                Completed: <strong style="color: var(--text-main);" id="lblOrderCompletedCount">{{ $completedCount }}</strong>
            </span>
            <span style="display: flex; align-items: center; gap: 6px; color: var(--text-muted);">
                <span class="dot" style="background-color: var(--info); width: 8px; height: 8px; border-radius: 50%;"></span>
                Processed: <strong style="color: var(--text-main);" id="lblOrderProcessedCount">{{ $processedCount }}</strong>
            </span>
            <span style="display: flex; align-items: center; gap: 6px; color: var(--text-muted);">
                <span class="dot" style="background-color: var(--lowstock); width: 8px; height: 8px; border-radius: 50%;"></span>
                Pending: <strong style="color: var(--text-main);" id="lblOrderPendingCount">{{ $pendingCount }}</strong>
            </span>
            <span style="display: flex; align-items: center; gap: 6px; color: var(--text-muted);">
                <span class="dot" style="background-color: var(--outofstock); width: 8px; height: 8px; border-radius: 50%;"></span>
                Cancelled: <strong style="color: var(--text-main);" id="lblOrderCancelledCount">{{ $cancelledCount }}</strong>
            </span>
        </div>
    </div>
</div>

<!-- Controls Bar -->
<div class="controls-bar" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
    <form action="{{ route('orders.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; flex-grow: 1;">
        <!-- Search Input -->
        <div class="search-box" style="position: relative; min-width: 240px; flex-grow: 1; max-width: 400px;">
            <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dimmed); width: 16px; height: 16px;"></i>
            <input type="text" name="search" id="searchOrderInput" placeholder="Cari ID, Pelanggan atau Barang..." value="{{ request('search') }}" style="width: 100%; padding: 10px 16px 10px 38px; background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem;">
        </div>

        <!-- Status Filter -->
        <select name="status" id="filterOrderStatus" style="padding: 10px 16px; background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
            <option value="">Semua Status</option>
            <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
            <option value="Processed" {{ request('status') === 'Processed' ? 'selected' : '' }}>Processed</option>
            <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
            <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>

        <button type="submit" class="btn" style="background-color: var(--primary); color: #fff; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
            Filter
        </button>

        @if(request()->anyFilled(['search', 'status']))
            <a href="{{ route('orders.index') }}" class="btn" style="background-color: var(--bg-input); color: var(--text-main); padding: 10px 16px; border-radius: 8px; border: 1px solid var(--border-color); font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                Reset
            </a>
        @endif
    </form>

    <!-- Trigger Add Order Modal -->
    <button id="btnOpenAddOrderModal" class="btn" style="background-color: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
        Buat Order Baru
    </button>
</div>

<!-- Orders Table Card -->
<div class="card" style="background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px;">
    <div style="overflow-x: auto; width: 100%;">
        <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background-color: var(--bg-input);">
                    <th style="padding: 14px 16px; width: 40px;"><input type="checkbox" disabled></th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">ID ORDER</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">NAMA PELANGGAN</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">ID BARANG</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">NAMA BARANG</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">QTY</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">TANGGAL ORDER</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">STATUS</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted); width: 80px;">AKSI</th>
                </tr>
            </thead>
            <tbody id="ordersTableBody">
                @forelse($orders as $o)
                    @php
                        $namaBarang = $o->barang ? $o->barang->nama_barang : 'Barang Terhapus';
                        
                        $badgeClass = 'badge-lowstock'; 
                        if ($o->status_order === 'Completed') $badgeClass = 'badge-instock';
                        elseif ($o->status_order === 'Processed') $badgeClass = 'badge-info';
                        elseif ($o->status_order === 'Cancelled') $badgeClass = 'badge-outofstock';

                        $isCancelled = $o->status_order === 'Cancelled';
                        $isCompleted = $o->status_order === 'Completed';
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color); transition: var(--transition);">
                        <td style="padding: 14px 16px;"><input type="checkbox" disabled></td>
                        <td style="padding: 14px 16px;"><code style="color: var(--primary); font-weight: 700; font-size: 0.8rem;">{{ $o->id_order }}</code></td>
                        <td style="padding: 14px 16px; font-weight: 600; color: var(--text-main);">{{ $o->nama_pelanggan }}</td>
                        <td style="padding: 14px 16px; color: var(--text-muted);"><code>{{ $o->id_barang }}</code></td>
                        <td style="padding: 14px 16px; color: var(--text-main);">{{ $namaBarang }}</td>
                        <td style="padding: 14px 16px; font-weight: 700; color: var(--text-main);">{{ $o->jumlah_order }}</td>
                        <td style="padding: 14px 16px; color: var(--text-muted);">{{ $o->tanggal_order }}</td>
                        <td style="padding: 14px 16px;">
                            <span class="badge {{ $badgeClass }}">{{ $o->status_order }}</span>
                        </td>
                        <td style="padding: 14px 16px; position: relative;">
                            <div class="action-dropdown-container">
                                <button class="btn-action action-menu-btn" data-id="{{ $o->id_order }}" style="background: none; border: none; padding: 6px; cursor: pointer; color: var(--text-muted); border-radius: 4px;">
                                    <i data-lucide="more-horizontal" style="width: 16px; height: 16px;"></i>
                                </button>
                                <div class="action-dropdown-menu" id="dropdown-{{ $o->id_order }}" style="display: none; position: absolute; right: 16px; top: 40px; background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-md); z-index: 100; min-width: 160px; padding: 6px 0;">
                                    <button class="dropdown-item process-order-btn" data-id="{{ $o->id_order }}" {{ $isCancelled || $isCompleted ? 'disabled style=opacity:0.4;cursor:not-allowed;' : '' }} style="width: 100%; text-align: left; padding: 8px 14px; background: none; border: none; font-size: 0.8rem; color: var(--text-main); cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                        <i data-lucide="play" style="width: 14px; height: 14px;"></i> Process Order
                                    </button>
                                    <button class="dropdown-item complete-order-btn" data-id="{{ $o->id_order }}" {{ $isCancelled || $isCompleted ? 'disabled style=opacity:0.4;cursor:not-allowed;' : '' }} style="width: 100%; text-align: left; padding: 8px 14px; background: none; border: none; font-size: 0.8rem; color: var(--text-main); cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                        <i data-lucide="check-circle-2" style="width: 14px; height: 14px;"></i> Complete Order
                                    </button>
                                    <button class="dropdown-item cancel-order-btn" data-id="{{ $o->id_order }}" {{ $isCancelled ? 'disabled style=opacity:0.4;cursor:not-allowed;' : '' }} style="width: 100%; text-align: left; padding: 8px 14px; background: none; border: none; font-size: 0.8rem; color: var(--outofstock); cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                        <i data-lucide="x-circle" style="width: 14px; height: 14px;"></i> Cancel & Restore
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-dimmed); padding: 40px 0; font-style: italic;">Tidak ada data order yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border-top: 1px solid var(--border-color); background-color: var(--bg-input); flex-wrap: wrap; gap: 10px;">
        <span style="font-size: 0.8rem; color: var(--text-muted);">
            Menampilkan <strong style="color: var(--text-main);">{{ $orders->firstItem() ?: 0 }}</strong> - <strong style="color: var(--text-main);">{{ $orders->lastItem() ?: 0 }}</strong> dari <strong style="color: var(--text-main);">{{ $orders->total() }}</strong> total record
        </span>
        
        <div style="display: flex; gap: 5px;">
            {{ $orders->links('pagination::simple-bootstrap-5') }}
        </div>
    </div>
</div>

<!-- ================= MODAL TAMBAH ORDER DIALOG ================= -->
<div class="modal-backdrop" id="orderModal">
    <div class="modal-container modal-content" style="max-width: 500px; padding: 30px; border-radius: 16px; background-color: var(--bg-main); border: 1px solid var(--border-color); box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main);">Checkout Order Baru</h3>
            <button id="btnCloseOrderModal" style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <!-- Validation errors -->
        <div id="orderValidationErrorCallout" class="error-callout" style="display: none; background-color: var(--outofstock-bg); border: 1px solid var(--outofstock-border); border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; color: var(--outofstock); font-size: 0.8rem; align-items: flex-start; gap: 8px;">
            <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;"></i>
            <ul id="orderErrorList" style="margin: 0; padding-left: 18px;"></ul>
        </div>

        <form id="orderForm">
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">NAMA PELANGGAN</label>
                <input type="text" id="nama_pelanggan" class="login-input" style="padding: 10px 12px 10px 12px;" placeholder="Masukkan nama pelanggan..." required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">PILIH BARANG GUDANG</label>
                <select id="order_id_barang" class="login-input" style="padding: 10px 12px 10px 12px;" required>
                    <option value="">-- Pilih Barang --</option>
                    @foreach($availableProducts as $prod)
                        <option value="{{ $prod->id_barang }}">{{ $prod->nama_barang }} - {{ $prod->id_barang }} - Stok: {{ $prod->jumlah_barang }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
                <div class="form-group">
                    <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">QTY ORDER</label>
                    <input type="number" id="jumlah_order" class="login-input" style="padding: 10px 12px 10px 12px;" placeholder="1" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">TANGGAL ORDER</label>
                    <input type="date" id="tanggal_order" class="login-input" style="padding: 10px 12px 10px 12px;" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">STATUS AWAL</label>
                <select id="status_order" class="login-input" style="padding: 10px 12px 10px 12px;">
                    <option value="Pending" selected>Pending</option>
                    <option value="Processed">Processed</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" id="btnCancelOrderModal" class="btn" style="background-color: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-color); padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn" style="background-color: var(--primary); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer;">Checkout Order</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Toggle action dropdown menus
    document.querySelectorAll('.action-menu-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = btn.getAttribute('data-id');
            const dropdown = document.getElementById(`dropdown-${id}`);
            const isShow = dropdown.style.display === 'block';
            
            // Close all dropdowns
            document.querySelectorAll('.action-dropdown-menu').forEach(m => m.style.display = 'none');
            
            if (!isShow) {
                dropdown.style.display = 'block';
            }
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.action-dropdown-menu').forEach(m => m.style.display = 'none');
    });

    // Handle process order
    document.querySelectorAll('.complete-order-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = btn.getAttribute('data-id');
            updateOrderStatus(id, 'Completed');
        });
    });

    document.querySelectorAll('.process-order-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = btn.getAttribute('data-id');
            updateOrderStatus(id, 'Processed');
        });
    });

    async function updateOrderStatus(orderId, status) {
        try {
            const res = await fetch(`/orders/${orderId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status_order: status })
            });

            const result = await res.json();
            if (res.ok && result.success) {
                window.location.reload();
            } else {
                alert(result.errors ? result.errors.join('\n') : 'Gagal memperbarui status order.');
            }
        } catch (err) {
            console.error(err);
            alert('Kesalahan koneksi ke server.');
        }
    }

    // Cancel order & restore stock
    document.querySelectorAll('.cancel-order-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const id = btn.getAttribute('data-id');
            if (!confirm(`Apakah Anda yakin ingin membatalkan order #${id}? Stok barang terkait akan dikembalikan.`)) {
                return;
            }

            try {
                const res = await fetch(`/orders/${id}/cancel`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const result = await res.json();
                if (res.ok && result.success) {
                    alert(result.message);
                    window.location.reload();
                } else {
                    alert(result.errors ? result.errors.join('\n') : 'Gagal membatalkan order.');
                }
            } catch (err) {
                console.error(err);
                alert('Kesalahan koneksi ke server.');
            }
        });
    });

    // Add order modal toggle
    const modal = document.getElementById('orderModal');
    const closeBtn = document.getElementById('btnCloseOrderModal');
    const cancelBtn = document.getElementById('btnCancelOrderModal');
    const orderForm = document.getElementById('orderForm');
    const errorCallout = document.getElementById('orderValidationErrorCallout');
    const errorList = document.getElementById('orderErrorList');

    function openModal() {
        errorCallout.style.display = 'none';
        orderForm.reset();
        document.getElementById('tanggal_order').value = new Date().toISOString().split('T')[0];
        modal.classList.add('open');
    }

    function closeModal() {
        modal.classList.remove('open');
    }

    document.getElementById('btnOpenAddOrderModal').addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Order form submit via AJAX
    orderForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            nama_pelanggan: document.getElementById('nama_pelanggan').value,
            id_barang: document.getElementById('order_id_barang').value,
            jumlah_order: document.getElementById('jumlah_order').value,
            tanggal_order: document.getElementById('tanggal_order').value,
            status_order: document.getElementById('status_order').value
        };

        try {
            const res = await fetch('/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const result = await res.json();
            if (res.ok && result.success) {
                closeModal();
                window.location.reload();
            } else {
                errorList.innerHTML = '';
                const errors = result.errors || ['Gagal membuat order.'];
                errors.forEach(err => {
                    const li = document.createElement('li');
                    li.textContent = err;
                    errorList.appendChild(li);
                });
                errorCallout.style.display = 'flex';
            }
        } catch (err) {
            console.error(err);
            errorList.innerHTML = '<li>Gagal menghubungi server.</li>';
            errorCallout.style.display = 'flex';
        }
    });
</script>
@endsection
