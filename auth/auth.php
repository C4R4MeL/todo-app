<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$loginError  = '';
$regErrors   = [];
$regSuccess  = '';
$old         = ['username' => '', 'email' => ''];

// ===== PROSES LOGIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $loginError = 'Email dan password wajib diisi!';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ../index.php');
            exit;
        } else {
            $loginError = 'Email atau password salah!';
        }
    }
}

// ===== PROSES REGISTER =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $old['username'] = $username;
    $old['email']    = $email;

    if (empty($username)) {
        $regErrors['username'] = 'Username wajib diisi.';
    } elseif (strlen($username) < 3) {
        $regErrors['username'] = 'Username minimal 3 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $regErrors['username'] = 'Username hanya boleh huruf, angka, dan underscore (_).';
    }

    if (empty($email)) {
        $regErrors['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $regErrors['email'] = 'Format email tidak valid.';
    }

    if (empty($password)) {
        $regErrors['password'] = 'Password wajib diisi.';
    } else {
        $pwErrors = [];
        if (strlen($password) < 8)             $pwErrors[] = 'minimal 8 karakter';
        if (!preg_match('/[A-Z]/', $password))  $pwErrors[] = 'minimal 1 huruf kapital';
        if (!preg_match('/[a-z]/', $password))  $pwErrors[] = 'minimal 1 huruf kecil';
        if (!preg_match('/[0-9]/', $password))  $pwErrors[] = 'minimal 1 angka';
        if (!preg_match('/[\W_]/', $password))  $pwErrors[] = 'minimal 1 karakter spesial';
        if (!empty($pwErrors)) {
            $regErrors['password'] = 'Password harus mengandung: ' . implode(', ', $pwErrors) . '.';
        }
    }

    if (empty($confirm)) {
        $regErrors['confirm'] = 'Konfirmasi password wajib diisi.';
    } elseif ($password !== $confirm) {
        $regErrors['confirm'] = 'Konfirmasi password tidak cocok.';
    }

    if (empty($regErrors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $regErrors['email'] = 'Email sudah terdaftar, gunakan email lain.';
        } else {
            $stmt2 = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt2, "s", $username);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_store_result($stmt2);

            if (mysqli_stmt_num_rows($stmt2) > 0) {
                $regErrors['username'] = 'Username sudah dipakai, coba username lain.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt3  = mysqli_prepare(
                    $conn,
                    "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt3, "sss", $username, $email, $hashed);
                if (mysqli_stmt_execute($stmt3)) {
                    $regSuccess = 'Registrasi berhasil! Silakan login.';
                    $old        = ['username' => '', 'email' => ''];
                } else {
                    $regErrors['general'] = 'Terjadi kesalahan server, coba lagi.';
                }
            }
        }
    }
}

// Tentukan panel mana yang aktif saat halaman dimuat
// Kalau ada error register atau dari link ?panel=register → tampilkan register
$activePanel = 'login';
if (!empty($regErrors) || $regSuccess || ($_GET['panel'] ?? '') === 'register') {
    $activePanel = 'register';
}
?>

<style>
    * {
        box-sizing: border-box;
    }

    body {
        overflow-x: hidden;
    }

    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f2f5;
        padding: 2rem 1rem;
    }

    /* ===== BOX UTAMA ===== */
    .auth-box {
        position: relative;
        display: flex;
        width: 100%;
        max-width: 1000px;
        height: 540px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }

    /* ===== PANEL FORM ===== */
    .forms-container {
        position: absolute;
        inset: 0;
        display: flex;
    }

    .form-panel {
        flex: 1;
        background: white;
        padding: 2rem 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow-y: auto;
        transition: transform 0.65s cubic-bezier(0.77, 0, 0.175, 1),
            opacity 0.65s ease;
    }

    /* Login: awalnya di kiri */
    .form-login {
        transform: translateX(0);
        opacity: 1;
        z-index: 2;
    }

    /* Register: awalnya tersembunyi di kanan */
    .form-register {
        transform: translateX(100%);
        opacity: 0;
        z-index: 1;
    }

    /* Saat register aktif */
    .auth-box.show-register .form-login {
        transform: translateX(-100%);
        opacity: 0;
        z-index: 1;
    }

    .auth-box.show-register .form-register {
        transform: translateX(0);
        opacity: 1;
        z-index: 2;
    }

    .form-panel h4 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.1rem;
    }

    .form-panel .subtitle {
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 1.25rem;
    }

    /* ===== PANEL INFO ===== */
    .info-panel {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 42%;
        /* lebar panel info */
        background: linear-gradient(135deg, #0d6efd, #6610f2);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 2.5rem 2rem;
        z-index: 10;
        transition: transform 0.65s cubic-bezier(0.77, 0, 0.175, 1);

        /* Awalnya di kanan (login aktif) */
        right: 0;
        transform: translateX(0);
        border-radius: 0 20px 20px 0;
    }

    /* Saat register aktif → info panel geser ke kiri */
    .auth-box.show-register .info-panel {
        transform: translateX(-138%);
        /* geser ke kiri */
        border-radius: 20px 0 0 20px;
    }

    .info-panel .app-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.95;
        transition: transform 0.65s cubic-bezier(0.77, 0, 0.175, 1);
    }

    .info-panel h1 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
    }

    .info-panel .divider {
        width: 45px;
        height: 3px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 10px;
        margin: 1rem auto;
    }

    /* Teks login (tampil saat login aktif) */
    .info-login-text,
    .info-register-text {
        transition: opacity 0.3s ease, transform 0.3s ease;
        position: absolute;
        width: 100%;
        padding: 0 2rem;
        text-align: center;
    }

    .info-login-text {
        opacity: 1;
        transform: translateY(0);
    }

    .info-register-text {
        opacity: 0;
        transform: translateY(10px);
    }

    .auth-box.show-register .info-login-text {
        opacity: 0;
        transform: translateY(-10px);
    }

    .auth-box.show-register .info-register-text {
        opacity: 1;
        transform: translateY(0);
    }

    .info-panel p {
        font-size: 0.95rem;
        opacity: 0.85;
        line-height: 1.7;
        max-width: 240px;
        margin: 0 auto;
    }

    .info-panel .switch-link {
        margin-top: 2rem;
        font-size: 0.85rem;
        opacity: 0.85;
    }

    .info-panel .switch-link a,
    .info-panel .switch-link button {
        color: white;
        font-weight: 600;
        text-decoration: underline;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.85rem;
        padding: 0;
    }

    /* ===== PASSWORD RULES ===== */
    #password-rules {
        columns: 2;
        margin-top: 0.25rem !important;
        margin-bottom: 0 !important;
    }

    #password-rules li {
        font-size: 0.72rem;
        line-height: 1.4;
        padding: 0;
        break-inside: avoid;
    }

    #password-rules li i {
        font-size: 0.68rem;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .info-panel {
            display: none;
        }

        .form-panel {
            padding: 2rem 1.5rem;
            width: 100%;
        }

        .auth-box {
            height: auto;
            min-height: 500px;
        }

        #password-rules {
            columns: 1;
        }

        .form-register {
            transform: translateX(0);
            opacity: 1;
            position: relative;
        }

        .form-login {
            position: relative;
        }
    }
</style>

<div class="auth-wrapper">
    <div class="auth-box <?= $activePanel === 'register' ? 'show-register' : '' ?>" id="authBox">

        <!-- ===== FORM LOGIN ===== -->
        <div class="form-panel form-login">
            <h4>Selamat Datang! 👋</h4>
            <p class="subtitle">Masuk ke akunmu untuk melanjutkan</p>

            <?php if ($loginError): ?>
                <div class="alert alert-danger py-2">
                    <i class="bi bi-exclamation-circle"></i> <?= $loginError ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control"
                            placeholder="email@kamu.com" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control"
                            placeholder="••••••••" id="loginPassword" required>
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePassword('loginPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>

            <p class="text-center mt-3 text-muted small">
                Belum punya akun?
                <button class="btn btn-link btn-sm p-0" onclick="switchPanel('register')">
                    Daftar di sini
                </button>
            </p>
        </div>

        <!-- ===== FORM REGISTER ===== -->
        <div class="form-panel form-register">
            <h4>Buat Akun Baru</h4>
            <p class="subtitle">Isi data di bawah untuk mulai menggunakan Todo App</p>

            <?php if (!empty($regErrors['general'])): ?>
                <div class="alert alert-danger py-2">
                    <i class="bi bi-exclamation-triangle"></i> <?= $regErrors['general'] ?>
                </div>
            <?php endif; ?>

            <?php if ($regSuccess): ?>
                <div class="alert alert-success py-2">
                    <i class="bi bi-check-circle"></i> <?= $regSuccess ?>
                    <button class="btn btn-link btn-sm p-0 alert-link"
                        onclick="switchPanel('login')">Login sekarang →</button>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="action" value="register">

                <!-- Username -->
                <div class="mb-2">
                    <label class="form-label fw-semibold mb-1">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username"
                            class="form-control <?= isset($regErrors['username']) ? 'is-invalid' : '' ?>"
                            placeholder="contoh: john_doe"
                            value="<?= htmlspecialchars($old['username']) ?>">
                        <?php if (isset($regErrors['username'])): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i> <?= $regErrors['username'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-2">
                    <label class="form-label fw-semibold mb-1">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email"
                            class="form-control <?= isset($regErrors['email']) ? 'is-invalid' : '' ?>"
                            placeholder="email@kamu.com"
                            value="<?= htmlspecialchars($old['email']) ?>">
                        <?php if (isset($regErrors['email'])): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i> <?= $regErrors['email'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-2">
                    <label class="form-label fw-semibold mb-1">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password"
                            class="form-control <?= isset($regErrors['password']) ? 'is-invalid' : '' ?>"
                            placeholder="••••••••" id="regPassword"
                            oninput="checkStrength(this.value)">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePassword('regPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                        <?php if (isset($regErrors['password'])): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i> <?= $regErrors['password'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-1">
                        <div class="progress" style="height: 4px;">
                            <div id="strength-bar" class="progress-bar" style="width:0%"></div>
                        </div>
                        <small id="strength-text"></small>
                    </div>
                    <ul class="list-unstyled mb-0" id="password-rules">
                        <li id="rule-length"> <i class="bi bi-circle text-muted"></i> Minimal 8 karakter</li>
                        <li id="rule-upper"> <i class="bi bi-circle text-muted"></i> Minimal 1 huruf kapital</li>
                        <li id="rule-lower"> <i class="bi bi-circle text-muted"></i> Minimal 1 huruf kecil</li>
                        <li id="rule-number"> <i class="bi bi-circle text-muted"></i> Minimal 1 angka</li>
                        <li id="rule-special"> <i class="bi bi-circle text-muted"></i> Minimal 1 karakter spesial</li>
                    </ul>
                </div>

                <!-- Konfirmasi Password -->
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-1">Konfirmasi Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password"
                            class="form-control <?= isset($regErrors['confirm']) ? 'is-invalid' : '' ?>"
                            placeholder="••••••••" id="confirmPassword"
                            oninput="checkMatch()">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePassword('confirmPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                        <?php if (isset($regErrors['confirm'])): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i> <?= $regErrors['confirm'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <small id="match-text" class="mt-1 d-block"></small>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-person-plus"></i> Daftar Sekarang
                </button>
            </form>
        </div>

        <!-- ===== PANEL INFO (bergeser) ===== -->
        <div class="info-panel" id="infoPanel">

            <div class="app-icon">
                <i class="bi bi-check2-square"></i>
            </div>
            <h1>Todo App</h1>
            <div class="divider"></div>

            <!-- Teks saat login aktif -->
            <div class="info-login-text">
                <p>Satu tempat untuk semua tugasmu. Tetap terorganisir, tetap produktif.</p>
                <div class="switch-link mt-3">
                    Belum punya akun?<br>
                    <button onclick="switchPanel('register')">Daftar sekarang →</button>
                </div>
            </div>

            <!-- Teks saat register aktif -->
            <div class="info-register-text">
                <p>Sudah punya akun? Masuk dan lanjutkan produktivitasmu hari ini.</p>
                <div class="switch-link mt-3">
                    Sudah punya akun?<br>
                    <button onclick="switchPanel('login')">Login di sini →</button>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    function switchPanel(target) {
        const box = document.getElementById('authBox');
        if (target === 'register') {
            box.classList.add('show-register');
        } else {
            box.classList.remove('show-register');
        }
    }

    function togglePassword(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }

    function checkStrength(val) {
        const rules = {
            'rule-length': val.length >= 8,
            'rule-upper': /[A-Z]/.test(val),
            'rule-lower': /[a-z]/.test(val),
            'rule-number': /[0-9]/.test(val),
            'rule-special': /[\W_]/.test(val),
        };
        let passed = 0;
        for (const [id, ok] of Object.entries(rules)) {
            const el = document.getElementById(id);
            el.className = ok ? 'text-success' : 'text-danger';
            el.querySelector('i').className = ok ?
                'bi bi-check-circle-fill text-success' :
                'bi bi-x-circle text-danger';
            if (ok) passed++;
        }
        const bar = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');
        if (val === '') {
            bar.style.width = '0%';
            text.textContent = '';
            document.querySelectorAll('#password-rules li').forEach(li => {
                li.className = 'text-muted';
                li.querySelector('i').className = 'bi bi-circle text-muted';
            });
            return;
        }
        bar.style.width = (passed / 5 * 100) + '%';
        if (passed <= 2) {
            bar.className = 'progress-bar bg-danger';
            text.innerHTML = '<span class="text-danger">Lemah</span>';
        } else if (passed <= 4) {
            bar.className = 'progress-bar bg-warning';
            text.innerHTML = '<span class="text-warning">Sedang</span>';
        } else {
            bar.className = 'progress-bar bg-success';
            text.innerHTML = '<span class="text-success">Kuat 💪</span>';
        }
        checkMatch();
    }

    function checkMatch() {
        const pw = document.getElementById('regPassword').value;
        const confirm = document.getElementById('confirmPassword').value;
        const text = document.getElementById('match-text');
        if (confirm === '') {
            text.textContent = '';
            return;
        }
        text.innerHTML = pw === confirm ?
            '<i class="bi bi-check-circle-fill text-success"></i> <span class="text-success">Password cocok</span>' :
            '<i class="bi bi-x-circle text-danger"></i> <span class="text-danger">Password tidak cocok</span>';
    }
</script>

<?php require_once '../includes/footer.php'; ?>