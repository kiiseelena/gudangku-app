<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display the user management page.
     */
    public function index()
    {
        // Restrict to Admin
        if (Auth::user()->role !== 'Admin') {
            abort(403, 'Akses ditolak! Hanya role Admin yang dapat mengakses halaman manajemen user.');
        }

        $users = User::orderBy('username', 'asc')->get();
        return view('users', compact('users'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        // Restrict to Admin
        if (Auth::user()->role !== 'Admin') {
            return response()->json([
                'success' => false,
                'errors' => ['Akses ditolak! Hanya role Admin yang dapat membuat user baru.']
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'role' => ['required', Rule::in(['Admin', 'Manajer', 'Gudang'])]
        ], [
            'username.required' => "Username wajib diisi.",
            'username.unique' => "Username sudah terdaftar di database.",
            'role.required' => "Role wajib ditentukan.",
            'role.in' => "Role harus salah satu dari: Admin, Manajer, atau Gudang."
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all()
            ], 400);
        }

        $username = trim($request->input('username'));
        
        $user = User::create([
            'username' => $username,
            'password' => Hash::make('admin123'), // Default password
            'role' => $request->input('role')
        ]);

        return response()->json([
            'success' => true,
            'data' => $user
        ], 201);
    }

    /**
     * Remove the specified user.
     */
    public function destroy($username)
    {
        // Restrict to Admin
        if (Auth::user()->role !== 'Admin') {
            return response()->json([
                'success' => false,
                'errors' => ['Akses ditolak! Hanya role Admin yang dapat menghapus user.']
            ], 403);
        }

        $user = User::where('username', $username)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'errors' => ['Data user tidak ditemukan.']
            ], 404);
        }

        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'errors' => ['Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.']
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "Akun @{$username} berhasil dihapus."
        ]);
    }
}
