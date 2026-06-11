<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barang;
use App\Models\Order;

class HomeController extends Controller
{
    /**
     * Display the warehouse dashboard summary.
     */
    public function index()
    {
        $barang = Barang::all();
        $orders = Order::all();

        // 1. Summarize stock by category
        $categories = ['Electronics', 'Apparel', 'Wellness', 'Home & Living', 'Others'];
        $categorySummary = [];
        
        foreach ($categories as $cat) {
            $catItems = $barang->where('jenis_barang', $cat);
            $categorySummary[$cat] = [
                'unique_count' => $catItems->count(),
                'total_qty' => $catItems->sum('jumlah_barang'),
                'icon' => $this->getCategoryIcon($cat)
            ];
        }

        // 2. Summarize order counts by status
        $orderSummary = [
            'Completed' => $orders->where('status_order', 'Completed')->count(),
            'Processed' => $orders->where('status_order', 'Processed')->count(),
            'Pending' => $orders->where('status_order', 'Pending')->count(),
            'Cancelled' => $orders->where('status_order', 'Cancelled')->count(),
        ];

        // 3. Recent 3 orders sorted by id descending
        $recentOrders = Order::orderBy('id_order', 'desc')
            ->take(3)
            ->get();

        return view('home', compact('categorySummary', 'orderSummary', 'recentOrders', 'barang'));
    }

    /**
     * Helper to map category to Lucide icon name.
     */
    private function getCategoryIcon($category)
    {
        switch ($category) {
            case 'Electronics': return 'cpu';
            case 'Apparel': return 'shirt';
            case 'Wellness': return 'sparkles';
            case 'Home & Living': return 'lamp';
            default: return 'package';
        }
    }
}
