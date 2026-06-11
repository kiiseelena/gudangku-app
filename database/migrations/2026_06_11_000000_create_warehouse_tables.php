<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create barang table
        Schema::create('barang', function (Blueprint $table) {
            $table->string('id_barang', 50)->primary();
            $table->string('nama_barang', 255);
            $table->integer('jumlah_barang')->default(0);
            $table->string('jenis_barang', 50);
            $table->date('tanggal_masuk');
            $table->date('tanggal_keluar')->nullable();
            $table->string('created_at_time', 100)->nullable();
            $table->timestamps();
        });

        // 2. Create orders table
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id_order', 50)->primary();
            $table->string('nama_pelanggan', 255);
            $table->string('id_barang', 50);
            $table->integer('jumlah_order');
            $table->string('status_order', 50);
            $table->date('tanggal_order');
            $table->string('created_at_time', 100)->nullable();
            $table->timestamps();

            // Foreign key relation
            $table->foreign('id_barang')
                  ->references('id_barang')
                  ->on('barang')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });

        // 3. Create session_logs table
        Schema::create('session_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('session_id');
            $table->string('role', 50);
            $table->string('timestamp', 50);
            $table->timestamps();
        });

        // Seed initial barang
        DB::table('barang')->insert([
            [
                'id_barang' => 'ELE-101',
                'nama_barang' => 'PixelMate Monitor',
                'jumlah_barang' => 594,
                'jenis_barang' => 'Electronics',
                'tanggal_masuk' => '2026-01-15',
                'tanggal_keluar' => null,
                'created_at_time' => '2026-06-11T13:00:00.000Z',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_barang' => 'ELE-102',
                'nama_barang' => 'FusionLink Router',
                'jumlah_barang' => 761,
                'jenis_barang' => 'Electronics',
                'tanggal_masuk' => '2026-02-10',
                'tanggal_keluar' => null,
                'created_at_time' => '2026-06-11T13:01:00.000Z',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_barang' => 'APP-103',
                'nama_barang' => 'VelvetAura Jacket',
                'jumlah_barang' => 0,
                'jenis_barang' => 'Apparel',
                'tanggal_masuk' => '2026-03-01',
                'tanggal_keluar' => '2026-03-10',
                'created_at_time' => '2026-06-11T13:02:00.000Z',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_barang' => 'APP-104',
                'nama_barang' => 'UrbanFlex Sneakers',
                'jumlah_barang' => 65,
                'jenis_barang' => 'Apparel',
                'tanggal_masuk' => '2026-04-12',
                'tanggal_keluar' => null,
                'created_at_time' => '2026-06-11T13:03:00.000Z',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_barang' => 'WEL-105',
                'nama_barang' => 'SilkSage Essential Oil',
                'jumlah_barang' => 165,
                'jenis_barang' => 'Wellness',
                'tanggal_masuk' => '2026-05-05',
                'tanggal_keluar' => null,
                'created_at_time' => '2026-06-11T13:04:00.000Z',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_barang' => 'HOM-106',
                'nama_barang' => 'CasaLuxe Desk Lamp',
                'jumlah_barang' => 8,
                'jenis_barang' => 'Home & Living',
                'tanggal_masuk' => '2026-05-20',
                'tanggal_keluar' => null,
                'created_at_time' => '2026-06-11T13:05:00.000Z',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Seed initial orders
        DB::table('orders')->insert([
            [
                'id_order' => 'ORD-1001',
                'nama_pelanggan' => 'John Doe',
                'id_barang' => 'ELE-101',
                'jumlah_order' => 5,
                'status_order' => 'Completed',
                'tanggal_order' => '2026-06-01',
                'created_at_time' => '2026-06-11T13:10:00.000Z',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_order' => 'ORD-1002',
                'nama_pelanggan' => 'Sarah Connor',
                'id_barang' => 'APP-104',
                'jumlah_order' => 2,
                'status_order' => 'Completed',
                'tanggal_order' => '2026-06-05',
                'created_at_time' => '2026-06-11T13:11:00.000Z',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_order' => 'ORD-1003',
                'nama_pelanggan' => 'Dicki',
                'id_barang' => 'ELE-101',
                'jumlah_order' => 1,
                'status_order' => 'Completed',
                'tanggal_order' => '2026-06-10',
                'created_at_time' => '2026-06-11T13:12:00.000Z',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Seed initial session logs
        DB::table('session_logs')->insert([
            [
                'session_id' => 5001,
                'role' => 'Admin',
                'timestamp' => '08:30:15',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_logs');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('barang');
    }
};
