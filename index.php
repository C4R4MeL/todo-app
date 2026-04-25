<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once 'config/db.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Ambil semua task
$tasks = mysqli_query(
    $conn,
    "SELECT * FROM tasks WHERE user_id='$user_id' 
     ORDER BY FIELD(priority,'high','medium','low'), deadline ASC"
);

// Hitung statistik
$total     = mysqli_num_rows($tasks);
$completed = mysqli_num_rows(mysqli_query(
    $conn,
    "SELECT id FROM tasks WHERE user_id='$user_id' AND status='completed'"
));
$pending   = $total - $completed;
$overdue   = mysqli_num_rows(mysqli_query(
    $conn,
    "SELECT id FROM tasks WHERE user_id='$user_id' AND status='pending' 
     AND deadline < CURDATE() AND deadline IS NOT NULL"
));

// Reset pointer
mysqli_data_seek($tasks, 0);

function priorityBadge($priority)
{
    $badges = [
        'high'   => '<span class="badge bg-danger badge-priority">🔴 High</span>',
        'medium' => '<span class="badge bg-warning text-dark badge-priority">🟡 Medium</span>',
        'low'    => '<span class="badge bg-success badge-priority">🟢 Low</span>',
    ];
    return $badges[$priority] ?? '';
}
?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-check2-square"></i> Todo App
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-white">
                <i class="bi bi-person-circle"></i> <?= $_SESSION['username'] ?>
            </span>
            <a href="auth/logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <!-- Notifikasi -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle"></i> <?= $_GET['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="bi bi-x-circle"></i> <?= $_GET['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-primary"><?= $total ?></div>
                <div class="text-muted small">Total Task</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-warning"><?= $pending ?></div>
                <div class="text-muted small">Pending</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-success"><?= $completed ?></div>
                <div class="text-muted small">Selesai</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm text-center p-3">
                <div class="fs-2 fw-bold text-danger"><?= $overdue ?></div>
                <div class="text-muted small">Terlambat</div>
            </div>
        </div>
    </div>

    <!-- Header + Tombol Tambah -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-semibold">📋 Daftar Task</h5>
        <a href="tasks/create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Task
        </a>
    </div>

    <!-- Daftar Task -->
    <?php if ($total === 0): ?>
        <div class="empty-state shadow-sm">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
            <h5 class="mt-3 text-muted">Belum ada task nih!</h5>
            <p class="text-muted">Yuk mulai tambah task pertamamu 🚀</p>
            <a href="tasks/create.php" class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle"></i> Tambah Sekarang
            </a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php while ($task = mysqli_fetch_assoc($tasks)): ?>
                <?php
                $isCompleted = $task['status'] === 'completed';
                $isOverdue   = $task['deadline'] && $task['deadline'] < date('Y-m-d') && !$isCompleted;
                $cardClass   = $isCompleted ? 'completed' : 'priority-' . $task['priority'];
                ?>
                <div class="col-md-6">
                    <div class="card task-card shadow-sm <?= $cardClass ?>">
                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-semibold mb-0 <?= $isCompleted ? 'text-decoration-line-through text-muted' : '' ?>">
                                    <?= htmlspecialchars($task['title']) ?>
                                </h6>
                                <?= priorityBadge($task['priority']) ?>
                            </div>

                            <?php if ($task['description']): ?>
                                <p class="text-muted small mb-2"><?= htmlspecialchars($task['description']) ?></p>
                            <?php endif; ?>

                            <?php if ($task['deadline']): ?>
                                <p class="small mb-2 <?= $isOverdue ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <i class="bi bi-calendar-event"></i>
                                    <?= date('d M Y', strtotime($task['deadline'])) ?>
                                    <?= $isOverdue ? ' ⚠️ Terlambat!' : '' ?>
                                </p>
                            <?php endif; ?>

                            <div class="d-flex gap-2 mt-3 flex-wrap">
                                <a href="tasks/update_status.php?id=<?= $task['id'] ?>"
                                    class="btn btn-sm <?= $isCompleted ? 'btn-outline-secondary' : 'btn-success' ?>">
                                    <i class="bi bi-<?= $isCompleted ? 'arrow-counterclockwise' : 'check-lg' ?>"></i>
                                    <?= $isCompleted ? 'Batal' : 'Selesai' ?>
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