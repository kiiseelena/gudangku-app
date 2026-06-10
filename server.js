const express = require('express');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;
const DB_FILE = path.join(__dirname, 'db.json');

app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// Helper: Memuat Database
function loadDatabase() {
  if (!fs.existsSync(DB_FILE)) {
    // Inisialisasi awal database kosong sesuai skema baru jika file tidak ada
    const initialDB = {
      barang: [
        {
          id_barang: "ELE-101",
          nama_barang: "PixelMate Monitor",
          jumlah_barang: 595,
          jenis_barang: "Electronics",
          tanggal_masuk: "2026-01-15",
          tanggal_keluar: ""
        },
        {
          id_barang: "ELE-102",
          nama_barang: "FusionLink Router",
          jumlah_barang: 761,
          jenis_barang: "Electronics",
          tanggal_masuk: "2026-02-10",
          tanggal_keluar: ""
        }
      ],
      orders: [
        {
          id_order: "ORD-1001",
          "nama_pelanggan": "John Doe",
          id_barang: "ELE-101",
          jumlah_order: 5,
          status_order: "Completed",
          tanggal_order: "2026-06-01"
        }
      ],
      users: [
        {
          username: "himmad_admin",
          role: "Admin",
          created_at: "2026-06-01"
        }
      ],
      sessions: [
        {
          session: 5001,
          role: "Admin",
          timestamp: "08:30:15"
        }
      ]
    };
    fs.writeFileSync(DB_FILE, JSON.stringify(initialDB, null, 2));
    return initialDB;
  }
  return JSON.parse(fs.readFileSync(DB_FILE, 'utf8'));
}

// Helper: Menyimpan Database
function saveDatabase(data) {
  fs.writeFileSync(DB_FILE, JSON.stringify(data, null, 2));
}

// Map Kategori ke Prefix ID
const CATEGORY_PREFIX_MAP = {
  "Electronics": "ELE",
  "Apparel": "APP",
  "Wellness": "WEL",
  "Home & Living": "HOM",
  "Others": "OTH"
};

// Definisi Skema Database & Validasi
const ENUM_JENIS_BARANG = Object.keys(CATEGORY_PREFIX_MAP);
const ENUM_ROLE = ["Admin", "Gudang", "Manajer"];
const ENUM_ORDER_STATUS = ["Pending", "Processed", "Completed", "Cancelled"];

// Fungsi Validasi Tipe Data Barang
function validateBarang(data, isEdit = false, currentIdBarang = null) {
  const errors = [];
  const db = loadDatabase();

  // 1. nama_barang: String
  if (data.nama_barang === undefined || data.nama_barang === null) {
    errors.push("Kolom 'nama_barang' wajib diisi.");
  } else if (typeof data.nama_barang !== 'string' || data.nama_barang.trim() === '') {
    errors.push("Kolom 'nama_barang' harus berupa tipe data String non-kosong.");
  }

  // 2. id_barang: String (Primary Key)
  if (!data.id_barang) {
    errors.push("Kolom 'id_barang' wajib diisi.");
  } else {
    const idStr = String(data.id_barang).trim();
    // Harus sesuai pattern PREFIX-NOMOR (misal ELE-101)
    const idRegex = /^[A-Z]{3}-\d+$/;
    if (!idRegex.test(idStr)) {
      errors.push("Kolom 'id_barang' harus berupa format String dengan prefix kategori valid (misal: ELE-101).");
    } else {
      // Validasi keunikan Primary Key jika bukan edit data yang sama
      if (!isEdit || idStr !== currentIdBarang) {
        const duplicate = db.barang.find(b => b.id_barang === idStr);
        if (duplicate) {
          errors.push(`Kolom 'id_barang' dengan nilai '${idStr}' sudah ada di database (Primary Key Constraint).`);
        }
      }
    }
  }

  // 3. jumlah_barang: Int
  if (data.jumlah_barang === undefined || data.jumlah_barang === null) {
    errors.push("Kolom 'jumlah_barang' wajib diisi.");
  } else {
    const jumlahInt = Number(data.jumlah_barang);
    if (!Number.isInteger(jumlahInt) || jumlahInt < 0) {
      errors.push("Kolom 'jumlah_barang' harus berupa tipe data Integer non-negatif.");
    }
  }

  // 4. jenis_barang: Enum
  if (!data.jenis_barang) {
    errors.push("Kolom 'jenis_barang' wajib diisi.");
  } else if (!ENUM_JENIS_BARANG.includes(data.jenis_barang)) {
    errors.push(`Kolom 'jenis_barang' harus berupa tipe data Enum. Nilai valid: [${ENUM_JENIS_BARANG.join(', ')}].`);
  }

  // Validasi kesesuaian prefix ID barang dengan jenis barang
  if (data.id_barang && data.jenis_barang) {
    const expectedPrefix = CATEGORY_PREFIX_MAP[data.jenis_barang];
    if (expectedPrefix && !data.id_barang.startsWith(expectedPrefix)) {
      errors.push(`Prefix 'id_barang' (${data.id_barang.split('-')[0]}) tidak cocok dengan jenis kategori '${data.jenis_barang}' (Harusnya dimulai dengan '${expectedPrefix}-').`);
    }
  }

  // Regex tanggal YYYY-MM-DD
  const dateRegex = /^\d{4}-\d{2}-\d{2}$/;

  // 5. tanggal_masuk: Date
  if (!data.tanggal_masuk) {
    errors.push("Kolom 'tanggal_masuk' wajib diisi.");
  } else if (typeof data.tanggal_masuk !== 'string' || !dateRegex.test(data.tanggal_masuk) || isNaN(Date.parse(data.tanggal_masuk))) {
    errors.push("Kolom 'tanggal_masuk' harus berupa tipe data Date yang valid (format: YYYY-MM-DD).");
  }

  // 6. tanggal_keluar: Date (boleh kosong, tapi jika diisi harus valid)
  if (data.tanggal_keluar) {
    if (typeof data.tanggal_keluar !== 'string' || !dateRegex.test(data.tanggal_keluar) || isNaN(Date.parse(data.tanggal_keluar))) {
      errors.push("Kolom 'tanggal_keluar' harus berupa tipe data Date yang valid (format: YYYY-MM-DD) atau dikosongkan.");
    } else if (data.tanggal_masuk && Date.parse(data.tanggal_keluar) < Date.parse(data.tanggal_masuk)) {
      errors.push("Kolom 'tanggal_keluar' tidak boleh lebih awal dari 'tanggal_masuk'.");
    }
  }

  return {
    isValid: errors.length === 0,
    errors
  };
}

// Fungsi Validasi Sesi
function validateSession(data) {
  const errors = [];
  if (data.session === undefined || data.session === null) {
    errors.push("Kolom 'session' wajib diisi.");
  } else {
    const sessionInt = Number(data.session);
    if (!Number.isInteger(sessionInt) || sessionInt <= 0) {
      errors.push("Kolom 'session' harus berupa tipe data Integer positif.");
    }
  }

  if (!data.role) {
    errors.push("Kolom 'role' wajib diisi.");
  } else if (!ENUM_ROLE.includes(data.role)) {
    errors.push(`Kolom 'role' harus berupa tipe data Enum. Nilai valid: [${ENUM_ROLE.join(', ')}].`);
  }

  const timeRegex = /^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/;
  if (!data.timestamp) {
    errors.push("Kolom 'timestamp' wajib diisi.");
  } else if (typeof data.timestamp !== 'string' || !timeRegex.test(data.timestamp)) {
    errors.push("Kolom 'timestamp' harus berupa tipe data Time yang valid (format: HH:MM:SS).");
  }

  return { isValid: errors.length === 0, errors };
}

// Fungsi Validasi Order
function validateOrder(data) {
  const errors = [];
  const db = loadDatabase();

  // 1. nama_pelanggan: String
  if (!data.nama_pelanggan || typeof data.nama_pelanggan !== 'string' || data.nama_pelanggan.trim() === '') {
    errors.push("Kolom 'nama_pelanggan' harus berupa tipe data String non-kosong.");
  }

  // 2. id_barang: String (Foreign Key Constraint)
  if (!data.id_barang) {
    errors.push("Kolom 'id_barang' wajib ditentukan.");
  } else {
    const productExists = db.barang.find(b => b.id_barang === data.id_barang);
    if (!productExists) {
      errors.push(`Barang dengan ID '${data.id_barang}' tidak ditemukan di database (Foreign Key Constraint Violation).`);
    }
  }

  // 3. jumlah_order: Int
  if (data.jumlah_order === undefined || data.jumlah_order === null) {
    errors.push("Kolom 'jumlah_order' wajib diisi.");
  } else {
    const qtyInt = Number(data.jumlah_order);
    if (!Number.isInteger(qtyInt) || qtyInt <= 0) {
      errors.push("Kolom 'jumlah_order' harus berupa tipe data Integer positif (> 0).");
    }
  }

  // 4. tanggal_order: Date
  const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
  if (!data.tanggal_order || !dateRegex.test(data.tanggal_order) || isNaN(Date.parse(data.tanggal_order))) {
    errors.push("Kolom 'tanggal_order' harus berupa tipe data Date yang valid (format: YYYY-MM-DD).");
  }

  // 5. status_order: Enum
  if (data.status_order && !ENUM_ORDER_STATUS.includes(data.status_order)) {
    errors.push(`Kolom 'status_order' harus berupa tipe data Enum. Nilai valid: [${ENUM_ORDER_STATUS.join(', ')}].`);
  }

  return {
    isValid: errors.length === 0,
    errors
  };
}

// --- API ENDPOINTS ---

// 1. Helper ID Auto-Generator
app.get('/api/generate-id/:kategori', (req, res) => {
  const kategori = req.params.kategori;
  const prefix = CATEGORY_PREFIX_MAP[kategori];
  
  if (!prefix) {
    return res.status(400).json({ success: false, errors: ["Kategori barang tidak valid."] });
  }

  const db = loadDatabase();
  // Filter barang yang diawali prefix kategori
  const prefixItems = db.barang.filter(b => b.id_barang.startsWith(prefix + '-'));
  
  let maxNum = 100; // Start counter at 101 (maxNum + 1)
  prefixItems.forEach(item => {
    const numPart = parseInt(item.id_barang.split('-')[1]);
    if (!isNaN(numPart) && numPart > maxNum) {
      maxNum = numPart;
    }
  });

  const nextId = `${prefix}-${maxNum + 1}`;
  res.json({ success: true, nextId });
});

// 2. INSPECT DATABASE SCHEMAS
app.get('/api/db-inspect', (req, res) => {
  const db = loadDatabase();
  res.json({
    schemas: {
      barang: {
        id_barang: "String (Primary Key - Prefix Auto-generated)",
        nama_barang: "String",
        jumlah_barang: "Integer",
        jenis_barang: `Enum [${ENUM_JENIS_BARANG.join(', ')}]`,
        tanggal_masuk: "Date (YYYY-MM-DD)",
        tanggal_keluar: "Date (YYYY-MM-DD, Nullable)"
      },
      orders: {
        id_order: "String (Primary Key)",
        nama_pelanggan: "String",
        id_barang: "String (Foreign Key)",
        jumlah_order: "Integer",
        status_order: `Enum [${ENUM_ORDER_STATUS.join(', ')}]`,
        tanggal_order: "Date (YYYY-MM-DD)"
      },
      users: {
        username: "String (Unique Key)",
        role: `Enum [${ENUM_ROLE.join(', ')}]`,
        created_at: "Date"
      }
    },
    tables: db
  });
});

// --- INVENTORY API ---
app.get('/api/barang', (req, res) => {
  const db = loadDatabase();
  res.json(db.barang);
});

// Add Product (Insert)
app.post('/api/barang', (req, res) => {
  const validation = validateBarang(req.body, false);
  if (!validation.isValid) {
    return res.status(400).json({ success: false, errors: validation.errors });
  }

  const db = loadDatabase();
  const newBarang = {
    id_barang: String(req.body.id_barang).trim(),
    nama_barang: req.body.nama_barang.trim(),
    jumlah_barang: parseInt(req.body.jumlah_barang),
    jenis_barang: req.body.jenis_barang,
    tanggal_masuk: req.body.tanggal_masuk,
    tanggal_keluar: req.body.tanggal_keluar || ""
  };

  db.barang.push(newBarang);
  saveDatabase(db);
  res.status(201).json({ success: true, data: newBarang });
});

// Update Product
app.put('/api/barang/:id', (req, res) => {
  const idParam = req.params.id;
  const validation = validateBarang(req.body, true, idParam);
  if (!validation.isValid) {
    return res.status(400).json({ success: false, errors: validation.errors });
  }

  const db = loadDatabase();
  const idx = db.barang.findIndex(b => b.id_barang === idParam);
  if (idx === -1) {
    return res.status(404).json({ success: false, errors: ["Data barang tidak ditemukan."] });
  }

  db.barang[idx] = {
    id_barang: String(req.body.id_barang).trim(),
    nama_barang: req.body.nama_barang.trim(),
    jumlah_barang: parseInt(req.body.jumlah_barang),
    jenis_barang: req.body.jenis_barang,
    tanggal_masuk: req.body.tanggal_masuk,
    tanggal_keluar: req.body.tanggal_keluar || ""
  };

  saveDatabase(db);
  res.json({ success: true, data: db.barang[idx] });
});

// Delete Product
app.delete('/api/barang/:id', (req, res) => {
  const idBarang = req.params.id;
  const db = loadDatabase();
  const filtered = db.barang.filter(b => b.id_barang !== idBarang);
  
  if (filtered.length === db.barang.length) {
    return res.status(404).json({ success: false, errors: ["Data barang tidak ditemukan."] });
  }

  db.barang = filtered;
  saveDatabase(db);
  res.json({ success: true, message: `Barang dengan ID '${idBarang}' berhasil dihapus.` });
});

// Bulk Delete Barang
app.post('/api/barang/bulk-delete', (req, res) => {
  const ids = req.body.ids; // Array of String IDs
  if (!Array.isArray(ids) || ids.length === 0) {
    return res.status(400).json({ success: false, errors: ["Request body 'ids' harus berupa array string."] });
  }

  const db = loadDatabase();
  const initialLength = db.barang.length;
  db.barang = db.barang.filter(b => !ids.includes(b.id_barang));

  saveDatabase(db);
  res.json({ 
    success: true, 
    message: `Berhasil menghapus ${initialLength - db.barang.length} barang dari database.` 
  });
});

// Bulk Import Barang
app.post('/api/barang/bulk-import', (req, res) => {
  const items = req.body.items;
  if (!Array.isArray(items)) {
    return res.status(400).json({ success: false, errors: ["Data yang dikirim harus berupa array barang."] });
  }

  const errors = [];
  const db = loadDatabase();
  const validItems = [];
  const importedIds = new Set();

  items.forEach((item, index) => {
    const idBarangStr = String(item.id_barang).trim();
    
    if (importedIds.has(idBarangStr)) {
      errors.push(`[Index ${index}] Duplikat ID Barang '${idBarangStr}' ditemukan di dalam daftar impor.`);
      return;
    }
    importedIds.add(idBarangStr);

    const validation = validateBarang(item, false);
    if (!validation.isValid) {
      validation.errors.forEach(err => {
        errors.push(`[Baris ${index + 1}: ${item.nama_barang || 'Tanpa Nama'}] ${err}`);
      });
    } else {
      validItems.push({
        id_barang: idBarangStr,
        nama_barang: String(item.nama_barang).trim(),
        jumlah_barang: parseInt(item.jumlah_barang),
        jenis_barang: item.jenis_barang,
        tanggal_masuk: item.tanggal_masuk,
        tanggal_keluar: item.tanggal_keluar || ""
      });
    }
  });

  if (errors.length > 0) {
    return res.status(400).json({ success: false, errors });
  }

  db.barang = [...db.barang, ...validItems];
  saveDatabase(db);
  res.json({ success: true, message: `Berhasil mengimpor ${validItems.length} barang.` });
});


// --- ORDERS API ---

// Ambil semua order
app.get('/api/orders', (req, res) => {
  const db = loadDatabase();
  res.json(db.orders);
});

// Tambah Order Baru (Terintegrasi Pemotongan Stok)
app.post('/api/orders', (req, res) => {
  const validation = validateOrder(req.body);
  if (!validation.isValid) {
    return res.status(400).json({ success: false, errors: validation.errors });
  }

  const db = loadDatabase();
  const idBarang = req.body.id_barang;
  const qtyOrder = parseInt(req.body.jumlah_order);

  // Cari barang di database
  const barangItem = db.barang.find(b => b.id_barang === idBarang);
  
  // 1. CEK STOK BARANG (Integrasi Database Logic)
  if (barangItem.jumlah_barang < qtyOrder) {
    return res.status(400).json({ 
      success: false, 
      errors: [`Stok barang '${barangItem.nama_barang}' tidak mencukupi untuk memenuhi order ini. Stok tersedia: ${barangItem.jumlah_barang} unit.`] 
    });
  }

  // 2. POTONG STOK BARANG
  barangItem.jumlah_barang -= qtyOrder;

  // 3. Generate ID Order ORD-XXXX
  let maxNum = 1000;
  db.orders.forEach(o => {
    const numPart = parseInt(o.id_order.split('-')[1]);
    if (!isNaN(numPart) && numPart > maxNum) {
      maxNum = numPart;
    }
  });
  const newOrderId = `ORD-${maxNum + 1}`;

  // 4. Buat record order
  const newOrder = {
    id_order: newOrderId,
    nama_pelanggan: req.body.nama_pelanggan.trim(),
    id_barang: idBarang,
    jumlah_order: qtyOrder,
    status_order: req.body.status_order || "Pending",
    tanggal_order: req.body.tanggal_order
  };

  db.orders.push(newOrder);
  saveDatabase(db);
  res.status(201).json({ success: true, data: newOrder });
});

// Batalkan Order (Restore Stok Barang)
app.post('/api/orders/:id/cancel', (req, res) => {
  const orderId = req.params.id;
  const db = loadDatabase();

  const orderItem = db.orders.find(o => o.id_order === orderId);
  if (!orderItem) {
    return res.status(404).json({ success: false, errors: ["Data order tidak ditemukan."] });
  }

  if (orderItem.status_order === "Cancelled") {
    return res.status(400).json({ success: false, errors: ["Order ini sudah dibatalkan sebelumnya."] });
  }

  // Cari barang terkait
  const barangItem = db.barang.find(b => b.id_barang === orderItem.id_barang);
  
  // 1. RESTORE STOK (Tambah kembali stok barang)
  if (barangItem) {
    barangItem.jumlah_barang += orderItem.jumlah_order;
  }

  // 2. UBAH STATUS ORDER MENJADI CANCELLED
  orderItem.status_order = "Cancelled";

  saveDatabase(db);
  res.json({ 
    success: true, 
    message: `Order ${orderId} berhasil dibatalkan. Stok barang '${barangItem ? barangItem.nama_barang : 'Barang'}' sebanyak ${orderItem.jumlah_order} unit dikembalikan ke inventaris.` 
  });
});

// Update Status Order Lainnya (Processed / Completed)
app.put('/api/orders/:id/status', (req, res) => {
  const orderId = req.params.id;
  const newStatus = req.body.status_order;
  
  if (!ENUM_ORDER_STATUS.includes(newStatus)) {
    return res.status(400).json({ success: false, errors: [`Status order tidak valid.`] });
  }

  const db = loadDatabase();
  const orderItem = db.orders.find(o => o.id_order === orderId);
  if (!orderItem) {
    return res.status(404).json({ success: false, errors: ["Data order tidak ditemukan."] });
  }

  if (orderItem.status_order === "Cancelled" && newStatus !== "Cancelled") {
    return res.status(400).json({ success: false, errors: ["Order yang sudah dibatalkan tidak bisa diubah statusnya lagi."] });
  }

  orderItem.status_order = newStatus;
  saveDatabase(db);
  res.json({ success: true, data: orderItem });
});


// --- USER MANAGEMENT API (MEMBUAT AKUN OLEH ADMIN) ---

// Dapatkan daftar semua user
app.get('/api/users', (req, res) => {
  const db = loadDatabase();
  res.json(db.users || []);
});

// Registrasi User Baru (Hanya Boleh Diizinkan di Sisi Client jika Active Role == Admin)
app.post('/api/users', (req, res) => {
  const db = loadDatabase();
  const { username, role } = req.body;

  if (!username || typeof username !== 'string' || username.trim() === '') {
    return res.status(400).json({ success: false, errors: ["Username wajib diisi dan berupa String."] });
  }

  if (!role || !ENUM_ROLE.includes(role)) {
    return res.status(400).json({ success: false, errors: [`Role harus berupa Enum yang valid: [${ENUM_ROLE.join(', ')}].`] });
  }

  // Cek keunikan username
  const exists = db.users.find(u => u.username.toLowerCase() === username.trim().toLowerCase());
  if (exists) {
    return res.status(400).json({ success: false, errors: [`Username '${username}' sudah terdaftar di database.`] });
  }

  const newUser = {
    username: username.trim(),
    role: role,
    created_at: new Date().toISOString().split('T')[0]
  };

  if (!db.users) db.users = [];
  db.users.push(newUser);
  saveDatabase(db);

  res.status(201).json({ success: true, data: newUser });
});

// Hapus Akun User (Admin Panel)
app.delete('/api/users/:username', (req, res) => {
  const usernameParam = req.params.username.trim();
  const db = loadDatabase();

  const filtered = db.users.filter(u => u.username !== usernameParam);
  if (filtered.length === db.users.length) {
    return res.status(404).json({ success: false, errors: ["Data user tidak ditemukan."] });
  }

  db.users = filtered;
  saveDatabase(db);
  res.json({ success: true, message: `Akun @${usernameParam} berhasil dihapus.` });
});



// --- SESSIONS API ---
app.get('/api/sessions', (req, res) => {
  const db = loadDatabase();
  res.json(db.sessions);
});

app.post('/api/sessions', (req, res) => {
  const validation = validateSession(req.body);
  if (!validation.isValid) {
    return res.status(400).json({ success: false, errors: validation.errors });
  }

  const db = loadDatabase();
  const newSession = {
    session: parseInt(req.body.session),
    role: req.body.role,
    timestamp: req.body.timestamp
  };

  db.sessions.push(newSession);
  if (db.sessions.length > 30) {
    db.sessions.shift();
  }

  saveDatabase(db);
  res.status(201).json({ success: true, data: newSession });
});

// Start Server
loadDatabase();
app.listen(PORT, () => {
  console.log(`Server database inventory berjalan di port ${PORT}`);
});
