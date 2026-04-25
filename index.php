<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once 'config/db.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Ambil semua task milik user
$tasks = mysqli_query($conn, 
    "SELECT * FROM tasks WHERE user_id='$user_id' ORDER BY 
     FIELD(priority, 'high', 'medium', 'low'), deadline ASC"
);

// Fungsi badge warna prioritas
function priorityBadge($priority) {
    $badges = [
        'high'   => '<span class="badge bg-danger">🔴 High</span>',
        'medium' => '<span class="badge bg-warning text-dark">🟡 Medium</span>',
        'low'    => '<span class="badge bg-success">🟢 Low</span>',
    ];
    return $badges[$priority] ?? '';
}
?>

<div class="container mt-4">

  <!-- Navbar -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="bi bi-check2-square"></i> Todo App</h3>
    <div>
      <span class="me-3">👤 <?= $_SESSION['username'] ?></span>
      <a href="auth/logout.php" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </div>

  <!-- Notifikasi sukses/error -->
  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= $_GET['success'] ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= $_GET['error'] ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Tombol Tambah Task -->
  <div class="mb-3">
    <a href="tasks/create.php" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> Tambah Task
    </a>
  </div>

  <!-- Daftar Task -->
  <?php if (mysqli_num_rows($tasks) === 0): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-inbox fs-1"></i>
      <p class="mt-2">Belum ada task. Yuk tambah task pertamamu!</p>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php while ($task = mysqli_fetch_assoc($tasks)): ?>
        <?php
          $isCompleted = $task['status'] === 'completed';
          $isOverdue   = $task['deadline'] && $task['deadline'] < date('Y-m-d') && !$isCompleted;
          $cardClass   = $isCompleted ? 'border-success' : ($isOverdue ? 'border-danger' : '');
        ?>
        <div class="col-md-6">
          <div class="card shadow-sm <?= $cardClass ?>">
            <div class="card-body">

              <!-- Judul & Status -->
              <div class="d-flex justify-content-between align-items-start">
                <h5 class="card-title <?= $isCompleted ? 'text-decoration-line-through text-muted' : '' ?>">
                  <?= htmlspecialchars($task['title']) ?>
                </h5>
                <?= priorityBadge($task['priority']) ?>
              </div>

              <!-- Deskripsi -->
              <?php if ($task['description']): ?>
                <p class="card-text text-muted small"><?= htmlspecialchars($task['description']) ?></p>
              <?php endif; ?>

              <!-- Deadline -->
              <?php if ($task['deadline']): ?>
                <p class="card-text small <?= $isOverdue ? 'text-danger fw-bold' : 'text-muted' ?>">
                  <i class="bi bi-calendar"></i> 
                  Deadline: <?= date('d M Y', strtotime($task['deadline'])) ?>
                  <?= $isOverdue ? '⚠️ Terlambat!' : '' ?>
                </p>
              <?php endif; ?>

              <!-- Tombol Aksi -->
              <div class="d-flex gap-2 mt-2">
                <a href="tasks/update_status.php?id=<?= $task['id'] ?>" 
                   class="btn btn-sm <?= $isCompleted ? 'btn-outline-secondary' : 'btn-success' ?>">
                  <i class="bi bi-<?= $isCompleted ? 'arrow-counterclockwise' : 'check-lg' ?>"></i>
                  <?= $isCompleted ? 'Batal Selesai' : 'Selesai' ?>
                </a>
                <a href="tasks/edit.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-warning">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="tasks/delete.php?id=<?= $task['id'] ?>" 
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Yakin mau hapus task ini?')">
                  <i class="bi bi-trash"></i> Hapus
                </a>
              </div>

            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>