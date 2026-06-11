<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barang;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    private $categoryPrefixMap = [
        'Electronics' => 'ELE',
        'Apparel' => 'APP',
        'Wellness' => 'WEL',
        'Home & Living' => 'HOM',
        'Others' => 'OTH'
    ];

    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Barang::query();

        // Search term
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('id_barang', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('jenis_barang', $request->input('category'));
        }

        // Status filter
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'instock') {
                $query->where('jumlah_barang', '>', 100);
            } elseif ($status === 'lowstock') {
                $query->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 100);
            } elseif ($status === 'outofstock') {
                $query->where('jumlah_barang', '=', 0);
            }
        }

        // Date filter
        if ($request->filled('start_date')) {
            $query->where('tanggal_masuk', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('tanggal_masuk', '<=', $request->input('end_date'));
        }

        $barang = $query->orderBy('id_barang', 'asc')->paginate(10)->withQueryString();

        // Calculating metrics
        $allItems = Barang::all();
        $totalQty = $allItems->sum('jumlah_barang');
        $inStockCount = $allItems->filter(fn($item) => $item->jumlah_barang > 100)->count();
        $lowStockCount = $allItems->filter(fn($item) => $item->jumlah_barang > 0 && $item->jumlah_barang <= 100)->count();
        $outOfStockCount = $allItems->filter(fn($item) => $item->jumlah_barang == 0)->count();

        // Map category price for asset value
        $categoryPriceMap = [
            'Electronics' => 4350,
            'Apparel' => 1200,
            'Wellness' => 800,
            'Home & Living' => 1500,
            'Others' => 500
        ];

        $totalAssetValue = $allItems->sum(function($item) use ($categoryPriceMap) {
            $price = $categoryPriceMap[$item->jenis_barang] ?? 500;
            return $item->jumlah_barang * $price;
        });

        return view('inventory', compact(
            'barang', 
            'totalQty', 
            'inStockCount', 
            'lowStockCount', 
            'outOfStockCount', 
            'totalAssetValue'
        ));
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validator = $this->validateBarang($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all()
            ], 400);
        }

        $data = $request->all();
        $data['id_barang'] = trim($data['id_barang']);
        $data['nama_barang'] = trim($data['nama_barang']);
        $data['jumlah_barang'] = (int)$data['jumlah_barang'];
        
        // Auto exit date if stock is 0
        if ($data['jumlah_barang'] === 0 && empty($data['tanggal_keluar'])) {
            $data['tanggal_keluar'] = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        }

        $data['created_at_time'] = Carbon::now('Asia/Jakarta')->toISOString();

        $barang = Barang::create($data);

        return response()->json([
            'success' => true,
            'data' => $barang
        ], 201);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, $id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json([
                'success' => false,
                'errors' => ['Data barang tidak ditemukan.']
            ], 404);
        }

        $validator = $this->validateBarang($request, $id);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all()
            ], 400);
        }

        $data = $request->all();
        $data['id_barang'] = trim($data['id_barang']);
        $data['nama_barang'] = trim($data['nama_barang']);
        $data['jumlah_barang'] = (int)$data['jumlah_barang'];

        // Manage exit date based on stock
        if ($data['jumlah_barang'] === 0 && empty($data['tanggal_keluar'])) {
            $data['tanggal_keluar'] = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        } elseif ($data['jumlah_barang'] > 0 && empty($request->input('tanggal_keluar'))) {
            $data['tanggal_keluar'] = null;
        }

        // If ID changed (Primary Key update)
        if ($barang->id_barang !== $data['id_barang']) {
            $oldId = $barang->id_barang;
            Barang::where('id_barang', $oldId)->update($data);
            $barang = Barang::find($data['id_barang']);
        } else {
            $barang->update($data);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Delete a product.
     */
    public function destroy($id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json([
                'success' => false,
                'errors' => ['Data barang tidak ditemukan.']
            ], 404);
        }

        $barang->delete();

        return response()->json([
            'success' => true,
            'message' => "Barang dengan ID '{$id}' berhasil dihapus."
        ]);
    }

    /**
     * Bulk delete products.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'errors' => ["Request body 'ids' harus berupa array string."]
            ], 400);
        }

        $count = Barang::whereIn('id_barang', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "Berhasil menghapus {$count} barang dari database."
        ]);
    }

    /**
     * Auto generate ID based on category.
     */
    public function generateId($kategori)
    {
        $prefix = $this->categoryPrefixMap[$kategori] ?? null;
        if (!$prefix) {
            return response()->json([
                'success' => false,
                'errors' => ['Kategori barang tidak valid.']
            ], 400);
        }

        $maxNum = 100;
        $items = Barang::where('id_barang', 'like', "{$prefix}-%")->get();
        
        foreach ($items as $item) {
            $parts = explode('-', $item->id_barang);
            $num = isset($parts[1]) ? (int)$parts[1] : 0;
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }

        $nextId = "{$prefix}-" . ($maxNum + 1);

        return response()->json([
            'success' => true,
            'nextId' => $nextId
        ]);
    }

    /**
     * Helper to validate product fields.
     */
    private function validateBarang(Request $request, $id = null)
    {
        $rules = [
            'nama_barang' => ['required', 'string', 'min:1'],
            'id_barang' => [
                'required', 
                'string', 
                'regex:/^[A-Z]{3}-\d+$/',
                function ($attribute, $value, $fail) use ($request, $id) {
                    // Unique check
                    if (!$id || $value !== $id) {
                        if (Barang::where('id_barang', $value)->exists()) {
                            $fail("Kolom 'id_barang' dengan nilai '{$value}' sudah ada di database (Primary Key Constraint).");
                        }
                    }

                    // Check prefix matches category
                    $kategori = $request->input('jenis_barang');
                    $expectedPrefix = $this->categoryPrefixMap[$kategori] ?? null;
                    if ($expectedPrefix && !str_starts_with($value, $expectedPrefix . '-')) {
                        $actualPrefix = explode('-', $value)[0];
                        $fail("Prefix 'id_barang' ({$actualPrefix}) tidak cocok dengan jenis kategori '{$kategori}' (Harusnya dimulai dengan '{$expectedPrefix}-').");
                    }
                }
            ],
            'jumlah_barang' => ['required', 'integer', 'min:0'],
            'jenis_barang' => ['required', Rule::in(array_keys($this->categoryPrefixMap))],
            'tanggal_masuk' => ['required', 'date_format:Y-m-d'],
            'tanggal_keluar' => [
                'nullable', 
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) use ($request) {
                    $masuk = $request->input('tanggal_masuk');
                    if ($masuk && $value && strtotime($value) < strtotime($masuk)) {
                        $fail("Kolom 'tanggal_keluar' tidak boleh lebih awal dari 'tanggal_masuk'.");
                    }
                }
            ]
        ];

        return Validator::make($request->all(), $rules, [
            'nama_barang.required' => "Kolom 'nama_barang' wajib diisi.",
            'nama_barang.string' => "Kolom 'nama_barang' harus berupa tipe data String.",
            'id_barang.required' => "Kolom 'id_barang' wajib diisi.",
            'id_barang.regex' => "Kolom 'id_barang' harus berupa format String dengan prefix kategori valid (misal: ELE-101).",
            'jumlah_barang.required' => "Kolom 'jumlah_barang' wajib diisi.",
            'jumlah_barang.integer' => "Kolom 'jumlah_barang' harus berupa tipe data Integer non-negatif.",
            'jumlah_barang.min' => "Kolom 'jumlah_barang' harus berupa tipe data Integer non-negatif.",
            'jenis_barang.required' => "Kolom 'jenis_barang' wajib diisi.",
            'jenis_barang.in' => "Kolom 'jenis_barang' harus berupa tipe data Enum. Nilai valid: [" . implode(', ', array_keys($this->categoryPrefixMap)) . "].",
            'tanggal_masuk.required' => "Kolom 'tanggal_masuk' wajib diisi.",
            'tanggal_masuk.date_format' => "Kolom 'tanggal_masuk' harus berupa tipe data Date yang valid (format: YYYY-MM-DD).",
            'tanggal_keluar.date_format' => "Kolom 'tanggal_keluar' harus berupa tipe data Date yang valid (format: YYYY-MM-DD) atau dikosongkan.",
        ]);
    }
}
