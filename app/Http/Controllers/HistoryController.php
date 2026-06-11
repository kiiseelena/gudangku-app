<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barang;
use App\Models\Order;
use Carbon\Carbon;

class HistoryController extends Controller
{
    /**
     * Display the warehouse history timeline.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $typeFilter = $request->input('type', '');

        $barang = Barang::all();
        $orders = Order::all();

        $events = collect();

        // 1. Add Barang Masuk initial entries (IN)
        foreach ($barang as $item) {
            $events->push([
                'tanggal' => $item->tanggal_masuk,
                'tipe' => 'IN',
                'nama_barang' => $item->nama_barang,
                'id_barang' => $item->id_barang,
                'jumlah' => $item->jumlah_barang,
                'keterangan' => "Stok awal barang ditambahkan ke inventaris ({$item->jenis_barang})",
                'created_at_time' => $item->created_at_time ?: $item->tanggal_masuk,
            ]);
        }

        // 2. Add Orders entries (OUT or IN for cancellation)
        foreach ($orders as $o) {
            $item = $barang->firstWhere('id_barang', $o->id_barang);
            $namaBarang = $item ? $item->nama_barang : 'Barang Terhapus';

            if ($o->status_order !== 'Cancelled') {
                $events->push([
                    'tanggal' => $o->tanggal_order,
                    'tipe' => 'OUT',
                    'nama_barang' => $namaBarang,
                    'id_barang' => $o->id_barang,
                    'id_order' => $o->id_order,
                    'jumlah' => $o->jumlah_order,
                    'keterangan' => "Pesanan pelanggan {$o->id_order} oleh {$o->nama_pelanggan} ({$o->status_order})",
                    'created_at_time' => $o->created_at_time ?: $o->tanggal_order,
                ]);
            } else {
                // If Cancelled, it's an IN event restoring stock
                // Add a small offset to timestamp so it appears after the initial out order
                $cancelledTime = $o->created_at_time 
                    ? Carbon::parse($o->created_at_time)->addSecond()->toISOString() 
                    : $o->tanggal_order;

                $events->push([
                    'tanggal' => $o->tanggal_order,
                    'tipe' => 'IN',
                    'nama_barang' => $namaBarang,
                    'id_barang' => $o->id_barang,
                    'id_order' => $o->id_order,
                    'jumlah' => $o->jumlah_order,
                    'keterangan' => "Pengembalian stok dari pembatalan pesanan {$o->id_order}",
                    'created_at_time' => $cancelledTime,
                ]);
            }
        }

        // Sort events descending (latest first)
        $sortedEvents = $events->sortByDesc(function ($event) {
            return $event['created_at_time'];
        });

        // Filter events
        $filteredEvents = $sortedEvents->filter(function ($ev) use ($search, $typeFilter) {
            // Search filter
            $matchSearch = true;
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $matchSearch = str_contains(strtolower($ev['nama_barang']), $searchLower) ||
                               str_contains(strtolower($ev['id_barang']), $searchLower) ||
                               str_contains(strtolower($ev['keterangan']), $searchLower);
            }

            // Type filter
            $matchType = true;
            if (!empty($typeFilter)) {
                $matchType = $ev['tipe'] === $typeFilter;
            }

            return $matchSearch && $matchType;
        });

        // Paginate manually since we merged two collections in memory
        $currentPage = $request->input('page', 1);
        $perPage = 10;
        $paginatedEvents = $filteredEvents->slice(($currentPage - 1) * $perPage, $perPage)->all();
        
        $totalEvents = $filteredEvents->count();
        $totalPages = ceil($totalEvents / $perPage);

        return view('history', compact(
            'paginatedEvents',
            'search',
            'typeFilter',
            'currentPage',
            'totalPages',
            'totalEvents',
            'perPage'
        ));
    }
}
