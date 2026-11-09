<?php
$pageTitle = 'AI Quiz Generator';
$currentPage = 'quiz';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

$subjects = [];
$pastAttempts = [];

try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT name, code FROM subjects WHERE is_active = 1 ORDER BY name ASC");
    $subjects = $stmt->fetchAll();

    $attStmt = $db->prepare("SELECT qa.*, q.subject, q.topic, q.difficulty 
                             FROM quiz_attempts qa 
                             JOIN quizzes q ON qa.quiz_id = q.id 
                             WHERE qa.user_id = ? 
                             ORDER BY qa.completed_at DESC LIMIT 15");
    $attStmt->execute([$userId]);
    $pastAttempts = $attStmt->fetchAll();
} catch (Exception $e) {
    error_log("Quiz page setup error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-content py-3">
    <!-- State 1: Configuration & History View -->
    <div id="quizConfigSection">
        <div class="row g-4">
            <!-- Left Column: Quiz Generator Form -->
            <div class="col-lg-5">
                <div class="card p-4 shadow-sm">
                    <h5 class="fw-bold mb-3"><i class="bi bi-patch-question text-primary me-2"></i>Create Adaptive Quiz</h5>
                    <p class="small text-muted mb-4">Generate questions strictly formatted to test your theoretical and practical comprehension.</p>

                    <form id="generateQuizForm">
                        <div class="mb-3">
                            <label for="quizSubject" class="form-label small fw-semibold">Subject <span class="text-danger">*</span></label>
                            <input class="form-control" list="subjectOptions" id="quizSubject" placeholder="Choose or type subject" required>
                            <datalist id="subjectOptions">
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s['name']); ?>"><?php echo htmlspecialchars($s['code']); ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="mb-3">
                            <label for="quizTopic" class="form-label small fw-semibold">Topic / Concept <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quizTopic" placeholder="e.g. CPU Scheduling Algorithms" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label for="quizDifficulty" class="form-label small fw-semibold">Difficulty</label>
                                <select class="form-select" id="quizDifficulty">
                                    <option value="easy">Easy (Fundamentals)</option>
                                    <option value="medium" selected>Medium (Standard)</option>
                                    <option value="hard">Hard (Advanced / Gate)</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="quizCount" class="form-label small fw-semibold">Questions</label>
                                <select class="form-select" id="quizCount">
                                    <option value="5" selected>5 Questions</option>
                                    <option value="10">10 Questions</option>
                                    <option value="15">15 Questions</option>
                                    <option value="20">20 Questions</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="quizType" class="form-label small fw-semibold">Question Type</label>
                            <select class="form-select" id="quizType">
                                <option value="mcq" selected>Multiple Choice Questions (4 Options)</option>
                                <option value="true_false">True or False</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" id="startQuizBtn">
                            <i class="bi bi-play-circle-fill"></i>
                            <span>Generate & Start Quiz</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Past Quiz Attempts -->
            <div class="col-lg-7">
                <div class="card p-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold mb-0">Past Quiz Performance</h5>
                        <span class="badge badge-primary-soft"><?php echo count($pastAttempts); ?> attempts</span>
                    </div>

                    <?php if (empty($pastAttempts)): ?>
                        <div class="text-center py-5 my-auto text-muted">
                            <i class="bi bi-patch-question fs-1 opacity-50 d-block mb-2"></i>
                            <h6>No quiz attempts yet</h6>
                            <p class="small text-muted mb-0">Set your subject and topic on the left to start testing yourself!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Topic</th>
                                        <th>Subject</th>
                                        <th>Score</th>
                                        <th>Accuracy</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pastAttempts as $att): ?>
                                        <?php 
                                        $badgeClass = $att['score_percentage'] >= 75 ? 'badge-success-soft' : ($att['score_percentage'] >= 50 ? 'badge-warning-soft' : 'badge-danger-soft');
                                        ?>
                                        <tr>
                                            <td class="fw-semibold text-truncate small" style="max-width: 180px;">
                                                <?php echo htmlspecialchars($att['topic']); ?>
                                            </td>
                                            <td class="small text-muted"><?php echo htmlspecialchars($att['subject']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo $att['correct_answers']; ?> / <?php echo $att['total_questions']; ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold small"><?php echo $att['score_percentage']; ?>%</td>
                                            <td class="small text-muted"><?php echo formatDate($att['completed_at'], 'M d, Y'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- State 2: Interactive Quiz Runner View -->
    <div id="quizRunnerSection" class="d-none">
        <div class="card p-4 shadow-sm mx-auto" style="max-width: 850px;">
            <!-- Quiz Header Banner -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge badge-primary-soft" id="runnerSubjectBadge">Subject</span>
                        <span class="badge bg-secondary-subtle text-secondary" id="runnerDifficultyBadge">Medium</span>
                    </div>
                    <h5 class="fw-bold mb-0 text-primary" id="runnerTopicTitle">Topic Title</h5>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="px-3 py-1 bg-body-tertiary rounded-pill border fw-bold text-primary small d-flex align-items-center gap-2">
                        <i class="bi bi-stopwatch"></i>
                        <span id="quizTimerText">00:00</span>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="cancelQuizBtn">Exit Quiz</button>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                    <span id="runnerQuestionProgressText">Question 1 of 5</span>
                    <span id="runnerAnsweredCountText">0 Answered</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-primary" id="quizProgressBar" role="progressbar" style="width: 20%;"></div>
                </div>
            </div>

            <!-- Question Navigation Pills -->
            <div class="d-flex flex-wrap gap-2 mb-4 justify-content-center" id="questionPillNav">
                <!-- Buttons like 1, 2, 3 generated dynamically -->
            </div>

            <!-- Current Active Question Area -->
            <div class="mb-4">
                <h5 class="fw-bold mb-3" id="currentQuestionText">Loading question...</h5>
                <div class="d-flex flex-column gap-2" id="optionsContainer">
                    <!-- Options cards generated dynamically -->
                </div>
            </div>

            <!-- Action Controls -->
            <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                <button type="button" class="btn btn-secondary px-3" id="prevQuestionBtn">
                    <i class="bi bi-arrow-left me-1"></i> Previous
                </button>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary px-3" id="nextQuestionBtn">
                        Next <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-success px-4 fw-semibold" id="submitQuizBtn">
                        <i class="bi bi-check2-circle me-1"></i> Submit Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- State 3: Quiz Results & Explanations Review View -->
    <div id="quizResultSection" class="d-none">
        <div class="card p-4 p-md-5 shadow-sm mx-auto" style="max-width: 850px;">
            <div class="text-center mb-5">
                <div class="avatar-circle mx-auto mb-3" id="resultAvatarCircle" style="width: 80px; height: 80px; font-size: 2.5rem; background: var(--primary-light); color: var(--primary-color);">
                    <i class="bi bi-award-fill"></i>
                </div>
                <h3 class="fw-bold mb-1" id="resultPercentageText">80% Score</h3>
                <p class="text-muted mb-2" id="resultFractionText">4 out of 5 Questions Correct</p>
                <p class="lead fw-normal text-muted mx-auto" style="max-width: 600px;" id="resultFeedbackMessage">
                    Performance feedback here.
                </p>

                <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                    <button type="button" class="btn btn-primary px-4" id="takeAnotherQuizBtn">
                        <i class="bi bi-plus-circle me-1"></i> Take Another Quiz
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary px-4">
                        <i class="bi bi-speedometer2 me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Detailed Question-by-Question Review -->
            <div class="border-top pt-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-card-checklist text-primary me-2"></i>Detailed Question Review & Explanations</h5>
                <div class="d-flex flex-column gap-3" id="reviewQuestionsContainer">
                    <!-- Cards generated dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = "<script src='assets/js/quiz.js'></script>";
require_once __DIR__ . '/includes/footer.php';
?>

