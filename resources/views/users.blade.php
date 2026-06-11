@extends('layouts.app')

@section('title', 'Pendaftaran Akun')
@section('page_title', 'Warehouse User Management & Registrasi')

@section('content')
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    
    <!-- 1. Pendaftaran User Baru Card -->
    <div class="card" style="background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-sm); padding: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <i data-lucide="user-plus" style="color: var(--primary); width: 20px; height: 20px;"></i>
            Daftarkan Akun Baru
        </h3>

        <!-- Callout validation error -->
        <div id="userValidationErrorCallout" class="error-callout" style="display: none; background-color: var(--outofstock-bg); border: 1px solid var(--outofstock-border); border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; color: var(--outofstock); font-size: 0.8rem; align-items: flex-start; gap: 8px;">
            <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;"></i>
            <ul id="userErrorList" style="margin: 0; padding-left: 18px;"></ul>
        </div>

        <form id="registerUserForm">
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">USERNAME</label>
                <input type="text" id="regUsername" class="login-input" style="padding: 10px 12px 10px 12px;" placeholder="Masukkan username baru..." required>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">ROLE HAK AKSES</label>
                <select id="regRole" class="login-input" style="padding: 10px 12px 10px 12px;" required>
                    <option value="Gudang" selected>Gudang (Staff)</option>
                    <option value="Manajer">Manajer (Manager)</option>
                    <option value="Admin">Admin (Administrator)</option>
                </select>
                <small style="display: block; font-size: 0.7rem; color: var(--text-dimmed); margin-top: 6px;">
                    * Password default untuk akun baru adalah: <strong style="color: var(--primary);">admin123</strong>
                </small>
            </div>

            <button type="submit" class="btn" style="width: 100%; background-color: var(--primary); color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;">
                <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i>
                Daftarkan User
            </button>
        </form>
    </div>

    <!-- 2. Daftar User Terdaftar Card -->
    <div class="card" style="background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-sm); padding: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <i data-lucide="users" style="color: var(--primary); width: 20px; height: 20px;"></i>
            Akun Terdaftar di Sistem
        </h3>

        <ul id="registeredUsersList" style="list-style: none; display: flex; flex-direction: column; gap: 10px; padding: 0;">
            @foreach($users as $u)
                @php
                    $roleClass = strtolower($u->role);
                    $isSelf = $u->id === Auth::id();
                @endphp
                <li class="user-list-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background-color: var(--bg-input); border-radius: 10px; border: 1px solid var(--border-color);">
                    <span class="username" style="font-weight: 700; color: var(--text-main); font-size: 0.85rem;">{{ '@' . $u->username }}</span>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="badge" style="background-color: var(--primary-glow); color: var(--primary); border: 1px solid rgba(139, 92, 246, 0.3); padding: 3px 8px; font-size: 0.7rem; border-radius: 6px; font-weight: 600;">{{ $u->role }}</span>
                        
                        @if(!$isSelf)
                            <button class="delete-user-btn" data-username="{{ $u->username }}" title="Hapus Akun" style="background: none; border: none; cursor: pointer; color: var(--outofstock); padding: 4px; display: flex; align-items: center;">
                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                            </button>
                        @else
                            <span style="font-size:0.7rem; color:var(--text-dimmed); font-style:italic;">(Aktif)</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // Register user handler via AJAX
    const form = document.getElementById('registerUserForm');
    const errorCallout = document.getElementById('userValidationErrorCallout');
    const errorList = document.getElementById('userErrorList');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorCallout.style.display = 'none';

        const payload = {
            username: document.getElementById('regUsername').value,
            role: document.getElementById('regRole').value
        };

        try {
            const res = await fetch('{{ route("users.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const result = await res.json();
            if (res.ok && result.success) {
                window.location.reload();
            } else {
                errorList.innerHTML = '';
                const errors = result.errors || ['Gagal mendaftarkan user.'];
                errors.forEach(err => {
                    const li = document.createElement('li');
                    li.textContent = err;
                    errorList.appendChild(li);
                });
                errorCallout.style.display = 'flex';
            }
        } catch (err) {
            console.error(err);
            errorList.innerHTML = '<li>Gagal menghubungi server.</li>';
            errorCallout.style.display = 'flex';
        }
    });

    // Delete user handler
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const username = btn.getAttribute('data-username');
            if (!confirm(`Apakah Anda yakin ingin menghapus akun @${username}?`)) {
                return;
            }

            try {
                const res = await fetch(`/users/${username}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const result = await res.json();
                if (res.ok && result.success) {
                    window.location.reload();
                } else {
                    alert(result.errors ? result.errors.join('\n') : 'Gagal menghapus user.');
                }
            } catch (err) {
                console.error(err);
                alert('Kesalahan koneksi ke server.');
            }
        });
    });
</script>
@endsection
