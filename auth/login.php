<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi!';
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
            $error = 'Email atau password salah!';
        }
    }
}
?>

<style>
    /* ===== PAGE TRANSITION ===== */
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
        from { opacity: 0; }
        to   { opacity: 1; }
    }

    .auth-box {
        display: flex;
        width: 100%;
        max-width: 1000px;
        height: 520px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        animation: slideInLeft 0.45s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slideInLeft {
        from { transform: translateX(-40px); opacity: 0; }
        to   { transform: translateX(0);     opacity: 1; }
    }

    /* Slide out saat klik link ke register */
    .auth-box.slide-out {
        animation: slideOutRight 0.35s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }

    @keyframes slideOutRight {
        from { transform: translateX(0);    opacity: 1; }
        to   { transform: translateX(60px); opacity: 0; }
    }

    /* ===== FORM (kiri, lebih sempit) ===== */
    .auth-form {
        flex: 0.9;           /* lebih sempit dari register */
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
        margin-bottom: 1.5rem;
    }

    /* ===== INFO (kanan, lebih lebar) ===== */
    .auth-info {
        flex: 1.4;           /* lebih lebar dari register */
        background: linear-gradient(135deg, #6610f2, #0d6efd);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 2rem 2.5rem;
        text-align: center;
    }

    .auth-info .app-icon {
        font-size: 4.5rem;
        margin-bottom: 1rem;
        opacity: 0.95;
    }

    .auth-info h1 {
        font-size: 2.2rem;
        font-weight: 800;
        margin-bottom: 0.75rem;
        letter-spacing: -0.5px;
    }

    .auth-info p {
        font-size: 1rem;
        opacity: 0.85;
        line-height: 1.7;
        max-width: 300px;
    }

    .auth-info .divider {
        width: 50px;
        height: 3px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 10px;
        margin: 1.25rem auto;
    }

    .auth-info .no-account {
        margin-top: 2rem;
        font-size: 0.875rem;
        opacity: 0.85;
    }

    .auth-info .no-account a {
        color: white;
        font-weight: 600;
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .auth-info  { display: none; }
        .auth-form  { padding: 2rem 1.5rem; flex: 1; }
        .auth-box   { height: auto; }
    }
</style>

<div class="auth-wrapper">
    <div class="auth-box" id="authBox">

        <!-- Kiri: Form Login -->
        <div class="auth-form">
            <h4>Selamat Datang!</h4>
            <p class="subtitle">Masuk ke akunmu untuk melanjutkan</p>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control"
                               placeholder="kamu@gmail.com" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control"
                               placeholder="Masukkan Kata Sandi" id="password" required>
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePassword('password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>

            <p class="text-center mt-4 text-muted small">
                Belum punya akun?
                <a href="register.php" class="link-transition">Daftar di sini</a>
            </p>
        </div>

        <!-- Kanan: Info Aplikasi (lebih lebar) -->
        <div class="auth-info">
            <div class="app-icon">
                <i class="bi bi-check2-square"></i>
            </div>
            <h1>Todo App</h1>
            <div class="divider"></div>
            <p>Satu tempat untuk semua tugasmu.<br>Tetap terorganisir, tetap produktif.</p>
            <div class="no-account">
                Belum punya akun?<br>
                <a href="register.php" class="link-transition">Daftar sekarang →</a>
            </div>
        </div>

    </div>
</div>

<script>
    function togglePassword(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }

    // Efek slide out ke kanan saat klik link ke register
    document.querySelectorAll('.link-transition').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            const box  = document.getElementById('authBox');
            box.classList.add('slide-out');
            setTimeout(() => { window.location.href = href; }, 340);
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>