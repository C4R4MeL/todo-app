<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$errors  = [];
$success = '';
$old     = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $old['username'] = $username;
    $old['email']    = $email;

    if (empty($username)) {
        $errors['username'] = 'Username wajib diisi.';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username minimal 3 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username hanya boleh huruf, angka, dan underscore (_).';
    }

    if (empty($email)) {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password wajib diisi.';
    } else {
        $pwErrors = [];
        if (strlen($password) < 8)             $pwErrors[] = 'minimal 8 karakter';
        if (!preg_match('/[A-Z]/', $password))  $pwErrors[] = 'minimal 1 huruf kapital';
        if (!preg_match('/[a-z]/', $password))  $pwErrors[] = 'minimal 1 huruf kecil';
        if (!preg_match('/[0-9]/', $password))  $pwErrors[] = 'minimal 1 angka';
        if (!preg_match('/[\W_]/', $password))  $pwErrors[] = 'minimal 1 karakter spesial';
        if (!empty($pwErrors)) {
            $errors['password'] = 'Password harus mengandung: ' . implode(', ', $pwErrors) . '.';
        }
    }

    if (empty($confirm)) {
        $errors['confirm'] = 'Konfirmasi password wajib diisi.';
    } elseif ($password !== $confirm) {
        $errors['confirm'] = 'Konfirmasi password tidak cocok.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors['email'] = 'Email sudah terdaftar, gunakan email lain.';
        } else {
            $stmt2 = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt2, "s", $username);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_store_result($stmt2);

            if (mysqli_stmt_num_rows($stmt2) > 0) {
                $errors['username'] = 'Username sudah dipakai, coba username lain.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt3  = mysqli_prepare(
                    $conn,
                    "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt3, "sss", $username, $email, $hashed);
                if (mysqli_stmt_execute($stmt3)) {
                    $success = 'Registrasi berhasil! Silakan login.';
                    $old     = ['username' => '', 'email' => ''];
                } else {
                    $errors['general'] = 'Terjadi kesalahan server, coba lagi.';
                }
            }
        }
    }
}
?>

<style>
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
        animation: pageFadeIn 0.4s ease;
    }

    @keyframes pageFadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .auth-box {
        display: flex;
        width: 100%;
        max-width: 1000px;
        height: 520px;
        /* sama dengan login */
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        animation: slideInRight 0.45s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slideInRight {
        from {
            transform: translateX(40px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .auth-box.slide-out {
        animation: slideOutLeft 0.35s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }

    @keyframes slideOutLeft {
        from {
            transform: translateX(0);
            opacity: 1;
        }

        to {
            transform: translateX(-60px);
            opacity: 0;
        }
    }

    /* Info (kiri, lebih sempit) */
    .auth-info {
        flex: 0.75;
        /* lebih sempit dari login */
        background: linear-gradient(135deg, #0d6efd, #6610f2);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 2rem 1.75rem;
        text-align: center;
    }

    .auth-info .app-icon {
        font-size: 3.5rem;
        margin-bottom: 0.75rem;
        opacity: 0.95;
    }

    .auth-info h1 {
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
    }

    .auth-info p {
        font-size: 0.9rem;
        opacity: 0.85;
        line-height: 1.6;
        max-width: 220px;
    }

    .auth-info .divider {
        width: 40px;
        height: 3px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 10px;
        margin: 1rem auto;
    }

    .auth-info .already-link {
        margin-top: 1.5rem;
        font-size: 0.8rem;
        opacity: 0.85;
    }

    .auth-info .already-link a {
        color: white;
        font-weight: 600;
        text-decoration: underline;
    }

    /* Form (kanan, lebih lebar) */
    .auth-form {
        flex: 1.4;
        /* lebih lebar dari login */
        background: white;
        padding: 2rem 2.5rem;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .auth-form h4 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.1rem;
    }

    .auth-form .subtitle {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    #password-rules {
        columns: 2;
        margin-top: 0.25rem !important;
        margin-bottom: 0 !important;
    }

    #password-rules li {
        font-size: 0.75rem;
        line-height: 1.4;
        padding: 0;
        break-inside: avoid;
    }

    #password-rules li i {
        font-size: 0.7rem;
    }

    @media (max-width: 768px) {
        .auth-info {
            display: none;
        }

        .auth-form {
            padding: 2rem 1.5rem;
            flex: 1;
        }

        .auth-box {
            height: auto;
        }

        #password-rules {
            columns: 1;
        }
    }
</style>

<div class="auth-wrapper">
    <div class="auth-box" id="authBox">

        <!-- Kiri: Info Aplikasi -->
        <div class="auth-info">
            <div class="app-icon">
                <i class="bi bi-check2-square"></i>
            </div>
            <h1>Todo App</h1>
            <div class="divider"></div>
            <p>Kelola tugasmu dengan mudah, tetap produktif setiap harinya.</p>
            <div class="already-link">
                Sudah punya akun?<br>
                <a href="login.php">Login di sini →</a>
            </div>
        </div>

        <!-- Kanan: Form Register -->
        <div class="auth-form">
            <h4>Buat Akun Baru</h4>
            <p class="subtitle">Isi data di bawah untuk mulai menggunakan Todo App</p>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?= $errors['general'] ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?= $success ?>
                    <a href="login.php" class="alert-link ms-1">Login sekarang →</a>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>

                <!-- Username -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username"
                            class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                            placeholder="contoh: john_doe"
                            value="<?= htmlspecialchars($old['username']) ?>">
                        <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i> <?= $errors['username'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email"
                            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            placeholder="email@kamu.com"
                            value="<?= htmlspecialchars($old['email']) ?>">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i> <?= $errors['email'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password"
                            class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                            placeholder="••••••••" id="password"
                            oninput="checkStrength(this.value)">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePassword('password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i> <?= $errors['password'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Strength bar — selalu tampil -->
                    <div class="mt-2">
                        <div class="progress" style="height: 5px;">
                            <div id="strength-bar" class="progress-bar" style="width:0%"></div>
                        </div>
                        <small id="strength-text" class="text-muted"></small>
                    </div>

                    <!-- Checklist — selalu tampil -->
                    <ul class="list-unstyled mt-2 small mb-0" id="password-rules">
                        <li id="rule-length"> <i class="bi bi-circle text-muted"></i> Minimal 8 karakter</li>
                        <li id="rule-upper"> <i class="bi bi-circle text-muted"></i> Minimal 1 huruf kapital</li>
                        <li id="rule-lower"> <i class="bi bi-circle text-muted"></i> Minimal 1 huruf kecil</li>
                        <li id="rule-number"> <i class="bi bi-circle text-muted"></i> Minimal 1 angka</li>
                        <li id="rule-special"> <i class="bi bi-circle text-muted"></i> Minimal 1 karakter spesial (!@#$%)</li>
                    </ul>
                </div>

                <!-- Konfirmasi Password -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Konfirmasi Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password"
                            class="form-control <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                            placeholder="••••••••" id="confirm_password"
                            oninput="checkMatch()">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePassword('confirm_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                        <?php if (isset($errors['confirm'])): ?>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i> <?= $errors['confirm'] ?>
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

    </div>
</div>

<script>
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
        const pw = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        const text = document.getElementById('match-text');
        if (confirm === '') {
            text.textContent = '';
            return;
        }
        text.innerHTML = pw === confirm ?
            '<i class="bi bi-check-circle-fill text-success"></i> <span class="text-success">Password cocok</span>' :
            '<i class="bi bi-x-circle text-danger"></i> <span class="text-danger">Password tidak cocok</span>';
    }

    // Efek slide out ke kiri saat klik link ke login
    document.querySelectorAll('.link-transition').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            const box = document.getElementById('authBox');
            box.classList.add('slide-out');
            setTimeout(() => {
                window.location.href = href;
            }, 340);
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>