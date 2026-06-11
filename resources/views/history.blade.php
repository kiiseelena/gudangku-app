@extends('layouts.app')

@section('title', 'Riwayat Log')
@section('page_title', 'Warehouse Transactions & Activities History')

@section('content')
<!-- Controls Bar -->
<div class="controls-bar" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
    <form action="{{ route('history.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; flex-grow: 1;">
        <!-- Search Input -->
        <div class="search-box" style="position: relative; min-width: 240px; flex-grow: 1; max-width: 400px;">
            <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dimmed); width: 16px; height: 16px;"></i>
            <input type="text" name="search" id="searchHistoryInput" placeholder="Cari nama barang, ID, atau keterangan..." value="{{ $search }}" style="width: 100%; padding: 10px 16px 10px 38px; background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem;">
        </div>

        <!-- Type Filter -->
        <select name="type" id="filterHistoryType" style="padding: 10px 16px; background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
            <option value="">Semua Tipe Transaksi</option>
            <option value="IN" {{ $typeFilter === 'IN' ? 'selected' : '' }}>IN (Barang Masuk)</option>
            <option value="OUT" {{ $typeFilter === 'OUT' ? 'selected' : '' }}>OUT (Barang Keluar)</option>
        </select>

        <button type="submit" class="btn" style="background-color: var(--primary); color: #fff; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
            Filter
        </button>

        @if(!empty($search) || !empty($typeFilter))
            <a href="{{ route('history.index') }}" class="btn" style="background-color: var(--bg-input); color: var(--text-main); padding: 10px 16px; border-radius: 8px; border: 1px solid var(--border-color); font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                Reset
            </a>
        @endif
    </form>
    
    <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: flex; align-items: center; gap: 6px; background: var(--bg-main); padding: 10px 16px; border-radius: 8px; border: 1px solid var(--border-color);">
        <i data-lucide="info" style="width: 16px; height: 16px; color: var(--primary);"></i>
        Diperbarui berkala
    </div>
</div>

<!-- History Log Table Card -->
<div class="card" style="background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 24px;">
    <div style="overflow-x: auto; width: 100%;">
        <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background-color: var(--bg-input);">
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted); width: 140px;">TANGGAL</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted); width: 100px;">TIPE</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">NAMA BARANG</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted); width: 140px;">ID BARANG</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted); width: 100px;">QTY</th>
                    <th style="padding: 14px 16px; font-weight: 700; color: var(--text-muted);">KETERANGAN</th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                @forelse($paginatedEvents as $ev)
                    @php
                        $badgeClass = $ev['tipe'] === 'IN' ? 'badge-instock' : 'badge-outofstock';
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color); transition: var(--transition);">
                        <td style="padding: 14px 16px;"><strong>{{ $ev['tanggal'] }}</strong></td>
                        <td style="padding: 14px 16px;">
                            <span class="badge {{ $badgeClass }}">{{ $ev['tipe'] }}</span>
                        </td>
                        <td style="padding: 14px 16px; color: var(--text-main); font-weight: 600;">{{ $ev['nama_barang'] }}</td>
                        <td style="padding: 14px 16px;"><code style="color: var(--primary); font-weight: 600; font-size: 0.8rem;">{{ $ev['id_barang'] }}</code></td>
                        <td style="padding: 14px 16px; font-weight: 700; color: var(--text-main);">{{ $ev['jumlah'] }}</td>
                        <td style="padding: 14px 16px;"><span style="color: var(--text-muted);">{{ $ev['keterangan'] }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-dimmed); padding: 40px 0; font-style: italic;">Tidak ada riwayat aktivitas gudang yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    @if($totalPages > 1)
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border-top: 1px solid var(--border-color); background-color: var(--bg-input); flex-wrap: wrap; gap: 10px;">
        <span style="font-size: 0.8rem; color: var(--text-muted);">
            Menampilkan halaman <strong style="color: var(--text-main);">{{ $currentPage }}</strong> dari <strong style="color: var(--text-main);">{{ $totalPages }}</strong> total halaman (<strong style="color: var(--text-main);">{{ $totalEvents }}</strong> record)
        </span>
        
        <div style="display: flex; gap: 5px;">
            @if($currentPage > 1)
                <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}" class="btn" style="background-color: var(--bg-main); border: 1px solid var(--border-color); color: var(--text-main); padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem;">Sebelumnya</a>
            @endif

            @for($i = 1; $i <= $totalPages; $i++)
                <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}" class="btn" style="background-color: {{ $currentPage == $i ? 'var(--primary)' : 'var(--bg-main)' }}; border: 1px solid var(--border-color); color: {{ $currentPage == $i ? '#fff' : 'var(--text-main)' }}; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem;">{{ $i }}</a>
            @endfor

            @if($currentPage < $totalPages)
                <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}" class="btn" style="background-color: var(--bg-main); border: 1px solid var(--border-color); color: var(--text-main); padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem;">Berikutnya</a>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // Auto-refresh history page every 5 seconds to keep it live
    setInterval(() => {
        // Only reload if no search/filter parameters are active to prevent interrupting the user
        const searchInput = document.getElementById('searchHistoryInput');
        const filterSelect = document.getElementById('filterHistoryType');
        if (!searchInput.value && !filterSelect.value) {
            window.location.reload();
        }
    }, 5000);
</script>
@endsection
