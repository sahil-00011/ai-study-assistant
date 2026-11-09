<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Auth check
requireAuth();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

// Default statistics
$totalConversations = 0;
$totalNotes = 0;
$totalQuizzesAttempted = 0;
$avgQuizScore = 0;
$pendingTasksCount = 0;

$recentConversations = [];
$recentNotes = [];
$recentQuizzes = [];
$upcomingTasks = [];

// Weekly activity for Chart.js
$weeklyActivityLabels = [];
$weeklyActivityCounts = [];

// Quiz score distribution for Chart.js
$quizDistribution = ['< 50%' => 0, '50-75%' => 0, '75-100%' => 0];

$dbError = null;

try {
    $db = getDbConnection();

    // 1. Total Conversations
    $stmt = $db->prepare("SELECT COUNT(*) FROM chat_conversations WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalConversations = (int)$stmt->fetchColumn();

    // 2. Total Notes
    $stmt = $db->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalNotes = (int)$stmt->fetchColumn();

    // 3. Total Quizzes Attempted
    $stmt = $db->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalQuizzesAttempted = (int)$stmt->fetchColumn();

    // 4. Average Quiz Score
    $stmt = $db->prepare("SELECT ROUND(AVG(score_percentage), 1) FROM quiz_attempts WHERE user_id = ?");
    $stmt->execute([$userId]);
    $avgQuizScore = $stmt->fetchColumn() ?: 0;

    // 5. Pending Tasks Count
    $stmt = $db->prepare("SELECT COUNT(*) FROM study_tasks WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $pendingTasksCount = (int)$stmt->fetchColumn();

    // Recent Conversations
    $stmt = $db->prepare("SELECT id, title, updated_at FROM chat_conversations WHERE user_id = ? ORDER BY updated_at DESC LIMIT 4");
    $stmt->execute([$userId]);
    $recentConversations = $stmt->fetchAll();

    // Recent Notes
    $stmt = $db->prepare("SELECT id, subject, topic, format, created_at FROM notes WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
    $stmt->execute([$userId]);
    $recentNotes = $stmt->fetchAll();

    // Recent Quizzes
    $stmt = $db->prepare("SELECT qa.id, qa.score_percentage, qa.completed_at, q.subject, q.topic 
                          FROM quiz_attempts qa 
                          JOIN quizzes q ON qa.quiz_id = q.id 
                          WHERE qa.user_id = ? 
                          ORDER BY qa.completed_at DESC LIMIT 4");
    $stmt->execute([$userId]);
    $recentQuizzes = $stmt->fetchAll();

    // Upcoming Tasks
    $stmt = $db->prepare("SELECT id, title, priority, due_date, category FROM study_tasks WHERE user_id = ? AND status = 'pending' ORDER BY (due_date IS NULL), due_date ASC LIMIT 5");
    $stmt->execute([$userId]);
    $upcomingTasks = $stmt->fetchAll();

    // Weekly Activity chart data (last 7 days)
    $stmt = $db->prepare("SELECT DATE(created_at) as log_date, COUNT(*) as cnt 
                          FROM activity_logs 
                          WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
                          GROUP BY DATE(created_at) 
                          ORDER BY log_date ASC");
    $stmt->execute([$userId]);
    $activityMap = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    for ($i = 6; $i >= 0; $i--) {
        $dateStr = date('Y-m-d', strtotime("-$i days"));
        $dayLabel = date('D (M d)', strtotime("-$i days"));
        $weeklyActivityLabels[] = $dayLabel;
        $weeklyActivityCounts[] = isset($activityMap[$dateStr]) ? (int)$activityMap[$dateStr] : 0;
    }

    // Quiz Distribution calculation
    $stmt = $db->prepare("SELECT score_percentage FROM quiz_attempts WHERE user_id = ?");
    $stmt->execute([$userId]);
    $allScores = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($allScores as $s) {
        $val = (float)$s;
        if ($val < 50) $quizDistribution['< 50%']++;
        elseif ($val < 75) $quizDistribution['50-75%']++;
        else $quizDistribution['75-100%']++;
    }

} catch (Exception $e) {
    $dbError = $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-content">
    <?php if ($dbError): ?>
        <div class="alert alert-warning shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Database Notice:</strong> <?php echo htmlspecialchars($dbError); ?>. Please ensure MySQL is running in XAMPP and <code>database/ai_study_assistant.sql</code> is imported.
        </div>
    <?php endif; ?>

    <!-- Welcome Header Banner -->
    <div class="card border-0 mb-4 shadow-sm" style="background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%); color: #FFFFFF; border-radius: 16px;">
        <div class="card-body p-4 p-md-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge mb-2 px-3 py-1" style="background: rgba(255,255,255,0.2); font-weight: 500;">
                    <i class="bi bi-mortarboard-fill me-1"></i> <?php echo htmlspecialchars($currentUser['academic_level'] ?? 'Undergraduate'); ?>
                </span>
                <h2 class="fw-bold mb-1 text-white">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>!</h2>
                <p class="mb-0 text-white-50">
                    <?php echo htmlspecialchars($currentUser['institution'] ?? 'AI Study Assistant Platform'); ?> &bull; Let's accelerate your learning today.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="chatbot.php" class="btn btn-light fw-semibold text-primary shadow-sm">
                    <i class="bi bi-chat-dots-fill me-1"></i> Ask AI Tutor
                </a>
                <a href="notes.php" class="btn btn-outline-light fw-semibold">
                    <i class="bi bi-file-earmark-plus-fill me-1"></i> Create Notes
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Grid (Loaded strictly from MySQL) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: AI Conversations -->
        <div class="col-sm-6 col-xl-2dot4 col-lg-4">
            <div class="card stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-icon" style="background: rgba(79, 70, 229, 0.12); color: #4F46E5;">
                        <i class="bi bi-chat-left-dots-fill"></i>
                    </div>
                    <span class="badge badge-primary-soft">Conversations</span>
                </div>
                <div class="stat-number"><?php echo number_format($totalConversations); ?></div>
                <div class="stat-label">AI Tutor Sessions</div>
            </div>
        </div>

        <!-- Card 2: Generated Notes -->
        <div class="col-sm-6 col-xl-2dot4 col-lg-4">
            <div class="card stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10B981;">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <span class="badge badge-success-soft">Study Notes</span>
                </div>
                <div class="stat-number"><?php echo number_format($totalNotes); ?></div>
                <div class="stat-label">Topic Guides Saved</div>
            </div>
        </div>

        <!-- Card 3: Quizzes Attempted -->
        <div class="col-sm-6 col-xl-2dot4 col-lg-4">
            <div class="card stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #F59E0B;">
                        <i class="bi bi-patch-question-fill"></i>
                    </div>
                    <span class="badge badge-warning-soft">Tests Taken</span>
                </div>
                <div class="stat-number"><?php echo number_format($totalQuizzesAttempted); ?></div>
                <div class="stat-label">Quizzes Completed</div>
            </div>
        </div>

        <!-- Card 4: Average Score -->
        <div class="col-sm-6 col-xl-2dot4 col-lg-4">
            <div class="card stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-icon" style="background: rgba(14, 165, 233, 0.12); color: #0EA5E9;">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <span class="badge" style="background: rgba(14, 165, 233, 0.15); color: #0EA5E9;">Score Avg</span>
                </div>
                <div class="stat-number"><?php echo $avgQuizScore; ?>%</div>
                <div class="stat-label">Overall Accuracy</div>
            </div>
        </div>

        <!-- Card 5: Pending Tasks -->
        <div class="col-sm-12 col-xl-2dot4 col-lg-4">
            <div class="card stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #EF4444;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <span class="badge badge-danger-soft">Pending</span>
                </div>
                <div class="stat-number"><?php echo number_format($pendingTasksCount); ?></div>
                <div class="stat-label">Study Tasks Due</div>
            </div>
        </div>
    </div>

    <!-- Section B: Quick Action Pills -->
    <div class="card mb-4 p-3 shadow-sm">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="fw-semibold small text-muted text-uppercase letter-spacing">
                <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Launch Tools:
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="chatbot.php" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                    <i class="bi bi-chat-dots"></i> Ask AI Tutor
                </a>
                <a href="notes.php" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1">
                    <i class="bi bi-journal-plus"></i> Generate Notes
                </a>
                <a href="quiz.php" class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1">
                    <i class="bi bi-patch-question"></i> Create Quiz
                </a>
                <a href="planner.php" class="btn btn-sm btn-outline-info d-flex align-items-center gap-1">
                    <i class="bi bi-plus-circle"></i> Add Study Task
                </a>
                <a href="pdf-summarizer.php" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
                    <i class="bi bi-file-earmark-pdf"></i> Summarize PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Section A: Learning Analytics Charts -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100 p-4 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Weekly Learning Activity</h5>
                        <p class="small text-muted mb-0">Platform interactions (notes, quizzes, chats, tasks) over the last 7 days</p>
                    </div>
                    <span class="badge badge-primary-soft">Real-time MySQL Data</span>
                </div>
                <div style="height: 260px; position: relative;">
                    <canvas id="weeklyActivityChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100 p-4 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Quiz Mastery</h5>
                        <p class="small text-muted mb-0">Score distribution breakdown</p>
                    </div>
                    <i class="bi bi-pie-chart text-muted"></i>
                </div>
                <div style="height: 220px; position: relative;" class="d-flex align-items-center justify-content-center">
                    <?php if ($totalQuizzesAttempted > 0): ?>
                        <canvas id="quizDistributionChart"></canvas>
                    <?php else: ?>
                        <div class="text-center text-muted p-4">
                            <i class="bi bi-patch-question fs-1 text-muted opacity-50"></i>
                            <div class="small mt-2">No quizzes attempted yet.</div>
                            <a href="quiz.php" class="btn btn-sm btn-primary mt-2">Generate First Quiz</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex justify-content-around text-center mt-3 pt-2 border-top small">
                    <div>
                        <div class="fw-bold text-success"><?php echo $quizDistribution['75-100%']; ?></div>
                        <div class="text-muted" style="font-size:0.75rem;">75-100%</div>
                    </div>
                    <div>
                        <div class="fw-bold text-warning"><?php echo $quizDistribution['50-75%']; ?></div>
                        <div class="text-muted" style="font-size:0.75rem;">50-75%</div>
                    </div>
                    <div>
                        <div class="fw-bold text-danger"><?php echo $quizDistribution['< 50%']; ?></div>
                        <div class="text-muted" style="font-size:0.75rem;">&lt; 50%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section C & D: Recent Activity & Upcoming Tasks -->
    <div class="row g-4">
        <!-- Recent Notes & Chat Activities -->
        <div class="col-lg-7">
            <div class="card p-4 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0">Recent Study Activities</h5>
                    <a href="history.php" class="small text-decoration-none text-primary fw-semibold">View All History &rarr;</a>
                </div>

                <!-- Nav Tabs for Recent Items -->
                <ul class="nav nav-pills mb-3 small" id="recentTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-1 px-3" id="notes-tab" data-bs-toggle="pill" data-bs-target="#tab-notes" type="button" role="tab">Recent Notes (<?php echo count($recentNotes); ?>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1 px-3" id="chats-tab" data-bs-toggle="pill" data-bs-target="#tab-chats" type="button" role="tab">AI Chats (<?php echo count($recentConversations); ?>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1 px-3" id="quizzes-tab" data-bs-toggle="pill" data-bs-target="#tab-quizzes" type="button" role="tab">Quizzes (<?php echo count($recentQuizzes); ?>)</button>
                    </li>
                </ul>

                <div class="tab-content" id="recentTabContent">
                    <!-- Tab 1: Notes -->
                    <div class="tab-pane fade show active" id="tab-notes" role="tabpanel">
                        <?php if (empty($recentNotes)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-journal-x fs-2 opacity-50"></i>
                                <p class="small mt-2 mb-0">No notes generated yet.</p>
                                <a href="notes.php" class="btn btn-sm btn-outline-primary mt-2">Generate Notes</a>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentNotes as $n): ?>
                                    <div class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between">
                                        <div class="text-truncate me-2">
                                            <div class="fw-semibold text-truncate small"><?php echo htmlspecialchars($n['topic']); ?></div>
                                            <span class="badge bg-body-secondary text-muted border" style="font-size:0.75rem;"><?php echo htmlspecialchars($n['subject']); ?></span>
                                            <span class="badge badge-primary-soft" style="font-size:0.72rem;"><?php echo htmlspecialchars($n['format']); ?></span>
                                        </div>
                                        <div class="small text-muted flex-shrink-0" style="font-size:0.8rem;">
                                            <?php echo timeAgo($n['created_at']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 2: Chats -->
                    <div class="tab-pane fade" id="tab-chats" role="tabpanel">
                        <?php if (empty($recentConversations)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-chat-square-dots fs-2 opacity-50"></i>
                                <p class="small mt-2 mb-0">No AI chat sessions started yet.</p>
                                <a href="chatbot.php" class="btn btn-sm btn-outline-primary mt-2">Start First Chat</a>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentConversations as $c): ?>
                                    <a href="chatbot.php?conv_id=<?php echo $c['id']; ?>" class="list-group-item list-group-item-action px-0 py-2 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                            <i class="bi bi-chat-left-text text-primary"></i>
                                            <span class="small fw-semibold text-truncate"><?php echo htmlspecialchars($c['title']); ?></span>
                                        </div>
                                        <span class="small text-muted flex-shrink-0" style="font-size:0.8rem;"><?php echo timeAgo($c['updated_at']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 3: Quizzes -->
                    <div class="tab-pane fade" id="tab-quizzes" role="tabpanel">
                        <?php if (empty($recentQuizzes)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-patch-question fs-2 opacity-50"></i>
                                <p class="small mt-2 mb-0">No quizzes completed yet.</p>
                                <a href="quiz.php" class="btn btn-sm btn-outline-warning mt-2">Take a Quiz</a>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentQuizzes as $q): ?>
                                    <div class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between">
                                        <div class="text-truncate me-2">
                                            <div class="fw-semibold small text-truncate"><?php echo htmlspecialchars($q['topic']); ?> (<?php echo htmlspecialchars($q['subject']); ?>)</div>
                                            <span class="text-muted" style="font-size:0.75rem;"><?php echo formatDate($q['completed_at']); ?></span>
                                        </div>
                                        <span class="badge <?php echo $q['score_percentage'] >= 70 ? 'badge-success-soft' : ($q['score_percentage'] >= 50 ? 'badge-warning-soft' : 'badge-danger-soft'); ?> fs-6">
                                            <?php echo $q['score_percentage']; ?>%
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Study Tasks -->
        <div class="col-lg-5">
            <div class="card p-4 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0">Upcoming Tasks</h5>
                    <a href="planner.php" class="small text-decoration-none text-primary fw-semibold">View Planner &rarr;</a>
                </div>

                <?php if (empty($upcomingTasks)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar2-check fs-1 text-success opacity-75"></i>
                        <h6 class="fw-bold mt-2">All caught up!</h6>
                        <p class="small text-muted mb-3">No pending tasks on your study schedule.</p>
                        <a href="planner.php" class="btn btn-sm btn-primary">Add a Study Task</a>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($upcomingTasks as $task): ?>
                            <?php 
                            $priorityBadge = match($task['priority']) {
                                'high'   => 'badge-danger-soft',
                                'medium' => 'badge-warning-soft',
                                default  => 'badge-primary-soft'
                            };
                            $isOverdue = !empty($task['due_date']) && strtotime($task['due_date']) < strtotime(date('Y-m-d'));
                            ?>
                            <div class="p-3 border rounded-3 bg-body-tertiary d-flex align-items-center justify-content-between">
                                <div class="text-truncate me-2">
                                    <div class="fw-semibold small text-truncate"><?php echo htmlspecialchars($task['title']); ?></div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge <?php echo $priorityBadge; ?>" style="font-size:0.7rem;"><?php echo ucfirst($task['priority']); ?></span>
                                        <span class="small text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($task['category']); ?></span>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <?php if (!empty($task['due_date'])): ?>
                                        <div class="small <?php echo $isOverdue ? 'text-danger fw-bold' : 'text-muted'; ?>" style="font-size:0.78rem;">
                                            <i class="bi bi-calendar-event me-1"></i><?php echo date('M d', strtotime($task['due_date'])); ?>
                                            <?php if ($isOverdue): ?><span class="badge bg-danger ms-1" style="font-size:0.65rem;">LATE</span><?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small" style="font-size:0.75rem;">No deadline</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$weeklyLabelsJson = json_encode($weeklyActivityLabels);
$weeklyCountsJson = json_encode($weeklyActivityCounts);
$quizDistJson = json_encode(array_values($quizDistribution));

$extraScripts = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Weekly Activity Chart
    const ctxWeekly = document.getElementById('weeklyActivityChart');
    if (ctxWeekly) {
        new Chart(ctxWeekly, {
            type: 'bar',
            data: {
                labels: {$weeklyLabelsJson},
                datasets: [{
                    label: 'Study Activities',
                    data: {$weeklyCountsJson},
                    backgroundColor: 'rgba(79, 70, 229, 0.75)',
                    hoverBackgroundColor: '#4F46E5',
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Quiz Mastery Doughnut Chart
    const ctxQuiz = document.getElementById('quizDistributionChart');
    if (ctxQuiz) {
        new Chart(ctxQuiz, {
            type: 'doughnut',
            data: {
                labels: ['< 50%', '50-75%', '75-100%'],
                datasets: [{
                    data: {$quizDistJson},
                    backgroundColor: ['#EF4444', '#F59E0B', '#16A34A'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>
";

require_once __DIR__ . '/includes/footer.php';
?>

