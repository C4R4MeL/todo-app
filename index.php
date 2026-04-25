<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/auth.php');
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

// Greeting berdasarkan jam
$hour = (int) date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Selamat Pagi';
    $greetIcon = '☀️';
} elseif ($hour >= 12 && $hour < 15) {
    $greeting = 'Selamat Siang';
    $greetIcon = '🌤️';
} elseif ($hour >= 15 && $hour < 19) {
    $greeting = 'Selamat Sore';
    $greetIcon = '🌅';
} else {
    $greeting = 'Selamat Malam';
    $greetIcon = '🌙';
}

// Ringkasan task hari ini (deadline = hari ini)
$today = date('Y-m-d');
$todayTasks = mysqli_num_rows(mysqli_query(
    $conn,
    "SELECT id FROM tasks WHERE user_id='$user_id' 
     AND deadline = '$today' AND status = 'pending'"
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
<nav class="navbar shadow-sm mb-4" style="background: linear-gradient(135deg, #0d6efd, #6610f2);">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="index.php">
            <i class="bi bi-check2-square"></i> Todo App
        </a>
        <a href="auth/logout.php" class="btn btn-light btn-sm px-3">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
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

    <!-- Greeting + Statistik -->
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #0d6efd, #6610f2); color: white; border-radius: 16px;">
        <div class="card-body p-4">

            <!-- Greeting -->
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold mb-1">
                        <?= $greetIcon ?> <?= $greeting ?>, <?= $_SESSION['username'] ?>!
                    </h4>
                    <?php if ($todayTasks > 0): ?>
                        <p class="mb-0 opacity-75">
                            Kamu punya <strong><?= $todayTasks ?> task</strong> yang harus diselesaikan hari ini. Semangat! 💪
                        </p>
                    <?php elseif ($pending > 0): ?>
                        <p class="mb-0 opacity-75">
                            Tidak ada task jatuh tempo hari ini. Kamu punya <strong><?= $pending ?> task</strong> yang masih pending.
                        </p>
                    <?php else: ?>
                        <p class="mb-0 opacity-75">
                            Semua task sudah selesai! Kerja bagus hari ini. 🎉
                        </p>
                    <?php endif; ?>
                </div>
                <div class="text-end opacity-75 small">
                    <?= date('l, d F Y') ?>
                </div>
            </div>

            <hr style="border-color: rgba(255,255,255,0.2); margin: 1rem 0;">

            <!-- Statistik -->
            <div class="row g-3 text-center">
                <div class="col-6 col-md-3" onclick="applyFilter('all')" style="cursor:pointer">
                    <div class="stat-card-inner" id="stat-all">
                        <div class="fs-2 fw-bold"><?= $total ?></div>
                        <div class="small opacity-75">Total Task</div>
                    </div>
                </div>
                <div class="col-6 col-md-3" onclick="applyFilter('pending')" style="cursor:pointer">
                    <div class="stat-card-inner" id="stat-pending">
                        <div class="fs-2 fw-bold"><?= $pending ?></div>
                        <div class="small opacity-75">Pending</div>
                    </div>
                </div>
                <div class="col-6 col-md-3" onclick="applyFilter('completed')" style="cursor:pointer">
                    <div class="stat-card-inner" id="stat-completed">
                        <div class="fs-2 fw-bold"><?= $completed ?></div>
                        <div class="small opacity-75">Selesai</div>
                    </div>
                </div>
                <div class="col-6 col-md-3" onclick="applyFilter('overdue')" style="cursor:pointer">
                    <div class="stat-card-inner" id="stat-overdue">
                        <div class="fs-2 fw-bold"><?= $overdue ?></div>
                        <div class="small opacity-75">Terlambat</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Header + Tombol Tambah -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold">📋 Daftar Task</h5>
        <div class="d-flex gap-2 align-items-center">

            <!-- Dropdown Sort -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                    type="button" id="sortDropdown"
                    data-bs-toggle="dropdown">
                    <i class="bi bi-sort-down"></i> Sort
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <h6 class="dropdown-header">Urutkan berdasarkan</h6>
                    </li>
                    <li>
                        <button class="dropdown-item sort-btn active-sort" data-sort="deadline">
                            <i class="bi bi-calendar-event text-primary"></i> Deadline Terdekat
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item sort-btn" data-sort="priority">
                            <i class="bi bi-flag text-danger"></i> Prioritas Tertinggi
                        </button>
                    </li>
                </ul>
            </div>

            <a href="tasks/create.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Tambah Task
            </a>
        </div>
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
        <div class="row g-3" id="task-container">
            <?php while ($task = mysqli_fetch_assoc($tasks)): ?>
                <?php
                $isCompleted = $task['status'] === 'completed';
                $isOverdue   = $task['deadline'] && $task['deadline'] < date('Y-m-d') && !$isCompleted;
                $cardClass   = $isCompleted ? 'completed' : 'priority-' . $task['priority'];
                ?>
                <div class="col-md-6 task-item"
                    data-status="<?= $task['status'] ?>"
                    data-overdue="<?= $isOverdue ? 'true' : 'false' ?>"
                    data-priority="<?= $task['priority'] === 'high' ? 1 : ($task['priority'] === 'medium' ? 2 : 3) ?>"
                    data-deadline-ts="<?= $task['deadline'] ? strtotime($task['deadline']) : 99999999999 ?>">
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
                                <?php if (!$isCompleted): ?>
                                    <a href="tasks/edit.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                <?php endif; ?>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const taskContainer = document.getElementById('task-container');
        let activeFilter = 'all';
        let activeSort = 'deadline';

        // ===== FILTER =====
        function applyFilter(filter) {
            activeFilter = filter;

            // Reset & aktifkan stat card
            document.querySelectorAll('.stat-card-inner').forEach(c => c.classList.remove('active'));
            document.getElementById('stat-' + filter)?.classList.add('active');

            const items = document.querySelectorAll('.task-item');
            let visible = 0;

            items.forEach(item => {
                const status = item.dataset.status;
                const overdue = item.dataset.overdue === 'true';

                let show = false;
                if (filter === 'all') show = true;
                if (filter === 'pending' && status === 'pending' && !overdue) show = true;
                if (filter === 'completed' && status === 'completed') show = true;
                if (filter === 'overdue' && overdue) show = true;

                item.style.display = show ? '' : 'none';
                if (show) {
                    item.style.animation = 'fadeInCard 0.3s ease';
                    visible++;
                }
            });

            // Pesan kosong
            const old = document.getElementById('empty-filter-msg');
            if (old) old.remove();

            if (visible === 0 && taskContainer) {
                const labels = {
                    all: 'semua',
                    pending: 'pending',
                    completed: 'selesai',
                    overdue: 'terlambat'
                };
                const msg = document.createElement('div');
                msg.id = 'empty-filter-msg';
                msg.className = 'col-12 text-center py-4 text-muted';
                msg.innerHTML = `<i class="bi bi-filter-circle fs-3"></i>
                             <p class="mt-2">Tidak ada task <strong>${labels[filter]}</strong> saat ini.</p>`;
                taskContainer.appendChild(msg);
            }
        }

        // Stat card klik
        window.applyFilter = applyFilter;

        // ===== SORT =====
        function sortTasks(sortBy) {
            activeSort = sortBy;

            document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active-sort'));
            document.querySelector(`.sort-btn[data-sort="${sortBy}"]`)?.classList.add('active-sort');

            const labels = {
                deadline: '<i class="bi bi-calendar-event"></i> Deadline',
                priority: '<i class="bi bi-flag"></i> Prioritas'
            };
            document.getElementById('sortDropdown').innerHTML =
                `<i class="bi bi-sort-down"></i> ${labels[sortBy]}`;

            if (!taskContainer) return;

            const items = Array.from(document.querySelectorAll('.task-item'));

            items.sort((a, b) => {
                const aCompleted = a.dataset.status === 'completed';
                const bCompleted = b.dataset.status === 'completed';

                // Task completed selalu di bawah
                if (aCompleted && !bCompleted) return 1;
                if (!aCompleted && bCompleted) return -1;

                // Sort normal untuk task yang sama statusnya
                if (sortBy === 'deadline') {
                    return Number(a.dataset.deadlineTs) - Number(b.dataset.deadlineTs);
                } else if (sortBy === 'priority') {
                    return Number(a.dataset.priority) - Number(b.dataset.priority);
                }
                return 0;
            });

            items.forEach(item => taskContainer.appendChild(item));
            setTimeout(equalizeCards, 50);
        }

        // Event klik sort
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                sortTasks(this.dataset.sort);
            });
        });

        // Default saat load
        sortTasks('deadline');
        applyFilter('all');

        // ===== EQUALIZE CARD HEIGHT =====
        window.equalizeCards = function() {
            const items = document.querySelectorAll('.task-item .card');
            items.forEach(card => card.style.height = 'auto');
            const rows = {};
            items.forEach(card => {
                const top = Math.round(card.getBoundingClientRect().top);
                if (!rows[top]) rows[top] = [];
                rows[top].push(card);
            });
            Object.values(rows).forEach(row => {
                const maxH = Math.max(...row.map(c => c.offsetHeight));
                row.forEach(card => card.style.height = maxH + 'px');
            });
        };

        window.addEventListener('load', equalizeCards);
        window.addEventListener('resize', equalizeCards);

    });
</script>

<?php require_once 'includes/footer.php'; ?>