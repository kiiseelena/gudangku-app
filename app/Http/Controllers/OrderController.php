<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Barang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $query = Order::query();

        // Search term
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('id_order', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('id_barang', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status_order', $request->input('status'));
        }

        $orders = $query->orderBy('id_order', 'asc')->paginate(10)->withQueryString();

        // Calculate metrics
        $allOrders = Order::all();
        $barangList = Barang::all();

        // Map category price for revenue
        $categoryPriceMap = [
            'Electronics' => 4350,
            'Apparel' => 1200,
            'Wellness' => 800,
            'Home & Living' => 1500,
            'Others' => 500
        ];

        $totalRevenue = $allOrders->filter(fn($o) => $o->status_order !== 'Cancelled')->sum(function($o) use ($barangList, $categoryPriceMap) {
            $barang = $barangList->firstWhere('id_barang', $o->id_barang);
            $price = $barang ? ($categoryPriceMap[$barang->jenis_barang] ?? 500) : 500;
            return $o->jumlah_order * $price;
        });

        $activeOrdersCount = $allOrders->filter(fn($o) => $o->status_order !== 'Cancelled')->count();
        $completedCount = $allOrders->where('status_order', 'Completed')->count();
        $processedCount = $allOrders->where('status_order', 'Processed')->count();
        $pendingCount = $allOrders->where('status_order', 'Pending')->count();
        $cancelledCount = $allOrders->where('status_order', 'Cancelled')->count();

        // Get products list for the create order dropdown
        $availableProducts = Barang::orderBy('nama_barang', 'asc')->get();

        return view('orders', compact(
            'orders',
            'totalRevenue',
            'activeOrdersCount',
            'completedCount',
            'processedCount',
            'pendingCount',
            'cancelledCount',
            'availableProducts'
        ));
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_pelanggan' => ['required', 'string', 'min:1'],
            'id_barang' => ['required', 'string', 'exists:barang,id_barang'],
            'jumlah_order' => ['required', 'integer', 'min:1'],
            'tanggal_order' => ['required', 'date_format:Y-m-d'],
            'status_order' => ['nullable', 'string', Rule::in(['Pending', 'Processed', 'Completed', 'Cancelled'])]
        ], [
            'nama_pelanggan.required' => "Kolom 'nama_pelanggan' harus berupa data String non-kosong.",
            'id_barang.required' => "Kolom 'id_barang' wajib ditentukan.",
            'id_barang.exists' => "Barang dengan ID yang dipilih tidak ditemukan di database.",
            'jumlah_order.required' => "Kolom 'jumlah_order' wajib diisi.",
            'jumlah_order.integer' => "Kolom 'jumlah_order' harus berupa tipe data Integer positif.",
            'jumlah_order.min' => "Kolom 'jumlah_order' harus berupa tipe data Integer positif (> 0).",
            'tanggal_order.required' => "Kolom 'tanggal_order' harus berupa tipe data Date yang valid (format: YYYY-MM-DD).",
            'tanggal_order.date_format' => "Kolom 'tanggal_order' harus berupa tipe data Date yang valid (format: YYYY-MM-DD).",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all()
            ], 400);
        }

        $idBarang = $request->input('id_barang');
        $qtyOrder = (int)$request->input('jumlah_order');

        try {
            $newOrder = DB::transaction(function() use ($request, $idBarang, $qtyOrder) {
                // Lock the product row for update
                $barang = Barang::where('id_barang', $idBarang)->lockForUpdate()->first();
                
                if ($barang->jumlah_barang < $qtyOrder) {
                    throw new \Exception("Stok barang '{$barang->nama_barang}' tidak mencukupi untuk memenuhi order ini. Stok tersedia: {$barang->jumlah_barang} unit.");
                }

                // Decrement stock
                $newStock = $barang->jumlah_barang - $qtyOrder;
                $updateData = ['jumlah_barang' => $newStock];
                
                // If stock is depleted, set tanggal_keluar
                if ($newStock === 0) {
                    $updateData['tanggal_keluar'] = $request->input('tanggal_order');
                }

                $barang->update($updateData);

                // Auto generate ORD-XXXX ID
                $maxNum = 1000;
                $allOrders = Order::all();
                foreach ($allOrders as $o) {
                    $parts = explode('-', $o->id_order);
                    $num = isset($parts[1]) ? (int)$parts[1] : 0;
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
                $newOrderId = 'ORD-' . ($maxNum + 1);

                return Order::create([
                    'id_order' => $newOrderId,
                    'nama_pelanggan' => trim($request->input('nama_pelanggan')),
                    'id_barang' => $idBarang,
                    'jumlah_order' => $qtyOrder,
                    'status_order' => $request->input('status_order', 'Pending'),
                    'tanggal_order' => $request->input('tanggal_order'),
                    'created_at_time' => Carbon::now('Asia/Jakarta')->toISOString()
                ]);
            });

            return response()->json([
                'success' => true,
                'data' => $newOrder
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => [$e->getMessage()]
            ], 400);
        }
    }

    /**
     * Cancel an order and restore stock.
     */
    public function cancel($id)
    {
        try {
            $result = DB::transaction(function() use ($id) {
                $order = Order::where('id_order', $id)->lockForUpdate()->first();

                if (!$order) {
                    throw new \Exception("Order tidak ditemukan.");
                }

                if ($order->status_order === 'Cancelled') {
                    throw new \Exception("Order sudah dibatalkan.");
                }

                // Update status to Cancelled
                $order->update(['status_order' => 'Cancelled']);

                // Restore stock
                $barang = Barang::where('id_barang', $order->id_barang)->lockForUpdate()->first();
                if ($barang) {
                    $newStock = $barang->jumlah_barang + $order->jumlah_order;
                    $updateData = ['jumlah_barang' => $newStock];
                    
                    // If stock is now above 0, reset exit date
                    if ($newStock > 0) {
                        $updateData['tanggal_keluar'] = null;
                    }
                    $barang->update($updateData);
                }

                return [
                    'namaBarang' => $barang ? $barang->nama_barang : 'Barang',
                    'jumlahOrder' => $order->jumlah_order
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Order {$id} berhasil dibatalkan. Stok barang '{$result['namaBarang']}' sebanyak {$result['jumlahOrder']} unit dikembalikan ke inventaris."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => [$e->getMessage()]
            ], 400);
        }
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, $id)
    {
        $newStatus = $request->input('status_order');

        if (!in_array($newStatus, ['Pending', 'Processed', 'Completed', 'Cancelled'])) {
            return response()->json([
                'success' => false,
                'errors' => ['Status order tidak valid.']
            ], 400);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'errors' => ['Data order tidak ditemukan.']
            ], 404);
        }

        if ($order->status_order === 'Cancelled' && $newStatus !== 'Cancelled') {
            return response()->json([
                'success' => false,
                'errors' => ['Order yang sudah dibatalkan tidak bisa diubah statusnya lagi.']
            ], 400);
        }

        // If transitioning to Cancelled, we should trigger cancel function logic (restore stock)
        if ($newStatus === 'Cancelled' && $order->status_order !== 'Cancelled') {
            return $this->cancel($id);
        }

        $order->update(['status_order' => $newStatus]);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
}
