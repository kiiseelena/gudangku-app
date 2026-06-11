<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $primaryKey = 'id_order';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_order',
        'nama_pelanggan',
        'id_barang',
        'jumlah_order',
        'status_order',
        'tanggal_order',
        'created_at_time'
    ];

    /**
     * Get the barang associated with the order.
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}
