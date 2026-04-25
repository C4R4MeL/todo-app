<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/auth.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$error = '';
$id      = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Ambil data task, pastikan milik user yang login
$query = mysqli_query($conn, "SELECT * FROM tasks WHERE id='$id' AND user_id='$user_id'");
$task  = mysqli_fetch_assoc($query);
// Cegah edit task yang sudah selesai
if ($task['status'] === 'completed') {
    header('Location: ../index.php?error=Task yang sudah selesai tidak dapat diedit. Batalkan dulu statusnya.');
    exit;
}

if (!$task) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority    = $_POST['priority'];
    $deadline    = $_POST['deadline'];

    if (empty($title)) {
        $error = 'Judul task wajib diisi!';
    } else {
        $update = "UPDATE tasks SET 
                    title='$title', 
                    description='$description', 
                    priority='$priority', 
                    deadline='$deadline'
                   WHERE id='$id' AND user_id='$user_id'";
        if (mysqli_query($conn, $update)) {
            header('Location: ../index.php?success=Task berhasil diupdate!');
            exit;
        } else {
            $error = 'Gagal mengupdate task!';
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="mb-4"><i class="bi bi-pencil"></i> Edit Task</h4>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Judul Task <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control"
                                value="<?= $task['title'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3"><?= $task['description'] ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prioritas</label>
                            <select name="priority" class="form-select">
                                <option value="low" <?= $task['priority'] === 'low'    ? 'selected' : '' ?>>🟢 Low</option>
                                <option value="medium" <?= $task['priority'] === 'medium' ? 'selected' : '' ?>>🟡 Medium</option>
                                <option value="high" <?= $task['priority'] === 'high'   ? 'selected' : '' ?>>🔴 High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deadline</label>
                            <input type="date" name="deadline" class="form-control"
                                value="<?= $task['deadline'] ?>">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                            <a href="../index.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>