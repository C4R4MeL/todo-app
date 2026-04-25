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

    // Validasi username
    if (empty($username)) {
        $errors['username'] = 'Username wajib diisi.';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username minimal 3 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username hanya boleh huruf, angka, dan underscore (_).';
    }

    // Validasi email
    if (empty($email)) {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    // Validasi password
    if (empty($password)) {
        $errors['password'] = 'Password wajib diisi.';
    } else {
        $pwErrors = [];
        if (strlen($password) < 8)            $pwErrors[] = 'minimal 8 karakter';
        if (!preg_match('/[A-Z]/', $password)) $pwErrors[] = 'minimal 1 huruf kapital';
        if (!preg_match('/[a-z]/', $password)) $pwErrors[] = 'minimal 1 huruf kecil';
        if (!preg_match('/[0-9]/', $password)) $pwErrors[] = 'minimal 1 angka';
        if (!preg_match('/[\W_]/', $password)) $pwErrors[] = 'minimal 1 karakter spesial';
        if (!empty($pwErrors)) {
            $errors['password'] = 'Password harus mengandung: ' . implode(', ', $pwErrors) . '.';
        }
    }

    // Validasi konfirmasi password
    if (empty($confirm)) {
        $errors['confirm'] = 'Konfirmasi password wajib diisi.';
    } elseif ($password !== $confirm) {
        $errors['confirm'] = 'Konfirmasi password tidak cocok.';
    }

    // Kalau tidak ada error, simpan ke database
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

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card auth-card shadow-lg">

                <div class="auth-header">
                    <i class="bi bi-check2-square fs-1"></i>
                    <h4 class="mt-2 mb-0">Todo App</h4>
                    <p class="mb-0 opacity-75">Buat akun baru</p>
                </div>

                <div class="card-body p-4">

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
                                    placeholder="contoh: azlan_123"
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
                                    placeholder="kamu@gmail.com"
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
                                    placeholder="Masukkan Kata Sandi" id="password"
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
                                <div class="progress" style="height: 6px;">
                                    <div id="strength-bar" class="progress-bar" style="width: 0%"></div>
                                </div>
                                <small id="strength-text" class="text-muted"></small>
                            </div>

                            <!-- Checklist syarat — selalu tampil -->
                            <ul class="list-unstyled mt-2 small" id="password-rules">
                                <li id="rule-length"> <i class="bi bi-circle text-muted"></i> Minimal 8 karakter</li>
                                <li id="rule-upper"> <i class="bi bi-circle text-muted"></i> Minimal 1 huruf kapital</li>
                                <li id="rule-lower"> <i class="bi bi-circle text-muted"></i> Minimal 1 huruf kecil</li>
                                <li id="rule-number"> <i class="bi bi-circle text-muted"></i> Minimal 1 angka</li>
                                <li id="rule-special"> <i class="bi bi-circle text-muted"></i> Minimal 1 karakter spesial (!@#$%)</li>
                            </ul>
                        </div>

                        <!-- Konfirmasi Password -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Konfirmasi Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="confirm_password"
                                    class="form-control <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                                    placeholder="Masukkan Kata Sandi" id="confirm_password"
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
                            <!-- Info cocok/tidak — hanya muncul saat user mengetik -->
                            <small id="match-text" class="mt-1 d-block"></small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mt-1">
                            <i class="bi bi-person-plus"></i> Daftar
                        </button>
                    </form>

                    <p class="text-center mt-3 text-muted small">
                        Sudah punya akun? <a href="login.php">Login di sini</a>
                    </p>
                </div>
            </div>
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
            if (ok) {
                el.className = 'text-success';
                el.querySelector('i').className = 'bi bi-check-circle-fill text-success';
            } else {
                el.className = 'text-danger';
                el.querySelector('i').className = 'bi bi-x-circle text-danger';
            }
            if (ok) passed++;
        }

        const bar = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');
        bar.style.width = (passed / 5 * 100) + '%';

        if (val === '') {
            bar.style.width = '0%';
            text.textContent = '';
            // Reset ke icon netral kalau kosong
            document.querySelectorAll('#password-rules li i').forEach(i => {
                i.className = 'bi bi-circle text-muted';
            });
            document.querySelectorAll('#password-rules li').forEach(li => {
                li.className = 'text-muted';
            });
            return;
        }

        if (passed <= 2) {
            bar.className = 'progress-bar bg-danger';
            text.innerHTML = '<span class="text-danger">Lemah</span>';
        } else if (passed <= 4) {
            bar.className = 'progress-bar bg-warning';
            text.innerHTML = '<span class="text-warning">Sedang</span>';
        } else {
            bar.className = 'progress-bar bg-success';
            text.innerHTML = '<span class="text-success">PasswordKuat</span>';
        }

        checkMatch();
    }

    function checkMatch() {
        const pw = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        const text = document.getElementById('match-text');

        // Hanya tampil kalau user sudah mulai mengetik di field konfirmasi
        if (confirm === '') {
            text.textContent = '';
            return;
        }

        if (pw === confirm) {
            text.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> <span class="text-success">Password cocok</span>';
        } else {
            text.innerHTML = '<i class="bi bi-x-circle text-danger"></i> <span class="text-danger">Password tidak cocok</span>';
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>