<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/auth.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority    = $_POST['priority'];
    $deadline    = $_POST['deadline'];
    $user_id     = $_SESSION['user_id'];

    if (empty($title)) {
        $error = 'Judul task wajib diisi!';
    } else {
        $query = "INSERT INTO tasks (user_id, title, description, priority, deadline) 
                  VALUES ('$user_id', '$title', '$description', '$priority', '$deadline')";
        if (mysqli_query($conn, $query)) {
            header('Location: ../index.php?success=Task berhasil ditambahkan!');
            exit;
        } else {
            $error = 'Gagal menambahkan task!';
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="mb-4"><i class="bi bi-plus-circle"></i> Tambah Task</h4>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Judul Task <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-pencil-square"></i></span>
                                <input type="text" name="title" id="titleInput"
                                    class="form-control <?= isset($error) && empty($_POST['title'] ?? '') ? 'is-invalid' : '' ?>"
                                    placeholder="Contoh: Belajar PHP"
                                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                    oninput="checkTitle(this)">
                                <div class="invalid-feedback" id="titleError">
                                    <i class="bi bi-exclamation-circle"></i> Judul task wajib diisi.
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3"
                                placeholder="Deskripsi task (opsional)"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prioritas</label>
                            <select name="priority" class="form-select">
                                <option value="low">🟢 Low</option>
                                <option value="medium" selected>🟡 Medium</option>
                                <option value="high">🔴 High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deadline</label>
                            <input type="date" name="deadline" class="form-control">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus"></i> Tambah Task
                            </button>
                            <a href="../index.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function checkTitle(input) {
        const errorEl = document.getElementById('titleError');
        if (input.value.trim() === '') {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        }
    }

    // Validasi saat klik submit
    document.querySelector('form').addEventListener('submit', function(e) {
        const titleInput = document.getElementById('titleInput');
        if (titleInput.value.trim() === '') {
            e.preventDefault(); // tahan submit
            titleInput.classList.add('is-invalid');
            titleInput.focus(); // fokus ke field
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>