<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';

    protected $primaryKey = 'id_barang';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_barang',
        'nama_barang',
        'jumlah_barang',
        'jenis_barang',
        'tanggal_masuk',
        'tanggal_keluar',
        'created_at_time'
    ];

    /**
     * Get the orders for the barang.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'id_barang', 'id_barang');
    }
}
