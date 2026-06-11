@extends('layouts.app')

@section('title', 'Data Inventory')
@section('page_title', 'Warehouse Inventory Items')

@section('content')
<!-- Inventory Metrics Row -->
<div class="metrics-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <!-- Asset Value Card -->
    <div class="metric-card" style="padding: 20px; border-radius: 12px; background-color: var(--bg-card); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 8px;">
        <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">TOTAL NILAI ASET</span>
        <strong id="totalAssetValue" style="font-size: 1.6rem; font-weight: 800; color: var(--text-main);">
            ${{ number_format($totalAssetValue) }}
        </strong>
        <span id="totalProductCount" style="font-size: 0.75rem; color: var(--text-dimmed);">{{ $barang->total() }} Products</span>
    </div>
    
    <!-- Stock Status Breakdown Card -->
    <div class="metric-card" style="padding: 20px; border-radius: 12px; background-color: var(--bg-card); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 12px; grid-column: span 2;">
        <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">STATUS KETERSEDIAAN STOK</span>
        
        <!-- Progress Bar Visualizer -->
        @php
            $totalStatus = $inStockCount + $lowStockCount + $outOfStockCount ?: 1;
            $inPct = ($inStockCount / $totalStatus) * 100;
            $lowPct = ($lowStockCount / $totalStatus) * 100;
            $outPct = ($outOfStockCount / $totalStatus) * 100;
        @endphp
        <div class="progress-bar-container" style="display: flex; height: 8px; border-radius: 4px; overflow: hidden; background-color: var(--bg-input);">
            <div id="barInStock" style="width: {{ $inPct }}%; background-color: var(--instock); transition: var(--transition);"></div>
            <div id="barLowStock" style="width: {{ $lowPct }}%; background-color: var(--lowstock); transition: var(--transition);"></div>
            <div id="barOutOfStock" style="width: {{ $outPct }}%; background-color: var(--outofstock); transition: var(--transition);"></div>
        </div>

        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600;">
            <span style="display: flex; align-items: center; gap: 6px; color: var(--text-muted);">
                <span class="dot" style="background-color: var(--instock); width: 8px; height: 8px; border-radius: 50%;"></span>
                In stock: <strong style="color: var(--text-main);" id="lblInStockCount">{{ $inStockCount }}</strong>
            </span>
            <span style="display: flex; align-items: center; gap: 6px; color: var(--text-muted);">
                <span class="dot" style="background-color: var(--lowstock); width: 8px; height: 8px; border-radius: 50%;"></span>
                Low stock: <strong style="color: var(--text-main);" id="lblLowStockCount">{{ $lowStockCount }}</strong>
            </span>
            <span style="display: flex; align-items: center; gap: 6px; color: var(--text-muted);">
                <span class="dot" style="background-color: var(--outofstock); width: 8px; height: 8px; border-radius: 50%;"></span>
                Out of stock: <strong style="color: var(--text-main);" id="lblOutOfStockCount">{{ $outOfStockCount }}</strong>
            </span>
        </div>
    </div>
</div>

<!-- Controls / Search & Filters Bar -->
<div class="controls-bar" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
    <form action="{{ route('inventory.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; flex-grow: 1;">
        <!-- Search Input -->
        <div class="search-box" style="position: relative; min-width: 240px; flex-grow: 1; max-width: 400px;">
            <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dimmed); width: 16px; height: 16px;"></i>
            <input type="text" name="search" id="searchInput" placeholder="Cari ID atau Nama Barang..." value="{{ request('search') }}" style="width: 100%; padding: 10px 16px 10px 38px; background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem;">
        </div>

        <!-- Category Dropdown -->
        <select name="category" id="filterCategory" style="padding: 10px 16px; background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
            <option value="">Semua Kategori</option>
            <option value="Electronics" {{ request('category') === 'Electronics' ? 'selected' : '' }}>Electronics</option>
            <option value="Apparel" {{ request('category') === 'Apparel' ? 'selected' : '' }}>Apparel</option>
            <option value="Wellness" {{ request('category') === 'Wellness' ? 'selected' : '' }}>Wellness</option>
            <option value="Home & Living" {{ request('category') === 'Home & Living' ? 'selected' : '' }}>Home & Living</option>
            <option value="Others" {{ request('category') === 'Others' ? 'selected' : '' }}>Others</option>
        </select>

        <!-- Stock Status Dropdown -->
        <select name="status" id="filterStatus" style="padding: 10px 16px; background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
            <option value="">Semua Status</option>
            <option value="instock" {{ request('status') === 'instock' ? 'selected' : '' }}>In stock</option>
            <option value="lowstock" {{ request('status') === 'lowstock' ? 'selected' : '' }}>Low stock</option>
            <option value="outofstock" {{ request('status') === 'outofstock' ? 'selected' : '' }}>Out of stock</option>
        </select>

        <button type="submit" class="btn" style="background-color: var(--primary); color: #fff; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
            Filter
        </button>
        
        @if(request()->anyFilled(['search', 'category', 'status', 'start_date', 'end_date']))
            <a href="{{ route('inventory.index') }}" class="btn" style="background-color: var(--bg-input); color: var(--text-main); padding: 10px 16px; border-radius: 8px; border: 1px solid var(--border-color); font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                Reset
            </a>
        @endif
    </form>

    <div style="display: flex; gap: 10px;">
        <!-- Bulk Delete Button -->
        <button id="btnBulkDelete" class="btn btn-danger" style="display: none; background-color: var(--outofstock); color: #fff; border: none; border-radius: 8px; padding: 10px 16px; font-weight: 600; cursor: pointer; align-items: center; gap: 6px;">
            <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
            Delete Selected (<span id="bulkDeleteCount">0</span>)
        </button>

        <!-- Add Product Trigger -->
        <button id="btnOpenAddModal" class="btn" style="background-color: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
            Tambah Barang
        </button>
    </div>
</div>

<!-- Database Inventory Table Card -->
<div class="card" style="background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px;">
    <div style="overflow-x: auto; width: 100%;">
        <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background-color: var(--bg-input);">
                    <th style="padding: 14px 16px; width: 40px;"><input type="checkbox" id="selectAllCheckbox" style="cursor: pointer;"></th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">NAMA BARANG</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">KATEGORI</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">ID BARANG</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">STOK</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">TANGGAL MASUK</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">TANGGAL KELUAR</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">STATUS</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted); width: 80px;">AKSI</th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                @forelse($barang as $item)
                    @php
                        $qty = $item->jumlah_barang;
                        if ($qty > 100) {
                            $badgeClass = 'badge-instock';
                            $badgeLabel = 'In stock';
                        } elseif ($qty > 0) {
                            $badgeClass = 'badge-lowstock';
                            $badgeLabel = 'Low stock';
                        } else {
                            $badgeClass = 'badge-outofstock';
                            $badgeLabel = 'Out of stock';
                        }

                        $catIcon = 'package';
                        if ($item->jenis_barang === 'Electronics') $catIcon = 'cpu';
                        elseif ($item->jenis_barang === 'Apparel') $catIcon = 'shirt';
                        elseif ($item->jenis_barang === 'Wellness') $catIcon = 'sparkles';
                        elseif ($item->jenis_barang === 'Home & Living') $catIcon = 'lamp';
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color); transition: var(--transition);">
                        <td style="padding: 14px 16px;"><input type="checkbox" class="item-checkbox" data-id="{{ $item->id_barang }}" style="cursor: pointer;"></td>
                        <td style="padding: 14px 16px;">
                            <div class="product-cell" style="display: flex; align-items: center; gap: 10px;">
                                <div class="product-img-box" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background-color: var(--bg-input); border-radius: 6px; color: var(--text-muted);">
                                    <i data-lucide="{{ $catIcon }}" style="width: 16px; height: 16px;"></i>
                                </div>
                                <span style="font-weight: 600; color: var(--text-main);">{{ $item->nama_barang }}</span>
                            </div>
                        </td>
                        <td style="padding: 14px 16px; color: var(--text-muted);">{{ $item->jenis_barang }}</td>
                        <td style="padding: 14px 16px;"><code style="color: var(--primary); font-weight: 600; font-size: 0.8rem;">{{ $item->id_barang }}</code></td>
                        <td style="padding: 14px 16px; font-weight: 700; color: var(--text-main);">{{ $item->jumlah_barang }}</td>
                        <td style="padding: 14px 16px; color: var(--text-muted);">{{ $item->tanggal_masuk }}</td>
                        <td style="padding: 14px 16px; color: var(--text-muted);">
                            @if($item->tanggal_keluar)
                                {{ $item->tanggal_keluar }}
                            @else
                                <span style="color: var(--text-dimmed); font-style: italic; font-size: 0.8rem;">(Belum keluar)</span>
                            @endif
                        </td>
                        <td style="padding: 14px 16px;">
                            <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                        </td>
                        <td style="padding: 14px 16px; position: relative;">
                            <div class="action-dropdown-container">
                                <button class="btn-action action-menu-btn" data-id="{{ $item->id_barang }}" style="background: none; border: none; padding: 6px; cursor: pointer; color: var(--text-muted); border-radius: 4px;">
                                    <i data-lucide="more-horizontal" style="width: 16px; height: 16px;"></i>
                                </button>
                                <div class="action-dropdown-menu" id="dropdown-{{ $item->id_barang }}" style="display: none; position: absolute; right: 16px; top: 40px; background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-md); z-index: 100; min-width: 140px; padding: 6px 0;">
                                    <button class="dropdown-item edit-btn" data-id="{{ $item->id_barang }}" style="width: 100%; text-align: left; padding: 8px 14px; background: none; border: none; font-size: 0.8rem; color: var(--text-main); cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                        <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit Barang
                                    </button>
                                    <button class="dropdown-item delete-btn" data-id="{{ $item->id_barang }}" style="width: 100%; text-align: left; padding: 8px 14px; background: none; border: none; font-size: 0.8rem; color: var(--outofstock); cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Hapus Record
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-dimmed); padding: 40px 0; font-style: italic;">Tidak ada record data inventory yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border-top: 1px solid var(--border-color); background-color: var(--bg-input); flex-wrap: wrap; gap: 10px;">
        <span style="font-size: 0.8rem; color: var(--text-muted);">
            Menampilkan <strong style="color: var(--text-main);">{{ $barang->firstItem() ?: 0 }}</strong> - <strong style="color: var(--text-main);">{{ $barang->lastItem() ?: 0 }}</strong> dari <strong style="color: var(--text-main);">{{ $barang->total() }}</strong> total record
        </span>
        
        <div style="display: flex; gap: 5px;">
            {{ $barang->links('pagination::simple-bootstrap-5') }}
        </div>
    </div>
</div>

<!-- ================= MODAL PRODUCT DIALOG (ADD & EDIT) ================= -->
<div class="modal-backdrop" id="productModal">
    <div class="modal-container modal-content" style="max-width: 500px; padding: 30px; border-radius: 16px; background-color: var(--bg-main); border: 1px solid var(--border-color); box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="modalTitle" style="font-size: 1.25rem; font-weight: 800; color: var(--text-main);">Add Product</h3>
            <button id="btnCloseProductModal" style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <!-- Validation errors callout -->
        <div id="dbValidationErrorCallout" class="error-callout" style="display: none; background-color: var(--outofstock-bg); border: 1px solid var(--outofstock-border); border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; color: var(--outofstock); font-size: 0.8rem; align-items: flex-start; gap: 8px;">
            <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;"></i>
            <ul id="errorList" style="margin: 0; padding-left: 18px;"></ul>
        </div>

        <form id="productForm">
            <input type="hidden" id="formMode" value="ADD">
            <input type="hidden" id="originalIdBarang" value="">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">KATEGORI BARANG</label>
                <select id="jenis_barang" class="login-input" style="padding: 10px 12px 10px 12px;" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Apparel">Apparel</option>
                    <option value="Wellness">Wellness</option>
                    <option value="Home & Living">Home & Living</option>
                    <option value="Others">Others</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">ID BARANG (PRIMARY KEY)</label>
                <input type="text" id="id_barang" class="login-input" style="padding: 10px 12px 10px 12px; font-family: var(--font-mono); font-weight: 600;" placeholder="Pilih Kategori untuk auto-generate..." required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">NAMA BARANG</label>
                <input type="text" id="nama_barang" class="login-input" style="padding: 10px 12px 10px 12px;" placeholder="Masukkan nama barang..." required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">JUMLAH STOK (INTEGER >= 0)</label>
                <input type="number" id="jumlah_barang" class="login-input" style="padding: 10px 12px 10px 12px;" placeholder="0" min="0" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
                <div class="form-group">
                    <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">TANGGAL MASUK</label>
                    <input type="date" id="tanggal_masuk" class="login-input" style="padding: 10px 12px 10px 12px;" required>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">TANGGAL KELUAR</label>
                    <input type="date" id="tanggal_keluar" class="login-input" style="padding: 10px 12px 10px 12px;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" id="btnCancelProductModal" class="btn" style="background-color: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-color); padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn" style="background-color: var(--primary); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer;">Save Product</button>
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

    // Checkbox toggle logic
    const selectAllCb = document.getElementById('selectAllCheckbox');
    const itemCbs = document.querySelectorAll('.item-checkbox');
    const btnBulk = document.getElementById('btnBulkDelete');
    const bulkCountSpan = document.getElementById('bulkDeleteCount');

    function updateBulkDelete() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        const count = checked.length;
        if (count > 0) {
            bulkCountSpan.textContent = count;
            btnBulk.style.display = 'inline-flex';
        } else {
            btnBulk.style.display = 'none';
        }
    }

    selectAllCb.addEventListener('change', () => {
        itemCbs.forEach(cb => cb.checked = selectAllCb.checked);
        updateBulkDelete();
    });

    itemCbs.forEach(cb => {
        cb.addEventListener('change', updateBulkDelete);
    });

    // Bulk delete action handler
    btnBulk.addEventListener('click', async () => {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.getAttribute('data-id'));
        if (ids.length === 0) return;

        if (!confirm(`Apakah Anda yakin ingin menghapus ${ids.length} record barang terpilih?`)) {
            return;
        }

        try {
            const res = await fetch('{{ route("inventory.bulk-delete") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ ids })
            });

            const result = await res.json();
            if (res.ok && result.success) {
                window.location.reload();
            } else {
                alert(result.errors ? result.errors.join('\n') : 'Gagal melakukan hapus massal.');
            }
        } catch (err) {
            console.error(err);
            alert('Koneksi gagal ke server.');
        }
    });

    // Individual delete action
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const id = btn.getAttribute('data-id');
            if (!confirm(`Apakah Anda yakin ingin menghapus barang dengan ID: ${id}?`)) {
                return;
            }

            try {
                const res = await fetch(`/inventory/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const result = await res.json();
                if (res.ok && result.success) {
                    window.location.reload();
                } else {
                    alert(result.errors ? result.errors.join('\n') : 'Gagal menghapus barang.');
                }
            } catch (err) {
                console.error(err);
                alert('Koneksi gagal ke server.');
            }
        });
    });

    // Add / Edit Product Modals toggling
    const modal = document.getElementById('productModal');
    const closeBtn = document.getElementById('btnCloseProductModal');
    const cancelBtn = document.getElementById('btnCancelProductModal');
    const productForm = document.getElementById('productForm');
    const jenisBarangSelect = document.getElementById('jenis_barang');
    const idBarangInput = document.getElementById('id_barang');
    const errorCallout = document.getElementById('dbValidationErrorCallout');
    const errorList = document.getElementById('errorList');

    function openModal(mode, data = null) {
        document.getElementById('formMode').value = mode;
        errorCallout.style.display = 'none';

        if (mode === 'ADD') {
            document.getElementById('modalTitle').textContent = 'Tambah Barang Baru';
            document.getElementById('originalIdBarang').value = '';
            productForm.reset();
            
            // Set default date to today
            document.getElementById('tanggal_masuk').value = new Date().toISOString().split('T')[0];
        } else if (mode === 'EDIT' && data) {
            document.getElementById('modalTitle').textContent = 'Edit Data Barang';
            document.getElementById('originalIdBarang').value = data.id_barang;
            
            document.getElementById('jenis_barang').value = data.jenis_barang;
            document.getElementById('id_barang').value = data.id_barang;
            document.getElementById('nama_barang').value = data.nama_barang;
            document.getElementById('jumlah_barang').value = data.jumlah_barang;
            document.getElementById('tanggal_masuk').value = data.tanggal_masuk;
            document.getElementById('tanggal_keluar').value = data.tanggal_keluar || '';
        }
        
        modal.classList.add('open');
    }

    function closeModal() {
        modal.classList.remove('open');
    }

    document.getElementById('btnOpenAddModal').addEventListener('click', () => openModal('ADD'));
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Edit button click handlers
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = btn.getAttribute('data-id');
            
            // Find row data
            const tr = btn.closest('tr');
            const namaBarang = tr.querySelector('.product-cell span').textContent;
            const jenisBarang = tr.cells[2].textContent.trim();
            const idBarang = tr.cells[3].textContent.trim();
            const jumlahBarang = tr.cells[4].textContent.trim();
            const tanggalMasuk = tr.cells[5].textContent.trim();
            let tanggalKeluar = tr.cells[6].textContent.trim();
            if (tanggalKeluar.includes('(Belum keluar)')) {
                tanggalKeluar = '';
            }

            openModal('EDIT', {
                id_barang: idBarang,
                nama_barang: namaBarang,
                jenis_barang: jenisBarang,
                jumlah_barang: jumlahBarang,
                tanggal_masuk: tanggalMasuk,
                tanggal_keluar: tanggalKeluar
            });
        });
    });

    // Auto-generate Product ID when category changes
    jenisBarangSelect.addEventListener('change', async () => {
        const val = jenisBarangSelect.value;
        const mode = document.getElementById('formMode').value;
        
        // Only auto-generate in ADD mode
        if (mode !== 'ADD' || !val) {
            if (!val) idBarangInput.value = '';
            return;
        }

        try {
            const res = await fetch(`/api/generate-id/${val}`);
            const result = await res.json();
            if (res.ok && result.success) {
                idBarangInput.value = result.nextId;
            } else {
                idBarangInput.value = '';
            }
        } catch (err) {
            console.error(err);
            idBarangInput.value = '';
        }
    });

    // Form submission via AJAX
    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const mode = document.getElementById('formMode').value;
        const originalId = document.getElementById('originalIdBarang').value;
        
        const payload = {
            id_barang: idBarangInput.value,
            nama_barang: document.getElementById('nama_barang').value,
            jenis_barang: jenisBarangSelect.value,
            jumlah_barang: document.getElementById('jumlah_barang').value,
            tanggal_masuk: document.getElementById('tanggal_masuk').value,
            tanggal_keluar: document.getElementById('tanggal_keluar').value || null
        };

        const url = mode === 'ADD' ? '/inventory' : `/inventory/${originalId}`;
        const method = mode === 'ADD' ? 'POST' : 'PUT';

        try {
            const res = await fetch(url, {
                method: method,
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
                // Show errors
                errorList.innerHTML = '';
                const errors = result.errors || ['Format data tidak valid.'];
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
