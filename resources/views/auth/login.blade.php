<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gudang.io Inventory</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- CSS stylesheet -->
    <link rel="stylesheet" href="/styles.css">
    <style>
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-app);
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background-color: var(--bg-main);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 40px 30px;
            box-shadow: var(--shadow-lg);
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo {
            display: inline-flex;
            background: var(--primary-glow);
            padding: 12px;
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.3);
            margin-bottom: 15px;
            color: var(--primary);
        }
        .login-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 5px;
        }
        .login-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .input-group {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dimmed);
            pointer-events: none;
            width: 18px;
            height: 18px;
        }
        .login-input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            background-color: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-main);
            font-size: 0.9rem;
            transition: var(--transition);
        }
        .login-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
            background-color: var(--bg-main);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        .btn-login:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .error-callout {
            background-color: var(--outofstock-bg);
            border: 1px solid var(--outofstock-border);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            color: var(--outofstock);
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i data-lucide="package-open" style="width: 28px; height: 28px;"></i>
                </div>
                <h2 class="login-title">Gudang.io</h2>
                <p class="login-subtitle">Sistem Manajemen Inventory & Transaksi</p>
            </div>

            @if ($errors->any())
                <div class="error-callout">
                    <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-group">
                        <i data-lucide="user" class="input-icon"></i>
                        <input type="text" id="username" name="username" class="login-input" placeholder="Masukkan username..." value="{{ old('username') }}" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <i data-lucide="lock" class="input-icon"></i>
                        <input type="password" id="password" name="password" class="login-input" placeholder="Masukkan password..." required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Masuk
                    <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
