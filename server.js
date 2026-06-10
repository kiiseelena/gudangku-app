const express = require('express');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;
const DB_FILE = path.join(__dirname, 'db.json');

app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// Dynamic import of PG Pool to maintain absolute compatibility
let Pool = null;
try {
  Pool = require('pg').Pool;
} catch (err) {
  console.warn("Notice: driver 'pg' not installed. PostgreSQL mode will be disabled unless 'pg' is installed.");
}

const isPgActive = !!process.env.DATABASE_URL && !!Pool;
let pool = null;

if (isPgActive) {
  console.log("Neon Postgres Database Mode Active!");
  pool = new Pool({
    connectionString: process.env.DATABASE_URL,
    ssl: {
      rejectUnauthorized: false
    }
  });
} else {
  console.log("Local JSON Database Mode Active! (db.json)");
}

// ================= SCHEMA INITIALIZATION (POSTGRES) =================
async function initDbSchema() {
  if (!isPgActive) return;
  const client = await pool.connect();
  try {
    await client.query('BEGIN');
    
    // Create barang table
    await client.query(`
      CREATE TABLE IF NOT EXISTS barang (
        id_barang VARCHAR(50) PRIMARY KEY,
        nama_barang VARCHAR(255) NOT NULL,
        jumlah_barang INTEGER NOT NULL DEFAULT 0,
        jenis_barang VARCHAR(50) NOT NULL,
        tanggal_masuk DATE NOT NULL,
        tanggal_keluar DATE,
        created_at_time VARCHAR(100)
      )
    `);

    // Create orders table
    await client.query(`
      CREATE TABLE IF NOT EXISTS orders (
        id_order VARCHAR(50) PRIMARY KEY,
        nama_pelanggan VARCHAR(255) NOT NULL,
        id_barang VARCHAR(50) NOT NULL REFERENCES barang(id_barang) ON DELETE CASCADE,
        jumlah_order INTEGER NOT NULL,
        status_order VARCHAR(50) NOT NULL,
        tanggal_order DATE NOT NULL,
        created_at_time VARCHAR(100)
      )
    `);

    // Create users table
    await client.query(`
      CREATE TABLE IF NOT EXISTS users (
        username VARCHAR(50) PRIMARY KEY,
        role VARCHAR(50) NOT NULL,
        created_at DATE NOT NULL
      )
    `);

    // Create sessions table
    await client.query(`
      CREATE TABLE IF NOT EXISTS sessions (
        session INTEGER PRIMARY KEY,
        role VARCHAR(50) NOT NULL,
        timestamp VARCHAR(50) NOT NULL
      )
    `);

    // Run dynamic migrations to add created_at_time if tables already exist
    await client.query('ALTER TABLE barang ADD COLUMN IF NOT EXISTS created_at_time VARCHAR(100)');
    await client.query('ALTER TABLE orders ADD COLUMN IF NOT EXISTS created_at_time VARCHAR(100)');

    // Seed default admin if users table is empty
    const resUsers = await client.query('SELECT COUNT(*) FROM users');
    if (parseInt(resUsers.rows[0].count) === 0) {
      await client.query(`
        INSERT INTO users (username, role, created_at)
        VALUES ('himmad_admin', 'Admin', CURRENT_DATE)
      `);
    }

    // Seed default session if sessions table is empty
    const resSessions = await client.query('SELECT COUNT(*) FROM sessions');
    if (parseInt(resSessions.rows[0].count) === 0) {
      await client.query(`
        INSERT INTO sessions (session, role, timestamp)
        VALUES (5001, 'Admin', '08:30:15')
      `);
    }

    // Seed initial products if barang table is empty
    const resBarang = await client.query('SELECT COUNT(*) FROM barang');
    if (parseInt(resBarang.rows[0].count) === 0) {
      await client.query(`
        INSERT INTO barang (id_barang, nama_barang, jumlah_barang, jenis_barang, tanggal_masuk, tanggal_keluar)
        VALUES 
        ('ELE-101', 'PixelMate Monitor', 595, 'Electronics', '2026-01-15', NULL),
        ('ELE-102', 'FusionLink Router', 761, 'Electronics', '2026-02-10', NULL)
      `);
      
      await client.query(`
        INSERT INTO orders (id_order, nama_pelanggan, id_barang, jumlah_order, status_order, tanggal_order)
        VALUES
        ('ORD-1001', 'John Doe', 'ELE-101', 5, 'Completed', '2026-06-01')
      `);
    }

    await client.query('COMMIT');
    console.log("PostgreSQL Database Schema Initialized successfully on Neon.");
  } catch (err) {
    await client.query('ROLLBACK');
    console.error("Failed to initialize PostgreSQL schema:", err);
  } finally {
    client.release();
  }
}

// ================= LOCAL JSON FALLBACK HELPERS =================
function loadDatabase() {
  if (!fs.existsSync(DB_FILE)) {
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
          nama_pelanggan: "John Doe",
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

function saveDatabase(data) {
  fs.writeFileSync(DB_FILE, JSON.stringify(data, null, 2));
}

// ================= ABSTRACT DATABASE WRAPPERS =================

async function dbGetBarang() {
  if (isPgActive) {
    const res = await pool.query('SELECT * FROM barang ORDER BY id_barang ASC');
    return res.rows.map(r => ({
      ...r,
      tanggal_masuk: r.tanggal_masuk ? r.tanggal_masuk.toISOString().split('T')[0] : '',
      tanggal_keluar: r.tanggal_keluar ? r.tanggal_keluar.toISOString().split('T')[0] : ''
    }));
  } else {
    return loadDatabase().barang;
  }
}

async function dbGetBarangById(id) {
  if (isPgActive) {
    const res = await pool.query('SELECT * FROM barang WHERE id_barang = $1', [id]);
    if (res.rows.length === 0) return null;
    const r = res.rows[0];
    return {
      ...r,
      tanggal_masuk: r.tanggal_masuk ? r.tanggal_masuk.toISOString().split('T')[0] : '',
      tanggal_keluar: r.tanggal_keluar ? r.tanggal_keluar.toISOString().split('T')[0] : ''
    };
  } else {
    return loadDatabase().barang.find(b => b.id_barang === id) || null;
  }
}

async function dbInsertBarang(item) {
  if (isPgActive) {
    await pool.query(
      `INSERT INTO barang (id_barang, nama_barang, jumlah_barang, jenis_barang, tanggal_masuk, tanggal_keluar, created_at_time)
       VALUES ($1, $2, $3, $4, $5, $6, $7)`,
      [item.id_barang, item.nama_barang, item.jumlah_barang, item.jenis_barang, item.tanggal_masuk, item.tanggal_keluar || null, item.created_at_time || new Date().toISOString()]
    );
  } else {
    const db = loadDatabase();
    db.barang.push(item);
    saveDatabase(db);
  }
}

async function dbUpdateBarang(id, item) {
  if (isPgActive) {
    await pool.query(
      `UPDATE barang SET id_barang = $1, nama_barang = $2, jumlah_barang = $3, jenis_barang = $4, tanggal_masuk = $5, tanggal_keluar = $6, created_at_time = $7 WHERE id_barang = $8`,
      [item.id_barang, item.nama_barang, item.jumlah_barang, item.jenis_barang, item.tanggal_masuk, item.tanggal_keluar || null, item.created_at_time || new Date().toISOString(), id]
    );
  } else {
    const db = loadDatabase();
    const idx = db.barang.findIndex(b => b.id_barang === id);
    if (idx !== -1) {
      db.barang[idx] = item;
      saveDatabase(db);
    }
  }
}

async function dbDeleteBarang(id) {
  if (isPgActive) {
    const res = await pool.query('DELETE FROM barang WHERE id_barang = $1', [id]);
    return res.rowCount > 0;
  } else {
    const db = loadDatabase();
    const initialLen = db.barang.length;
    db.barang = db.barang.filter(b => b.id_barang !== id);
    saveDatabase(db);
    return db.barang.length < initialLen;
  }
}

async function dbBulkDeleteBarang(ids) {
  if (isPgActive) {
    const res = await pool.query('DELETE FROM barang WHERE id_barang = ANY($1)', [ids]);
    return res.rowCount;
  } else {
    const db = loadDatabase();
    const initialLen = db.barang.length;
    db.barang = db.barang.filter(b => !ids.includes(b.id_barang));
    saveDatabase(db);
    return initialLen - db.barang.length;
  }
}

async function dbBulkImportBarang(items) {
  if (isPgActive) {
    const client = await pool.connect();
    try {
      await client.query('BEGIN');
      for (const item of items) {
        await client.query(
          `INSERT INTO barang (id_barang, nama_barang, jumlah_barang, jenis_barang, tanggal_masuk, tanggal_keluar, created_at_time)
           VALUES ($1, $2, $3, $4, $5, $6, $7)`,
          [item.id_barang, item.nama_barang, item.jumlah_barang, item.jenis_barang, item.tanggal_masuk, item.tanggal_keluar || null, item.created_at_time || new Date().toISOString()]
        );
      }
      await client.query('COMMIT');
    } catch (err) {
      await client.query('ROLLBACK');
      throw err;
    } finally {
      client.release();
    }
  } else {
    const db = loadDatabase();
    db.barang = [...db.barang, ...items];
    saveDatabase(db);
  }
}

async function dbGetOrders() {
  if (isPgActive) {
    const res = await pool.query('SELECT * FROM orders ORDER BY id_order ASC');
    return res.rows.map(r => ({
      ...r,
      tanggal_order: r.tanggal_order ? r.tanggal_order.toISOString().split('T')[0] : ''
    }));
  } else {
    return loadDatabase().orders || [];
  }
}

async function dbGetOrderById(id) {
  if (isPgActive) {
    const res = await pool.query('SELECT * FROM orders WHERE id_order = $1', [id]);
    if (res.rows.length === 0) return null;
    const r = res.rows[0];
    return {
      ...r,
      tanggal_order: r.tanggal_order ? r.tanggal_order.toISOString().split('T')[0] : ''
    };
  } else {
    return loadDatabase().orders.find(o => o.id_order === id) || null;
  }
}

async function dbInsertOrderAndUpdateStock(order) {
  if (isPgActive) {
    const client = await pool.connect();
    try {
      await client.query('BEGIN');
      
      const resStock = await client.query('SELECT jumlah_barang, nama_barang FROM barang WHERE id_barang = $1 FOR UPDATE', [order.id_barang]);
      if (resStock.rows.length === 0) {
        throw new Error("Barang tidak ditemukan.");
      }
      
      const currentStock = resStock.rows[0].jumlah_barang;
      if (currentStock < order.jumlah_order) {
        throw new Error(`Stok barang '${resStock.rows[0].nama_barang}' tidak mencukupi. Tersedia: ${currentStock}.`);
      }
      
      await client.query(`
        UPDATE barang 
        SET jumlah_barang = jumlah_barang - $1,
            tanggal_keluar = CASE WHEN (jumlah_barang - $1) = 0 THEN CAST($2 AS DATE) ELSE tanggal_keluar END 
        WHERE id_barang = $3
      `, [order.jumlah_order, order.tanggal_order, order.id_barang]);
      
      await client.query(
        `INSERT INTO orders (id_order, nama_pelanggan, id_barang, jumlah_order, status_order, tanggal_order, created_at_time)
         VALUES ($1, $2, $3, $4, $5, $6, $7)`,
        [order.id_order, order.nama_pelanggan, order.id_barang, order.jumlah_order, order.status_order, order.tanggal_order, order.created_at_time || new Date().toISOString()]
      );
      
      await client.query('COMMIT');
    } catch (err) {
      await client.query('ROLLBACK');
      throw err;
    } finally {
      client.release();
    }
  } else {
    const db = loadDatabase();
    const barangItem = db.barang.find(b => b.id_barang === order.id_barang);
    barangItem.jumlah_barang -= order.jumlah_order;
    if (barangItem.jumlah_barang === 0) {
      barangItem.tanggal_keluar = order.tanggal_order;
    }
    db.orders.push(order);
    saveDatabase(db);
  }
}

async function dbCancelOrderAndRestoreStock(orderId) {
  if (isPgActive) {
    const client = await pool.connect();
    try {
      await client.query('BEGIN');
      
      const resOrder = await client.query('SELECT * FROM orders WHERE id_order = $1 FOR UPDATE', [orderId]);
      if (resOrder.rows.length === 0) {
        throw new Error("Order tidak ditemukan.");
      }
      
      const order = resOrder.rows[0];
      if (order.status_order === 'Cancelled') {
        throw new Error("Order sudah dibatalkan.");
      }
      
      await client.query('UPDATE orders SET status_order = \'Cancelled\' WHERE id_order = $1', [orderId]);
      
      await client.query(`
        UPDATE barang 
        SET jumlah_barang = jumlah_barang + $1,
            tanggal_keluar = CASE WHEN (jumlah_barang + $1) > 0 THEN NULL ELSE tanggal_keluar END 
        WHERE id_barang = $2
      `, [order.jumlah_order, order.id_barang]);
      
      await client.query('COMMIT');
      
      const resProd = await client.query('SELECT nama_barang FROM barang WHERE id_barang = $1', [order.id_barang]);
      return {
        success: true,
        namaBarang: resProd.rows.length > 0 ? resProd.rows[0].nama_barang : 'Barang',
        jumlahOrder: order.jumlah_order
      };
    } catch (err) {
      await client.query('ROLLBACK');
      throw err;
    } finally {
      client.release();
    }
  } else {
    const db = loadDatabase();
    const orderItem = db.orders.find(o => o.id_order === orderId);
    if (!orderItem) throw new Error("Order tidak ditemukan.");
    if (orderItem.status_order === "Cancelled") throw new Error("Order sudah dibatalkan.");
    
    const barangItem = db.barang.find(b => b.id_barang === orderItem.id_barang);
    if (barangItem) {
      barangItem.jumlah_barang += orderItem.jumlah_order;
      if (barangItem.jumlah_barang > 0) {
        barangItem.tanggal_keluar = "";
      }
    }
    orderItem.status_order = "Cancelled";
    saveDatabase(db);
    
    return {
      success: true,
      namaBarang: barangItem ? barangItem.nama_barang : 'Barang',
      jumlahOrder: orderItem.jumlah_order
    };
  }
}

async function dbUpdateOrderStatus(orderId, status) {
  if (isPgActive) {
    const res = await pool.query(
      'UPDATE orders SET status_order = $1 WHERE id_order = $2 RETURNING *',
      [status, orderId]
    );
    if (res.rows.length === 0) return null;
    return res.rows[0];
  } else {
    const db = loadDatabase();
    const orderItem = db.orders.find(o => o.id_order === orderId);
    if (!orderItem) return null;
    orderItem.status_order = status;
    saveDatabase(db);
    return orderItem;
  }
}

async function dbGetUsers() {
  if (isPgActive) {
    const res = await pool.query('SELECT * FROM users ORDER BY username ASC');
    return res.rows.map(r => ({
      ...r,
      created_at: r.created_at ? r.created_at.toISOString().split('T')[0] : ''
    }));
  } else {
    return loadDatabase().users || [];
  }
}

async function dbGetUserByUsername(username) {
  if (isPgActive) {
    const res = await pool.query('SELECT * FROM users WHERE LOWER(username) = LOWER($1)', [username]);
    if (res.rows.length === 0) return null;
    return res.rows[0];
  } else {
    const db = loadDatabase();
    return db.users.find(u => u.username.toLowerCase() === username.toLowerCase()) || null;
  }
}

async function dbInsertUser(user) {
  if (isPgActive) {
    await pool.query(
      `INSERT INTO users (username, role, created_at) VALUES ($1, $2, $3)`,
      [user.username, user.role, user.created_at]
    );
  } else {
    const db = loadDatabase();
    if (!db.users) db.users = [];
    db.users.push(user);
    saveDatabase(db);
  }
}

async function dbDeleteUser(username) {
  if (isPgActive) {
    const res = await pool.query('DELETE FROM users WHERE username = $1', [username]);
    return res.rowCount > 0;
  } else {
    const db = loadDatabase();
    const initialLen = db.users.length;
    db.users = db.users.filter(u => u.username !== username);
    saveDatabase(db);
    return db.users.length < initialLen;
  }
}

async function dbGetSessions() {
  if (isPgActive) {
    const res = await pool.query('SELECT * FROM sessions ORDER BY session ASC');
    return res.rows;
  } else {
    return loadDatabase().sessions || [];
  }
}

async function dbInsertSession(session) {
  if (isPgActive) {
    await pool.query(
      `INSERT INTO sessions (session, role, timestamp) VALUES ($1, $2, $3)`,
      [session.session, session.role, session.timestamp]
    );
    // Keep max 30 sessions in table
    await pool.query(`
      DELETE FROM sessions 
      WHERE session NOT IN (
        SELECT session FROM sessions 
        ORDER BY session DESC 
        LIMIT 30
      )
    `);
  } else {
    const db = loadDatabase();
    db.sessions.push(session);
    if (db.sessions.length > 30) {
      db.sessions.shift();
    }
    saveDatabase(db);
  }
}

// ================= SCHEMA DEFINITIONS & VALIDATIONS =================
const CATEGORY_PREFIX_MAP = {
  "Electronics": "ELE",
  "Apparel": "APP",
  "Wellness": "WEL",
  "Home & Living": "HOM",
  "Others": "OTH"
};

const ENUM_JENIS_BARANG = Object.keys(CATEGORY_PREFIX_MAP);
const ENUM_ROLE = ["Admin", "Gudang", "Manajer"];
const ENUM_ORDER_STATUS = ["Pending", "Processed", "Completed", "Cancelled"];

async function validateBarang(data, isEdit = false, currentIdBarang = null) {
  const errors = [];

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
    const idRegex = /^[A-Z]{3}-\d+$/;
    if (!idRegex.test(idStr)) {
      errors.push("Kolom 'id_barang' harus berupa format String dengan prefix kategori valid (misal: ELE-101).");
    } else {
      if (!isEdit || idStr !== currentIdBarang) {
        const duplicate = await dbGetBarangById(idStr);
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

  if (data.id_barang && data.jenis_barang) {
    const expectedPrefix = CATEGORY_PREFIX_MAP[data.jenis_barang];
    if (expectedPrefix && !data.id_barang.startsWith(expectedPrefix)) {
      errors.push(`Prefix 'id_barang' (${data.id_barang.split('-')[0]}) tidak cocok dengan jenis kategori '${data.jenis_barang}' (Harusnya dimulai dengan '${expectedPrefix}-').`);
    }
  }

  const dateRegex = /^\d{4}-\d{2}-\d{2}$/;

  // 5. tanggal_masuk: Date
  if (!data.tanggal_masuk) {
    errors.push("Kolom 'tanggal_masuk' wajib diisi.");
  } else if (typeof data.tanggal_masuk !== 'string' || !dateRegex.test(data.tanggal_masuk) || isNaN(Date.parse(data.tanggal_masuk))) {
    errors.push("Kolom 'tanggal_masuk' harus berupa tipe data Date yang valid (format: YYYY-MM-DD).");
  }

  // 6. tanggal_keluar: Date
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

async function validateOrder(data) {
  const errors = [];

  // 1. nama_pelanggan: String
  if (!data.nama_pelanggan || typeof data.nama_pelanggan !== 'string' || data.nama_pelanggan.trim() === '') {
    errors.push("Kolom 'nama_pelanggan' harus berupa data String non-kosong.");
  }

  // 2. id_barang: String (FK)
  if (!data.id_barang) {
    errors.push("Kolom 'id_barang' wajib ditentukan.");
  } else {
    const productExists = await dbGetBarangById(data.id_barang);
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

// ================= API ENDPOINTS =================

// 1. Helper ID Auto-Generator
app.get('/api/generate-id/:kategori', async (req, res) => {
  const kategori = req.params.kategori;
  const prefix = CATEGORY_PREFIX_MAP[kategori];
  
  if (!prefix) {
    return res.status(400).json({ success: false, errors: ["Kategori barang tidak valid."] });
  }

  try {
    const barangList = await dbGetBarang();
    const prefixItems = barangList.filter(b => b.id_barang.startsWith(prefix + '-'));
    
    let maxNum = 100;
    prefixItems.forEach(item => {
      const numPart = parseInt(item.id_barang.split('-')[1]);
      if (!isNaN(numPart) && numPart > maxNum) {
        maxNum = numPart;
      }
    });

    const nextId = `${prefix}-${maxNum + 1}`;
    res.json({ success: true, nextId });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal men-generate ID."] });
  }
});

// 2. INSPECT DATABASE SCHEMAS
app.get('/api/db-inspect', async (req, res) => {
  try {
    const barang = await dbGetBarang();
    const orders = await dbGetOrders();
    const users = await dbGetUsers();
    const sessions = await dbGetSessions();

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
      tables: {
        barang,
        orders,
        users,
        sessions
      }
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal memuat data inspeksi database."] });
  }
});

// ================= INVENTORY API =================

app.get('/api/barang', async (req, res) => {
  try {
    const items = await dbGetBarang();
    res.json(items);
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal memuat data barang."] });
  }
});

app.post('/api/barang', async (req, res) => {
  try {
    const validation = await validateBarang(req.body, false);
    if (!validation.isValid) {
      return res.status(400).json({ success: false, errors: validation.errors });
    }

    const newBarang = {
      id_barang: String(req.body.id_barang).trim(),
      nama_barang: req.body.nama_barang.trim(),
      jumlah_barang: parseInt(req.body.jumlah_barang),
      jenis_barang: req.body.jenis_barang,
      tanggal_masuk: req.body.tanggal_masuk,
      tanggal_keluar: req.body.tanggal_keluar || "",
      created_at_time: new Date().toISOString()
    };

    if (newBarang.jumlah_barang === 0 && !newBarang.tanggal_keluar) {
      newBarang.tanggal_keluar = new Date().toISOString().split('T')[0];
    }

    await dbInsertBarang(newBarang);
    res.status(201).json({ success: true, data: newBarang });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal menyimpan barang baru."] });
  }
});

app.put('/api/barang/:id', async (req, res) => {
  const idParam = req.params.id;
  try {
    const validation = await validateBarang(req.body, true, idParam);
    if (!validation.isValid) {
      return res.status(400).json({ success: false, errors: validation.errors });
    }

    const existing = await dbGetBarangById(idParam);
    if (!existing) {
      return res.status(404).json({ success: false, errors: ["Data barang tidak ditemukan."] });
    }

    const updatedBarang = {
      id_barang: String(req.body.id_barang).trim(),
      nama_barang: req.body.nama_barang.trim(),
      jumlah_barang: parseInt(req.body.jumlah_barang),
      jenis_barang: req.body.jenis_barang,
      tanggal_masuk: req.body.tanggal_masuk,
      tanggal_keluar: req.body.tanggal_keluar || "",
      created_at_time: existing.created_at_time || new Date().toISOString()
    };

    if (updatedBarang.jumlah_barang === 0 && !updatedBarang.tanggal_keluar) {
      updatedBarang.tanggal_keluar = new Date().toISOString().split('T')[0];
    } else if (updatedBarang.jumlah_barang > 0 && !req.body.tanggal_keluar) {
      updatedBarang.tanggal_keluar = "";
    }

    await dbUpdateBarang(idParam, updatedBarang);
    res.json({ success: true, data: updatedBarang });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal memperbarui data barang."] });
  }
});

app.delete('/api/barang/:id', async (req, res) => {
  const idBarang = req.params.id;
  try {
    const deleted = await dbDeleteBarang(idBarang);
    if (!deleted) {
      return res.status(404).json({ success: false, errors: ["Data barang tidak ditemukan."] });
    }
    res.json({ success: true, message: `Barang dengan ID '${idBarang}' berhasil dihapus.` });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal menghapus data barang."] });
  }
});

app.post('/api/barang/bulk-delete', async (req, res) => {
  const ids = req.body.ids;
  if (!Array.isArray(ids) || ids.length === 0) {
    return res.status(400).json({ success: false, errors: ["Request body 'ids' harus berupa array string."] });
  }

  try {
    const count = await dbBulkDeleteBarang(ids);
    res.json({ 
      success: true, 
      message: `Berhasil menghapus ${count} barang dari database.` 
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal menghapus massal barang."] });
  }
});

app.post('/api/barang/bulk-import', async (req, res) => {
  const items = req.body.items;
  if (!Array.isArray(items)) {
    return res.status(400).json({ success: false, errors: ["Data yang dikirim harus berupa array barang."] });
  }

  const errors = [];
  const validItems = [];
  const importedIds = new Set();

  try {
    for (let index = 0; index < items.length; index++) {
      const item = items[index];
      const idBarangStr = String(item.id_barang).trim();
      
      if (importedIds.has(idBarangStr)) {
        errors.push(`[Index ${index}] Duplikat ID Barang '${idBarangStr}' ditemukan di dalam daftar impor.`);
        continue;
      }
      importedIds.add(idBarangStr);

      const validation = await validateBarang(item, false);
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
    }

    if (errors.length > 0) {
      return res.status(400).json({ success: false, errors });
    }

    await dbBulkImportBarang(validItems);
    res.json({ success: true, message: `Berhasil mengimpor ${validItems.length} barang.` });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal mengimpor data barang."] });
  }
});

// ================= ORDERS API =================

app.get('/api/orders', async (req, res) => {
  try {
    const orders = await dbGetOrders();
    res.json(orders);
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal mengambil data orders."] });
  }
});

app.post('/api/orders', async (req, res) => {
  try {
    const validation = await validateOrder(req.body);
    if (!validation.isValid) {
      return res.status(400).json({ success: false, errors: validation.errors });
    }

    const idBarang = req.body.id_barang;
    const qtyOrder = parseInt(req.body.jumlah_order);

    const barangItem = await dbGetBarangById(idBarang);
    if (barangItem.jumlah_barang < qtyOrder) {
      return res.status(400).json({ 
        success: false, 
        errors: [`Stok barang '${barangItem.nama_barang}' tidak mencukupi untuk memenuhi order ini. Stok tersedia: ${barangItem.jumlah_barang} unit.`] 
      });
    }

    // Generate ID Order ORD-XXXX
    const orders = await dbGetOrders();
    let maxNum = 1000;
    orders.forEach(o => {
      const numPart = parseInt(o.id_order.split('-')[1]);
      if (!isNaN(numPart) && numPart > maxNum) {
        maxNum = numPart;
      }
    });
    const newOrderId = `ORD-${maxNum + 1}`;

    const newOrder = {
      id_order: newOrderId,
      nama_pelanggan: req.body.nama_pelanggan.trim(),
      id_barang: idBarang,
      jumlah_order: qtyOrder,
      status_order: req.body.status_order || "Pending",
      tanggal_order: req.body.tanggal_order,
      created_at_time: new Date().toISOString()
    };

    await dbInsertOrderAndUpdateStock(newOrder);
    res.status(201).json({ success: true, data: newOrder });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: [err.message || "Gagal membuat order baru."] });
  }
});

app.post('/api/orders/:id/cancel', async (req, res) => {
  const orderId = req.params.id;
  try {
    const result = await dbCancelOrderAndRestoreStock(orderId);
    res.json({ 
      success: true, 
      message: `Order ${orderId} berhasil dibatalkan. Stok barang '${result.namaBarang}' sebanyak ${result.jumlahOrder} unit dikembalikan ke inventaris.` 
    });
  } catch (err) {
    console.error(err);
    res.status(400).json({ success: false, errors: [err.message] });
  }
});

app.put('/api/orders/:id/status', async (req, res) => {
  const orderId = req.params.id;
  const newStatus = req.body.status_order;
  
  if (!ENUM_ORDER_STATUS.includes(newStatus)) {
    return res.status(400).json({ success: false, errors: [`Status order tidak valid.`] });
  }

  try {
    const orderItem = await dbGetOrderById(orderId);
    if (!orderItem) {
      return res.status(404).json({ success: false, errors: ["Data order tidak ditemukan."] });
    }

    if (orderItem.status_order === "Cancelled" && newStatus !== "Cancelled") {
      return res.status(400).json({ success: false, errors: ["Order yang sudah dibatalkan tidak bisa diubah statusnya lagi."] });
    }

    const updated = await dbUpdateOrderStatus(orderId, newStatus);
    res.json({ success: true, data: updated });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal merubah status order."] });
  }
});

// ================= USER MANAGEMENT API =================

app.get('/api/users', async (req, res) => {
  try {
    const users = await dbGetUsers();
    res.json(users);
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal memuat data user."] });
  }
});

app.post('/api/users', async (req, res) => {
  const { username, role } = req.body;

  if (!username || typeof username !== 'string' || username.trim() === '') {
    return res.status(400).json({ success: false, errors: ["Username wajib diisi dan berupa String."] });
  }

  if (!role || !ENUM_ROLE.includes(role)) {
    return res.status(400).json({ success: false, errors: [`Role harus berupa Enum yang valid: [${ENUM_ROLE.join(', ')}].`] });
  }

  try {
    const exists = await dbGetUserByUsername(username);
    if (exists) {
      return res.status(400).json({ success: false, errors: [`Username '${username}' sudah terdaftar di database.`] });
    }

    const newUser = {
      username: username.trim(),
      role: role,
      created_at: new Date().toISOString().split('T')[0]
    };

    await dbInsertUser(newUser);
    res.status(201).json({ success: true, data: newUser });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal mendaftarkan user baru."] });
  }
});

app.delete('/api/users/:username', async (req, res) => {
  const usernameParam = req.params.username.trim();
  try {
    const deleted = await dbDeleteUser(usernameParam);
    if (!deleted) {
      return res.status(404).json({ success: false, errors: ["Data user tidak ditemukan."] });
    }
    res.json({ success: true, message: `Akun @${usernameParam} berhasil dihapus.` });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal menghapus akun user."] });
  }
});

// ================= SESSIONS API =================

app.get('/api/sessions', async (req, res) => {
  try {
    const sessions = await dbGetSessions();
    res.json(sessions);
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal memuat data sesi."] });
  }
});

app.post('/api/sessions', async (req, res) => {
  const validation = validateSession(req.body);
  if (!validation.isValid) {
    return res.status(400).json({ success: false, errors: validation.errors });
  }

  try {
    const newSession = {
      session: parseInt(req.body.session),
      role: req.body.role,
      timestamp: req.body.timestamp
    };

    await dbInsertSession(newSession);
    res.status(201).json({ success: true, data: newSession });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, errors: ["Gagal mencatat sesi baru."] });
  }
});

// ================= SERVER STARTUP =================
async function startServer() {
  if (isPgActive) {
    console.log("Postgres configuration detected. Initializing database schema...");
    await initDbSchema();
  } else {
    console.log("No Postgres configuration. Loading fallback local JSON database...");
    loadDatabase();
  }

  app.listen(PORT, () => {
    console.log(`Server database warehouse berjalan di port ${PORT}`);
  });
}

startServer();
