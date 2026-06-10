// STATE APLIKASI
let state = {
  barang: [],
  orders: [],
  sessions: [],
  users: [],
  schemas: {},
  
  // Akun Aktif & Sesi
  activeUsername: "himmad_admin",
  activeRole: "Admin",
  activeSession: 5001,
  activeTimestamp: "08:30:15",
  
  // Tab/Halaman Aktif (Default ke Home untuk menampilkan Rangkuman)
  activeTab: "home", 
  
  // Filtering & Pagination Inventory
  searchTerm: "",
  filterCategory: "",
  filterStatus: "",
  filterStartDate: "",
  filterEndDate: "",
  currentPage: 1,
  pageSize: 10,

  // Filtering & Pagination Orders
  searchOrderQuery: "",
  filterOrderStatus: "",
  currentPageOrder: 1,
  pageSizeOrder: 10
};

// Map Harga Kategori untuk Hitung Aset / Revenue
const CATEGORY_PRICE_MAP = {
  "Electronics": 4350,
  "Apparel": 1200,
  "Wellness": 800,
  "Home & Living": 1500,
  "Others": 500
};

// INITIALIZE APP
document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();

  // Load status sesi dari localStorage jika ada
  if (localStorage.getItem('activeUsername')) {
    state.activeUsername = localStorage.getItem('activeUsername');
    state.activeRole = localStorage.getItem('activeRole') || "Admin";
    state.activeSession = parseInt(localStorage.getItem('activeSession')) || 5001;
    state.activeTimestamp = localStorage.getItem('activeTimestamp') || "08:30:15";
  }

  updateSessionUI();
  fetchData();
  setupEventListeners();
});

// GET REAL TIME TIME STAMP (HH:MM:SS)
function getFormattedTime() {
  const now = new Date();
  const pad = (num) => String(num).padStart(2, '0');
  return `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
}

// FETCH DATA DARI SERVER API
async function fetchData() {
  try {
    const res = await fetch('/api/db-inspect');
    if (!res.ok) throw new Error("Gagal mengambil data dari server");
    
    const data = await res.json();
    state.barang = data.tables.barang;
    state.orders = data.tables.orders || [];
    state.sessions = data.tables.sessions;
    state.users = data.tables.users || [];
    state.schemas = data.schemas;

    // Sinkronisasi Sesi Terakhir dari server jika tidak ada di local storage
    if (!localStorage.getItem('activeUsername') && state.sessions.length > 0) {
      const lastSession = state.sessions[state.sessions.length - 1];
      
      // Cari username dari role yang sesuai
      const matchingUser = state.users.find(u => u.role === lastSession.role);
      state.activeUsername = matchingUser ? matchingUser.username : "himmad_admin";
      state.activeRole = lastSession.role;
      state.activeSession = lastSession.session;
      state.activeTimestamp = lastSession.timestamp;
      updateSessionUI();
    }

    renderApp();
  } catch (err) {
    console.error(err);
    alert("Koneksi gagal ke backend database. Pastikan server Node berjalan.");
  }
}

// UPDATE USER SESSION UI (SIDEBAR FOOTER & AKSES ADMIN LINK)
function updateSessionUI() {
  document.getElementById('activeRoleName').textContent = `@${state.activeUsername}`;
  document.getElementById('activeSessionId').textContent = `Role: ${state.activeRole}`;
  
  const avatar = document.getElementById('activeAvatar');
  avatar.textContent = state.activeUsername.charAt(0).toUpperCase();
  
  // Tampilkan/Sembunyikan menu registrasi user khusus Admin
  const adminUsersLi = document.getElementById('liAdminUsers');
  if (state.activeRole === "Admin") {
    adminUsersLi.style.display = 'block';
  } else {
    adminUsersLi.style.display = 'none';
  }
  
  // Simpan ke localStorage
  localStorage.setItem('activeUsername', state.activeUsername);
  localStorage.setItem('activeRole', state.activeRole);
  localStorage.setItem('activeSession', state.activeSession);
  localStorage.setItem('activeTimestamp', state.activeTimestamp);
}

// RENDER UTAMA APLIKASI
function renderApp() {
  // Toggle Containers
  document.getElementById('view-home').style.display = state.activeTab === "home" ? 'block' : 'none';
  document.getElementById('view-inventory').style.display = state.activeTab === "inventory" ? 'block' : 'none';
  document.getElementById('view-orders').style.display = state.activeTab === "orders" ? 'block' : 'none';

  if (state.activeTab === "home") {
    renderHomeView();
  } else if (state.activeTab === "inventory") {
    renderMetrics();
    renderTable();
  } else if (state.activeTab === "orders") {
    renderOrdersMetrics();
    renderOrdersTable();
  }
}

// ================= RENDER VIEW 0: HOME SUMMARY (BARU) =================
function renderHomeView() {
  // 1. Rangkuman Jumlah Barang per Kategori
  const categoriesGrid = document.getElementById('homeCategoriesGrid');
  categoriesGrid.innerHTML = '';

  const categories = ["Electronics", "Apparel", "Wellness", "Home & Living", "Others"];
  
  categories.forEach(cat => {
    // Cari barang pada kategori ini
    const catItems = state.barang.filter(b => b.jenis_barang === cat);
    const uniqueCount = catItems.length;
    const totalQty = catItems.reduce((acc, curr) => acc + (parseInt(curr.jumlah_barang) || 0), 0);
    
    let catIcon = 'package';
    if (cat === 'Electronics') catIcon = 'cpu';
    else if (cat === 'Apparel') catIcon = 'shirt';
    else if (cat === 'Wellness') catIcon = 'sparkles';
    else if (cat === 'Home & Living') catIcon = 'lamp';

    const card = document.createElement('div');
    card.className = 'metric-card';
    card.style.padding = '18px';
    card.style.borderTop = '3px solid var(--primary)';
    card.innerHTML = `
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div class="product-img-box" style="width:32px; height:32px;">
          <i data-lucide="${catIcon}" style="width:16px; height:16px;"></i>
        </div>
        <strong style="color:#fff; font-size:0.9rem;">${cat}</strong>
      </div>
      <div style="display:flex; flex-direction:column; gap:4px;">
        <span style="font-size:0.75rem; color:var(--text-muted);">Jenis Item Unik: <strong style="color:#fff;">${uniqueCount}</strong></span>
        <span style="font-size:0.75rem; color:var(--text-muted);">Total Stok Unit: <strong style="color:#fff;">${totalQty} Unit</strong></span>
      </div>
    `;
    categoriesGrid.appendChild(card);
  });

  // 2. Ringkasan Status Order
  const ordersStats = document.getElementById('homeOrdersStats');
  ordersStats.innerHTML = '';

  let completed = 0, processed = 0, pending = 0, cancelled = 0;
  state.orders.forEach(o => {
    if (o.status_order === "Completed") completed++;
    else if (o.status_order === "Processed") processed++;
    else if (o.status_order === "Pending") pending++;
    else if (o.status_order === "Cancelled") cancelled++;
  });

  const statusRows = [
    { label: "Completed", count: completed, color: "#10b981" },
    { label: "Processed", count: processed, color: "#3b82f6" },
    { label: "Pending", count: pending, color: "#f59e0b" },
    { label: "Cancelled", count: cancelled, color: "#ef4444" }
  ];

  statusRows.forEach(row => {
    ordersStats.innerHTML += `
      <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; border-bottom:1px solid var(--border-color); padding-bottom:8px;">
        <span style="display:flex; align-items:center; gap:8px;">
          <span class="dot" style="background-color:${row.color}; width:10px; height:10px;"></span>
          <span style="color:var(--text-muted);">${row.label}</span>
        </span>
        <strong style="color:#fff; font-size:0.95rem;">${row.count} Orders</strong>
      </div>
    `;
  });

  // 3. Orders Terbaru (3 Item)
  const recentList = document.getElementById('homeRecentOrdersList');
  recentList.innerHTML = '';

  // Urutkan order berdasarkan ID secara descending (order terbaru di atas)
  const sortedOrders = [...state.orders].sort((a, b) => b.id_order.localeCompare(a.id_order));
  const recentOrders = sortedOrders.slice(0, 3);

  if (recentOrders.length === 0) {
    recentList.innerHTML = '<li style="color:var(--text-dimmed); font-style:italic; font-size:0.8rem; padding: 10px 0;">Belum ada riwayat pesanan.</li>';
  } else {
    recentOrders.forEach(o => {
      const item = state.barang.find(b => b.id_barang === o.id_barang);
      const namaBarang = item ? item.nama_barang : "Product";
      
      let badgeColor = '#f59e0b';
      if (o.status_order === 'Completed') badgeColor = '#10b981';
      else if (o.status_order === 'Processed') badgeColor = '#3b82f6';
      else if (o.status_order === 'Cancelled') badgeColor = '#ef4444';

      const li = document.createElement('li');
      li.className = 'user-list-item';
      li.style.padding = '10px';
      li.innerHTML = `
        <div style="display:flex; flex-direction:column; gap:2px;">
          <span style="font-weight:700; color:#fff; font-size:0.8rem;">${o.id_order} - ${o.nama_pelanggan}</span>
          <span style="font-size:0.75rem; color:var(--text-muted);">${namaBarang} (${o.jumlah_order} Unit)</span>
        </div>
        <span class="badge" style="background-color:${badgeColor}20; color:${badgeColor}; border:1px solid ${badgeColor}40; padding:3px 8px; font-size:0.65rem; border-radius:4px;">${o.status_order}</span>
      `;
      recentList.appendChild(li);
    });
  }

  lucide.createIcons();
}

// ================= RENDER VIEW 1: INVENTORY =================

// RENDER CARDS METRIK & PROGRESS BAR
function renderMetrics() {
  const items = state.barang;
  
  let totalQty = 0;
  let inStockCount = 0;
  let lowStockCount = 0;
  let outOfStockCount = 0;
  
  items.forEach(item => {
    const qty = parseInt(item.jumlah_barang) || 0;
    totalQty += qty;
    
    if (qty > 100) {
      inStockCount++;
    } else if (qty > 0) {
      lowStockCount++;
    } else {
      outOfStockCount++;
    }
  });

  // Kalkulasi total aset
  let assetValue = 0;
  items.forEach(item => {
    const qty = parseInt(item.jumlah_barang) || 0;
    const itemPrice = CATEGORY_PRICE_MAP[item.jenis_barang] || 500;
    assetValue += qty * itemPrice;
  });
  
  document.getElementById('totalAssetValue').textContent = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0
  }).format(assetValue);

  // visualisasi total product
  document.getElementById('totalProductCount').textContent = `${items.length} Products`;
  
  document.getElementById('lblInStockCount').textContent = inStockCount;
  document.getElementById('lblLowStockCount').textContent = lowStockCount;
  document.getElementById('lblOutOfStockCount').textContent = outOfStockCount;

  const totalCategories = inStockCount + lowStockCount + outOfStockCount || 1;
  const inPct = (inStockCount / totalCategories) * 100;
  const lowPct = (lowStockCount / totalCategories) * 100;
  const outPct = (outOfStockCount / totalCategories) * 100;

  document.getElementById('barInStock').style.width = `${inPct}%`;
  document.getElementById('barLowStock').style.width = `${lowPct}%`;
  document.getElementById('barOutOfStock').style.width = `${outPct}%`;
}

// RENDER INVENTORY TABLE
function renderTable() {
  const tbody = document.getElementById('inventoryTableBody');
  tbody.innerHTML = '';

  let filtered = state.barang.filter(item => {
    const matchSearch = 
      item.nama_barang.toLowerCase().includes(state.searchTerm.toLowerCase()) ||
      item.id_barang.toLowerCase().includes(state.searchTerm.toLowerCase());
    
    const matchCategory = state.filterCategory === "" || item.jenis_barang === state.filterCategory;
    
    const qty = item.jumlah_barang;
    let itemStatus = 'instock';
    if (qty === 0) itemStatus = 'outofstock';
    else if (qty <= 100) itemStatus = 'lowstock';

    const matchStatus = state.filterStatus === "" || itemStatus === state.filterStatus;

    let matchDate = true;
    if (state.filterStartDate) {
      matchDate = matchDate && (Date.parse(item.tanggal_masuk) >= Date.parse(state.filterStartDate));
    }
    if (state.filterEndDate) {
      matchDate = matchDate && (Date.parse(item.tanggal_masuk) <= Date.parse(state.filterEndDate));
    }

    return matchSearch && matchCategory && matchStatus && matchDate;
  });

  document.getElementById('selectAllCheckbox').checked = false;
  updateBulkDeleteButton();

  document.getElementById('totalItemsCount').textContent = filtered.length;

  const totalPages = Math.ceil(filtered.length / state.pageSize) || 1;
  if (state.currentPage > totalPages) {
    state.currentPage = totalPages;
  }

  const startIdx = (state.currentPage - 1) * state.pageSize;
  const endIdx = Math.min(startIdx + state.pageSize, filtered.length);
  const pageItems = filtered.slice(startIdx, endIdx);

  document.getElementById('pageRangeStart').textContent = filtered.length > 0 ? startIdx + 1 : 0;
  document.getElementById('pageRangeEnd').textContent = endIdx;

  if (pageItems.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--text-dimmed); padding: 40px 0;">Tidak ada record data inventory yang cocok.</td></tr>`;
    renderPaginationButtons(totalPages);
    return;
  }

  pageItems.forEach(item => {
    let badgeClass = 'badge-instock';
    let badgeLabel = 'In stock';
    if (item.jumlah_barang === 0) {
      badgeClass = 'badge-outofstock';
      badgeLabel = 'Out of stock';
    } else if (item.jumlah_barang <= 100) {
      badgeClass = 'badge-lowstock';
      badgeLabel = 'Low stock';
    }

    let catIcon = 'package';
    if (item.jenis_barang === 'Electronics') catIcon = 'cpu';
    else if (item.jenis_barang === 'Apparel') catIcon = 'shirt';
    else if (item.jenis_barang === 'Wellness') catIcon = 'sparkles';
    else if (item.jenis_barang === 'Home & Living') catIcon = 'lamp';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="checkbox" class="item-checkbox" data-id="${item.id_barang}"></td>
      <td>
        <div class="product-cell">
          <div class="product-img-box">
            <i data-lucide="${catIcon}"></i>
          </div>
          <span>${item.nama_barang}</span>
        </div>
      </td>
      <td>${item.jenis_barang}</td>
      <td><code style="color: #c084fc;">${item.id_barang}</code></td>
      <td><strong>${item.jumlah_barang}</strong></td>
      <td>${item.tanggal_masuk}</td>
      <td>${item.tanggal_keluar || '<span style="color: var(--text-dimmed); font-style: italic;">(Belum keluar)</span>'}</td>
      <td><span class="badge ${badgeClass}">${badgeLabel}</span></td>
      <td>
        <div class="action-dropdown-container">
          <button class="btn-action action-menu-btn" data-id="${item.id_barang}">
            <i data-lucide="more-horizontal"></i>
          </button>
          <div class="action-dropdown-menu" id="dropdown-${item.id_barang}">
            <button class="dropdown-item edit-btn" data-id="${item.id_barang}">
              <i data-lucide="edit-3"></i> Edit Record
            </button>
            <button class="dropdown-item delete-btn delete" data-id="${item.id_barang}">
              <i data-lucide="trash-2"></i> Delete Record
            </button>
          </div>
        </div>
      </td>
    `;
    tbody.appendChild(tr);
  });

  lucide.createIcons();
  
  // Binding dropdown menus
  document.querySelectorAll('.action-menu-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = e.currentTarget.getAttribute('data-id');
      toggleActionDropdown(id);
    });
  });

  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = e.currentTarget.getAttribute('data-id');
      closeAllDropdowns();
      openEditModal(id);
    });
  });

  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = e.currentTarget.getAttribute('data-id');
      closeAllDropdowns();
      deleteProduct(id);
    });
  });

  document.querySelectorAll('.item-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkDeleteButton);
  });

  renderPaginationButtons(totalPages);
}

// RENDER TOMBOL PAGINASI INVENTORY
function renderPaginationButtons(totalPages) {
  const container = document.getElementById('pageNumbersContainer');
  container.innerHTML = '';

  document.getElementById('btnPrevPage').disabled = state.currentPage === 1;
  document.getElementById('btnNextPage').disabled = state.currentPage === totalPages;

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement('div');
    btn.className = `page-num ${state.currentPage === i ? 'active' : ''}`;
    btn.textContent = i;
    btn.addEventListener('click', () => {
      state.currentPage = i;
      renderTable();
    });
    container.appendChild(btn);
  }
}

// ================= RENDER VIEW 2: ORDERS =================

// CALCULATE METRICS & RENDER CARDS ORDER
function renderOrdersMetrics() {
  const orders = state.orders;
  
  let totalRevenue = 0;
  let completedCount = 0;
  let processedCount = 0;
  let pendingCount = 0;
  let cancelledCount = 0;
  
  orders.forEach(o => {
    const item = state.barang.find(b => b.id_barang === o.id_barang);
    const itemPrice = item ? (CATEGORY_PRICE_MAP[item.jenis_barang] || 500) : 500;
    
    if (o.status_order !== "Cancelled") {
      totalRevenue += parseInt(o.jumlah_order) * itemPrice;
    }

    if (o.status_order === "Completed") completedCount++;
    else if (o.status_order === "Processed") processedCount++;
    else if (o.status_order === "Pending") pendingCount++;
    else if (o.status_order === "Cancelled") cancelledCount++;
  });

  document.getElementById('totalOrderRevenue').textContent = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0
  }).format(totalRevenue);

  document.getElementById('totalOrdersCount').textContent = `${orders.filter(o => o.status_order !== "Cancelled").length} Active Orders`;
  
  document.getElementById('lblOrderCompletedCount').textContent = completedCount;
  document.getElementById('lblOrderProcessedCount').textContent = processedCount;
  document.getElementById('lblOrderPendingCount').textContent = pendingCount;
  document.getElementById('lblOrderCancelledCount').textContent = cancelledCount;

  const total = completedCount + processedCount + pendingCount + cancelledCount || 1;
  document.getElementById('barOrderCompleted').style.width = `${(completedCount / total) * 100}%`;
  document.getElementById('barOrderProcessed').style.width = `${(processedCount / total) * 100}%`;
  document.getElementById('barOrderPending').style.width = `${(pendingCount / total) * 100}%`;
  document.getElementById('barOrderCancelled').style.width = `${(cancelledCount / total) * 100}%`;
}

// RENDER ORDERS TABLE
function renderOrdersTable() {
  const tbody = document.getElementById('ordersTableBody');
  tbody.innerHTML = '';

  let filtered = state.orders.filter(o => {
    const matchSearch = 
      o.nama_pelanggan.toLowerCase().includes(state.searchOrderQuery.toLowerCase()) ||
      o.id_order.toLowerCase().includes(state.searchOrderQuery.toLowerCase()) ||
      o.id_barang.toLowerCase().includes(state.searchOrderQuery.toLowerCase());
    
    const matchStatus = state.filterOrderStatus === "" || o.status_order === state.filterOrderStatus;

    return matchSearch && matchStatus;
  });

  document.getElementById('totalOrdersItemsCount').textContent = filtered.length;

  const totalPages = Math.ceil(filtered.length / state.pageSizeOrder) || 1;
  if (state.currentPageOrder > totalPages) {
    state.currentPageOrder = totalPages;
  }

  const startIdx = (state.currentPageOrder - 1) * state.pageSizeOrder;
  const endIdx = Math.min(startIdx + state.pageSizeOrder, filtered.length);
  const pageItems = filtered.slice(startIdx, endIdx);

  document.getElementById('pageOrderRangeStart').textContent = filtered.length > 0 ? startIdx + 1 : 0;
  document.getElementById('pageOrderRangeEnd').textContent = endIdx;

  if (pageItems.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--text-dimmed); padding: 40px 0;">Tidak ada data order yang cocok.</td></tr>`;
    renderPaginationOrderButtons(totalPages);
    return;
  }

  pageItems.forEach(o => {
    const barangItem = state.barang.find(b => b.id_barang === o.id_barang);
    const namaBarang = barangItem ? barangItem.nama_barang : '<span style="color: var(--text-dimmed);">Deleted Product</span>';
    
    let badgeClass = 'badge-lowstock'; 
    if (o.status_order === 'Completed') badgeClass = 'badge-instock';
    else if (o.status_order === 'Processed') badgeClass = 'badge-info';
    else if (o.status_order === 'Cancelled') badgeClass = 'badge-outofstock';

    const isCancelled = o.status_order === 'Cancelled';
    const isCompleted = o.status_order === 'Completed';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="checkbox" disabled></td>
      <td><code style="color: #a78bfa; font-weight: 700;">${o.id_order}</code></td>
      <td><strong>${o.nama_pelanggan}</strong></td>
      <td><code>${o.id_barang}</code></td>
      <td>${namaBarang}</td>
      <td><strong>${o.jumlah_order}</strong></td>
      <td>${o.tanggal_order}</td>
      <td><span class="badge ${badgeClass}">${o.status_order}</span></td>
      <td>
        <div class="action-dropdown-container">
          <button class="btn-action action-menu-btn" data-id="${o.id_order}">
            <i data-lucide="more-horizontal"></i>
          </button>
          <div class="action-dropdown-menu" id="dropdown-${o.id_order}">
            <button class="dropdown-item process-order-btn" data-id="${o.id_order}" ${isCancelled || isCompleted ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''}>
              <i data-lucide="play"></i> Process Order
            </button>
            <button class="dropdown-item complete-order-btn" data-id="${o.id_order}" ${isCancelled || isCompleted ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''}>
              <i data-lucide="check-circle-2"></i> Complete Order
            </button>
            <button class="dropdown-item delete-btn cancel-order-btn" data-id="${o.id_order}" ${isCancelled ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''}>
              <i data-lucide="x-circle"></i> Cancel & Restore
            </button>
          </div>
        </div>
      </td>
    `;
    tbody.appendChild(tr);
  });

  lucide.createIcons();
  
  // Binding dropdown order
  document.querySelectorAll('.action-menu-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = e.currentTarget.getAttribute('data-id');
      toggleActionDropdown(id);
    });
  });

  document.querySelectorAll('.process-order-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = e.currentTarget.getAttribute('data-id');
      closeAllDropdowns();
      updateOrderStatus(id, "Processed");
    });
  });

  document.querySelectorAll('.complete-order-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = e.currentTarget.getAttribute('data-id');
      closeAllDropdowns();
      updateOrderStatus(id, "Completed");
    });
  });

  document.querySelectorAll('.cancel-order-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = e.currentTarget.getAttribute('data-id');
      closeAllDropdowns();
      cancelOrder(id);
    });
  });

  renderPaginationOrderButtons(totalPages);
}

// RENDER TOMBOL PAGINASI ORDERS
function renderPaginationOrderButtons(totalPages) {
  const container = document.getElementById('pageOrderNumbersContainer');
  container.innerHTML = '';

  document.getElementById('btnPrevOrderPage').disabled = state.currentPageOrder === 1;
  document.getElementById('btnNextOrderPage').disabled = state.currentPageOrder === totalPages;

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement('div');
    btn.className = `page-num ${state.currentPageOrder === i ? 'active' : ''}`;
    btn.textContent = i;
    btn.addEventListener('click', () => {
      state.currentPageOrder = i;
      renderOrdersTable();
    });
    container.appendChild(btn);
  }
}

// UPDATE ORDER STATUS API
async function updateOrderStatus(orderId, status) {
  try {
    const res = await fetch(`/api/orders/${orderId}/status`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status_order: status })
    });
    const result = await res.json();
    if (!res.ok) {
      alert(result.errors ? result.errors.join('\n') : "Gagal mengupdate order.");
    } else {
      fetchData();
    }
  } catch (err) {
    console.error(err);
    alert("Gagal melakukan update status order.");
  }
}

// CANCEL ORDER & RESTORE STOCK API
async function cancelOrder(orderId) {
  if (!confirm(`Apakah Anda yakin ingin membatalkan order #${orderId}? Stok barang terkait akan dikembalikan.`)) {
    return;
  }
  try {
    const res = await fetch(`/api/orders/${orderId}/cancel`, {
      method: 'POST'
    });
    const result = await res.json();
    if (!res.ok) {
      alert(result.errors ? result.errors.join('\n') : "Gagal membatalkan order.");
    } else {
      alert(result.message);
      fetchData();
    }
  } catch (err) {
    console.error(err);
    alert("Gagal membatalkan order ke server.");
  }
}

// ================= ACTION DROPDOWN MENUS LOGIC =================
function toggleActionDropdown(id) {
  const currentDropdown = document.getElementById(`dropdown-${id}`);
  if (!currentDropdown) return;
  const isShow = currentDropdown.classList.contains('show');
  closeAllDropdowns();
  if (!isShow) {
    currentDropdown.classList.add('show');
  }
}

function closeAllDropdowns() {
  document.querySelectorAll('.action-dropdown-menu').forEach(menu => {
    menu.classList.remove('show');
  });
}

// BULK ACTIONS BUTTON (DELETE SELECTED)
function updateBulkDeleteButton() {
  const checkboxes = document.querySelectorAll('.item-checkbox:checked');
  const count = checkboxes.length;
  
  const btnBulk = document.getElementById('btnBulkDelete');
  const countSpan = document.getElementById('bulkDeleteCount');
  
  if (count > 0 && state.activeTab === "inventory") {
    countSpan.textContent = count;
    btnBulk.style.display = 'inline-flex';
  } else {
    btnBulk.style.display = 'none';
  }
}

// ================= EVENT LISTENERS BINDING =================
function setupEventListeners() {
  // Navigation tabs switching
  document.getElementById('navHome').addEventListener('click', (e) => {
    e.preventDefault();
    setActiveTab("home");
  });

  document.getElementById('navInventoryMenu').addEventListener('click', (e) => {
    e.preventDefault();
    setActiveTab("inventory");
  });

  document.getElementById('navOrdersMenu').addEventListener('click', (e) => {
    e.preventDefault();
    setActiveTab("orders");
  });

  document.getElementById('navStoreSubmenu').addEventListener('click', (e) => {
    e.preventDefault();
    setActiveTab("inventory");
  });

  // Search & Filter Inventory
  document.getElementById('searchInput').addEventListener('input', (e) => {
    state.searchTerm = e.target.value;
    state.currentPage = 1;
    renderTable();
  });

  document.getElementById('filterCategory').addEventListener('change', (e) => {
    state.filterCategory = e.target.value;
    state.currentPage = 1;
    renderTable();
  });

  document.getElementById('filterStatus').addEventListener('change', (e) => {
    state.filterStatus = e.target.value;
    state.currentPage = 1;
    renderTable();
  });

  // Date Filter Inventory Modal
  document.querySelector('.date-picker-mock').addEventListener('click', () => {
    document.getElementById('filterStartDate').value = state.filterStartDate;
    document.getElementById('filterEndDate').value = state.filterEndDate;
    document.getElementById('dateFilterModal').classList.add('open');
  });

  document.getElementById('btnCloseDateFilterModal').addEventListener('click', () => {
    document.getElementById('dateFilterModal').classList.remove('open');
  });

  document.getElementById('btnResetDateFilter').addEventListener('click', () => {
    state.filterStartDate = "";
    state.filterEndDate = "";
    document.getElementById('dateFilterLabel').textContent = "All Entry Dates";
    document.getElementById('dateFilterModal').classList.remove('open');
    state.currentPage = 1;
    renderTable();
  });

  document.getElementById('dateFilterForm').addEventListener('submit', (e) => {
    e.preventDefault();
    state.filterStartDate = document.getElementById('filterStartDate').value;
    state.filterEndDate = document.getElementById('filterEndDate').value;
    
    const startLbl = state.filterStartDate ? state.filterStartDate : "*";
    const endLbl = state.filterEndDate ? state.filterEndDate : "*";
    
    if (state.filterStartDate || state.filterEndDate) {
      document.getElementById('dateFilterLabel').textContent = `${startLbl} s/d ${endLbl}`;
    } else {
      document.getElementById('dateFilterLabel').textContent = "All Entry Dates";
    }
    
    document.getElementById('dateFilterModal').classList.remove('open');
    state.currentPage = 1;
    renderTable();
  });

  // Page size select Inventory
  document.getElementById('pageSizeSelect').addEventListener('change', (e) => {
    state.pageSize = parseInt(e.target.value);
    state.currentPage = 1;
    renderTable();
  });

  // Prev / Next Page Inventory
  document.getElementById('btnPrevPage').addEventListener('click', () => {
    if (state.currentPage > 1) {
      state.currentPage--;
      renderTable();
    }
  });

  document.getElementById('btnNextPage').addEventListener('click', () => {
    const totalPages = Math.ceil(state.barang.length / state.pageSize);
    if (state.currentPage < totalPages) {
      state.currentPage++;
      renderTable();
    }
  });

  // Search & Filter Orders
  document.getElementById('searchOrderInput').addEventListener('input', (e) => {
    state.searchOrderQuery = e.target.value;
    state.currentPageOrder = 1;
    renderOrdersTable();
  });

  document.getElementById('filterOrderStatus').addEventListener('change', (e) => {
    state.filterOrderStatus = e.target.value;
    state.currentPageOrder = 1;
    renderOrdersTable();
  });

  document.getElementById('pageSizeOrderSelect').addEventListener('change', (e) => {
    state.pageSizeOrder = parseInt(e.target.value);
    state.currentPageOrder = 1;
    renderOrdersTable();
  });

  document.getElementById('btnPrevOrderPage').addEventListener('click', () => {
    if (state.currentPageOrder > 1) {
      state.currentPageOrder--;
      renderOrdersTable();
    }
  });

  document.getElementById('btnNextOrderPage').addEventListener('click', () => {
    const totalPages = Math.ceil(state.orders.length / state.pageSizeOrder);
    if (state.currentPageOrder < totalPages) {
      state.currentPageOrder++;
      renderOrdersTable();
    }
  });

  // Document click closes dropdowns
  document.addEventListener('click', () => {
    closeAllDropdowns();
  });

  // MODAL PRODUCT: Open Add
  document.getElementById('btnOpenAddModal').addEventListener('click', () => {
    openAddModal();
  });

  document.getElementById('btnCloseProductModal').addEventListener('click', closeProductModal);
  document.getElementById('btnCancelProductModal').addEventListener('click', closeProductModal);
  document.getElementById('productForm').addEventListener('submit', handleProductFormSubmit);

  // AUTO GENERATE ID BARANG SAAT KATEGORI DIPILIH
  document.getElementById('jenis_barang').addEventListener('change', async (e) => {
    const kategori = e.target.value;
    const idInput = document.getElementById('id_barang');
    if (!kategori) {
      idInput.value = "";
      return;
    }

    try {
      const res = await fetch(`/api/generate-id/${kategori}`);
      const result = await res.json();
      if (res.ok && result.success) {
        idInput.value = result.nextId;
      } else {
        idInput.value = "";
        console.error("Gagal generate ID");
      }
    } catch (err) {
      console.error(err);
      idInput.value = "";
    }
  });

  // MODAL ORDER: Open & submit
  document.getElementById('btnOpenAddOrderModal').addEventListener('click', () => {
    openOrderModal();
  });
  document.getElementById('btnCloseOrderModal').addEventListener('click', closeOrderModal);
  document.getElementById('btnCancelOrderModal').addEventListener('click', closeOrderModal);
  document.getElementById('orderForm').addEventListener('submit', handleOrderFormSubmit);

  // MODAL Switch Account: Open & submit
  document.getElementById('activeUserCard').addEventListener('click', () => {
    openRoleModal();
  });
  document.getElementById('btnCloseRoleModal').addEventListener('click', closeRoleModal);
  document.getElementById('btnCancelRoleModal').addEventListener('click', closeRoleModal);
  document.getElementById('roleForm').addEventListener('submit', handleRoleFormSubmit);

  // Switch Account modal user selector dropdown change listener
  document.getElementById('selectUserAccount').addEventListener('change', (e) => {
    const selectedUsername = e.target.value;
    const user = state.users.find(u => u.username === selectedUsername);
    if (user) {
      document.getElementById('displayAccountRole').value = user.role;
    }
  });

  // MODAL ADMIN REGISTER USER: Open
  document.getElementById('navAdminUsers').addEventListener('click', (e) => {
    e.preventDefault();
    if (state.activeRole !== "Admin") {
      alert("Akses ditolak! Hanya role Admin yang dapat meregistrasikan akun baru.");
      return;
    }
    openUserModal();
  });
  document.getElementById('btnCloseUserModal').addEventListener('click', closeUserModal);
  document.getElementById('btnCancelUserModal').addEventListener('click', closeUserModal);
  document.getElementById('registerUserForm').addEventListener('submit', handleRegisterUserSubmit);

  // Select all checkbox Inventory
  document.getElementById('selectAllCheckbox').addEventListener('change', (e) => {
    const isChecked = e.target.checked;
    document.querySelectorAll('.item-checkbox').forEach(cb => {
      cb.checked = isChecked;
    });
    updateBulkDeleteButton();
  });

  document.getElementById('btnBulkDelete').addEventListener('click', handleBulkDelete);

  // Mobile sidebar drawer toggle
  document.getElementById('mobileMenuToggle').addEventListener('click', (e) => {
    e.stopPropagation();
    document.querySelector('.sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
  });

  // Close sidebar drawer on click main content
  document.querySelector('.main-content').addEventListener('click', () => {
    document.querySelector('.sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
  });

  // Close sidebar drawer on click backdrop overlay
  document.getElementById('sidebarOverlay').addEventListener('click', () => {
    document.querySelector('.sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
  });
}

// VIEW SWITCHER TAB MANAGER
function setActiveTab(tab) {
  state.activeTab = tab;
  
  // Close mobile sidebar if open
  document.querySelector('.sidebar').classList.remove('active');
  document.getElementById('sidebarOverlay').classList.remove('active');
  
  // Set link active state di sidebar menu
  document.getElementById('navHome').classList.remove('active');
  document.getElementById('navInventoryMenu').classList.remove('active');
  document.getElementById('navOrdersMenu').classList.remove('active');
  
  if (tab === "home") {
    document.getElementById('navHome').classList.add('active');
  } else if (tab === "inventory") {
    document.getElementById('navInventoryMenu').classList.add('active');
  } else if (tab === "orders") {
    document.getElementById('navOrdersMenu').classList.add('active');
  }

  renderApp();
}

// OPEN PRODUCT MODAL IN ADD MODE
function openAddModal() {
  document.getElementById('modalTitle').textContent = "Add Product";
  document.getElementById('formMode').value = "ADD";
  document.getElementById('originalIdBarang').value = "";
  
  document.getElementById('nama_barang').value = "";
  document.getElementById('id_barang').value = ""; 
  document.getElementById('jumlah_barang').value = "";
  document.getElementById('jenis_barang').value = "";
  
  const todayStr = new Date().toISOString().split('T')[0];
  document.getElementById('tanggal_masuk').value = todayStr;
  document.getElementById('tanggal_keluar').value = "";

  hideValidationError();
  document.getElementById('productModal').classList.add('open');
}

// OPEN PRODUCT MODAL IN EDIT MODE
function openEditModal(idBarang) {
  const item = state.barang.find(b => b.id_barang === idBarang);
  if (!item) return;

  document.getElementById('modalTitle').textContent = "Edit Product";
  document.getElementById('formMode').value = "EDIT";
  document.getElementById('originalIdBarang').value = item.id_barang;
  
  document.getElementById('nama_barang').value = item.nama_barang;
  document.getElementById('id_barang').value = item.id_barang; 
  document.getElementById('jumlah_barang').value = item.jumlah_barang;
  document.getElementById('jenis_barang').value = item.jenis_barang;
  document.getElementById('tanggal_masuk').value = item.tanggal_masuk;
  document.getElementById('tanggal_keluar').value = item.tanggal_keluar || "";

  hideValidationError();
  document.getElementById('productModal').classList.add('open');
}

function closeProductModal() {
  document.getElementById('productModal').classList.remove('open');
}

// SUBMIT PRODUCT FORM (SAVE TO DATABASE)
async function handleProductFormSubmit(e) {
  e.preventDefault();
  
  const mode = document.getElementById('formMode').value;
  const originalId = document.getElementById('originalIdBarang').value;

  const payload = {
    nama_barang: document.getElementById('nama_barang').value,
    id_barang: document.getElementById('id_barang').value,
    jumlah_barang: document.getElementById('jumlah_barang').value,
    jenis_barang: document.getElementById('jenis_barang').value,
    tanggal_masuk: document.getElementById('tanggal_masuk').value,
    tanggal_keluar: document.getElementById('tanggal_keluar').value || ""
  };

  const url = mode === "ADD" ? '/api/barang' : `/api/barang/${originalId}`;
  const method = mode === "ADD" ? 'POST' : 'PUT';

  try {
    const res = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const result = await res.json();

    if (!res.ok) {
      showValidationError(result.errors || ["Terjadi kesalahan skema database."]);
    } else {
      closeProductModal();
      fetchData();
    }
  } catch (err) {
    console.error(err);
    showValidationError(["Gagal mendaftarkan barang baru ke server database."]);
  }
}

// DELETE BARANG RECORD
async function deleteProduct(idBarang) {
  if (!confirm(`Apakah Anda yakin ingin menghapus barang dengan ID: ${idBarang}?`)) {
    return;
  }

  try {
    const res = await fetch(`/api/barang/${idBarang}`, {
      method: 'DELETE'
    });

    if (!res.ok) {
      const err = await res.json();
      alert(err.errors ? err.errors.join('\n') : "Gagal menghapus data.");
    } else {
      fetchData();
    }
  } catch (err) {
    console.error(err);
    alert("Gagal melakukan aksi delete ke server.");
  }
}

// BULK DELETE
async function handleBulkDelete() {
  const checkboxes = document.querySelectorAll('.item-checkbox:checked');
  const ids = Array.from(checkboxes).map(cb => cb.getAttribute('data-id'));
  
  if (ids.length === 0) return;
  if (!confirm(`Apakah Anda yakin ingin menghapus ${ids.length} record barang terpilih?`)) {
    return;
  }

  try {
    const res = await fetch('/api/barang/bulk-delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ids })
    });

    const result = await res.json();
    if (!res.ok) {
      alert(result.errors ? result.errors.join('\n') : "Gagal hapus massal.");
    } else {
      fetchData();
    }
  } catch (err) {
    console.error(err);
    alert("Kesalahan koneksi saat hapus data.");
  }
}

// BULK IMPORT FILE CHANGE HANDLER (NOT TRIGGERED IN HTML ANYMORE BUT CODE REMAINS AS UTILITY)
function handleImportFileChange(e) {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = async function(evt) {
    try {
      let parsed = JSON.parse(evt.target.result);
      if (parsed && !Array.isArray(parsed) && parsed.barang) {
        parsed = parsed.barang;
      }

      if (!Array.isArray(parsed)) {
        throw new Error("Struktur file JSON harus berupa array barang.");
      }

      const res = await fetch('/api/barang/bulk-import', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ items: parsed })
      });

      const result = await res.json();
      if (!res.ok) {
        let errMsg = "Import Gagal! Pelanggaran Skema Database:\n\n";
        (result.errors || ["Unknown validation error"]).forEach(err => {
          errMsg += `• ${err}\n`;
        });
        alert(errMsg);
      } else {
        alert(result.message || "Data berhasil diimpor!");
        fetchData();
      }
    } catch (err) {
      alert(`Gagal parsing berkas import: ${err.message}`);
    }
  };
  reader.readAsText(file);
}

// VALIDATION ERRORS DISPLAY HANDLERS INVENTORY
function showValidationError(errors) {
  const callout = document.getElementById('dbValidationErrorCallout');
  const errorList = document.getElementById('errorList');
  errorList.innerHTML = '';
  errors.forEach(err => {
    const li = document.createElement('li');
    li.innerHTML = `<code>[Constraint Violation]</code> ${err}`;
    errorList.appendChild(li);
  });
  callout.style.display = 'block';
  callout.classList.remove('shake');
  void callout.offsetWidth;
  callout.classList.add('shake');
}

function hideValidationError() {
  document.getElementById('dbValidationErrorCallout').style.display = 'none';
}

// --- LOG ORDERS MODAL LOGIC ---
function openOrderModal() {
  document.getElementById('orderValidationErrorCallout').style.display = 'none';
  
  const productSelect = document.getElementById('order_id_barang');
  productSelect.innerHTML = '<option value="">-- Pilih Barang --</option>';
  
  state.barang.forEach(item => {
    productSelect.innerHTML += `<option value="${item.id_barang}">${item.nama_barang} - ${item.id_barang} - Stok: ${item.jumlah_barang}</option>`;
  });

  document.getElementById('nama_pelanggan').value = "";
  document.getElementById('jumlah_order').value = "";
  document.getElementById('status_order').value = "Pending";
  
  const todayStr = new Date().toISOString().split('T')[0];
  document.getElementById('tanggal_order').value = todayStr;

  document.getElementById('orderModal').classList.add('open');
}

function closeOrderModal() {
  document.getElementById('orderModal').classList.remove('open');
}

// HANDLER SUBMIT TAMBAH ORDER BARU (SAVE TO DATABASE)
async function handleOrderFormSubmit(e) {
  e.preventDefault();

  const payload = {
    nama_pelanggan: document.getElementById('nama_pelanggan').value,
    id_barang: document.getElementById('order_id_barang').value,
    jumlah_order: document.getElementById('jumlah_order').value, 
    tanggal_order: document.getElementById('tanggal_order').value,
    status_order: document.getElementById('status_order').value
  };

  try {
    const res = await fetch('/api/orders', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await res.json();
    if (!res.ok) {
      const callout = document.getElementById('orderValidationErrorCallout');
      const list = document.getElementById('orderErrorList');
      list.innerHTML = '';
      (result.errors || ["Integrity error"]).forEach(err => {
        list.innerHTML += `<li><code>[Order Constraint]</code> ${err}</li>`;
      });
      callout.style.display = 'block';
    } else {
      closeOrderModal();
      fetchData(); 
    }
  } catch (err) {
    console.error(err);
    alert("Gagal mendaftarkan order baru ke server.");
  }
}

// --- Switch Account Modal Logic (MENGGUNAKAN DAFTAR USER DARI SERVER) ---
function openRoleModal() {
  const selectAcc = document.getElementById('selectUserAccount');
  selectAcc.innerHTML = '';
  
  state.users.forEach(u => {
    selectAcc.innerHTML += `<option value="${u.username}">${u.username} - Role: ${u.role}</option>`;
  });

  // Set default ke user aktif saat ini
  selectAcc.value = state.activeUsername;
  
  const activeUser = state.users.find(u => u.username === state.activeUsername);
  document.getElementById('displayAccountRole').value = activeUser ? activeUser.role : "Admin";
  
  document.getElementById('inputSessionId').value = Math.floor(Math.random() * 9000) + 1000;
  document.getElementById('inputTimestamp').value = getFormattedTime();
  document.getElementById('roleValidationErrorCallout').style.display = 'none';
  document.getElementById('roleModal').classList.add('open');
}

function closeRoleModal() {
  document.getElementById('roleModal').classList.remove('open');
}

// SUBMIT GANTI AKUN
async function handleRoleFormSubmit(e) {
  e.preventDefault();

  const selectedUsername = document.getElementById('selectUserAccount').value;
  const userObj = state.users.find(u => u.username === selectedUsername);
  if (!userObj) return;

  const role = userObj.role;
  const inputSess = document.getElementById('inputSessionId').value;
  const timestamp = document.getElementById('inputTimestamp').value;

  const payload = {
    session: inputSess,
    role: role,
    timestamp: timestamp
  };

  try {
    const res = await fetch('/api/sessions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await res.json();

    if (!res.ok) {
      const errCallout = document.getElementById('roleValidationErrorCallout');
      const errList = document.getElementById('roleErrorList');
      errList.innerHTML = '';
      (result.errors || ["Constraint violation"]).forEach(err => {
        errList.innerHTML += `<li>${err}</li>`;
      });
      errCallout.style.display = 'block';
    } else {
      state.activeUsername = selectedUsername;
      state.activeRole = role;
      state.activeSession = parseInt(inputSess);
      state.activeTimestamp = timestamp;

      updateSessionUI();
      closeRoleModal();
      
      // Jika ganti role ke non-admin dan tab aktif adalah 'akun', reset tab ke home
      if (state.activeRole !== "Admin" && state.activeTab === "akun") {
        setActiveTab("home");
      } else {
        fetchData();
      }
    }
  } catch (err) {
    console.error(err);
    alert("Gagal melakukan switch account ke server.");
  }
}

// --- USER MANAGEMENT MODAL ---
function openUserModal() {
  document.getElementById('regUsername').value = "";
  document.getElementById('regRole').value = "Gudang";
  document.getElementById('userValidationErrorCallout').style.display = 'none';
  
  renderRegisteredUsersList();
  document.getElementById('userManagementModal').classList.add('open');
}

function closeUserModal() {
  document.getElementById('userManagementModal').classList.remove('open');
}

function renderRegisteredUsersList() {
  const container = document.getElementById('registeredUsersList');
  container.innerHTML = '';

  if (state.users.length === 0) {
    container.innerHTML = '<li style="color: var(--text-dimmed); font-style: italic; font-size: 0.8rem;">Tidak ada user terdaftar.</li>';
    return;
  }

  state.users.forEach(u => {
    const roleClass = String(u.role).toLowerCase();
    const isSelf = u.username === state.activeUsername;
    container.innerHTML += `
      <li class="user-list-item">
        <span class="username">@${u.username}</span>
        <div style="display:flex; align-items:center; gap:8px;">
          <span class="badge-role ${roleClass}">${u.role}</span>
          ${!isSelf ? `
            <button class="btn-action delete-user-btn" data-username="${u.username}" title="Hapus Akun" style="padding:4px; color:#f87171; background:none; border:none; cursor:pointer;">
              <i data-lucide="trash-2" style="width:14px; height:14px;"></i>
            </button>
          ` : '<span style="font-size:0.65rem; color:var(--text-dimmed); font-style:italic;">(Aktif)</span>'}
        </div>
      </li>
    `;
  });

  // Re-create icons
  lucide.createIcons();

  // Bind click listeners
  document.querySelectorAll('.delete-user-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const username = e.currentTarget.getAttribute('data-username');
      deleteUserAccount(username);
    });
  });
}

// DELETE USER ACCOUNT
async function deleteUserAccount(username) {
  if (!confirm(`Apakah Anda yakin ingin menghapus akun @${username}?`)) {
    return;
  }

  try {
    const res = await fetch(`/api/users/${username}`, {
      method: 'DELETE'
    });

    const result = await res.json();
    if (!res.ok) {
      alert(result.errors ? result.errors.join('\n') : "Gagal menghapus user.");
    } else {
      fetchData().then(() => {
        renderRegisteredUsersList();
      });
    }
  } catch (err) {
    console.error(err);
    alert("Terjadi kesalahan koneksi saat menghapus user.");
  }
}


// SUBMIT REGISTRASI USER BARU
async function handleRegisterUserSubmit(e) {
  e.preventDefault();
  
  const username = document.getElementById('regUsername').value;
  const role = document.getElementById('regRole').value;

  try {
    const res = await fetch('/api/users', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, role })
    });

    const result = await res.json();
    if (!res.ok) {
      const callout = document.getElementById('userValidationErrorCallout');
      const list = document.getElementById('userErrorList');
      list.innerHTML = '';
      (result.errors || ["Constraint violation"]).forEach(err => {
        list.innerHTML += `<li>${err}</li>`;
      });
      callout.style.display = 'block';
    } else {
      document.getElementById('regUsername').value = '';
      document.getElementById('userValidationErrorCallout').style.display = 'none';
      fetchData().then(() => {
        renderRegisteredUsersList();
      });
    }
  } catch (err) {
    console.error(err);
    alert("Gagal mendaftarkan akun baru ke server.");
  }
}
