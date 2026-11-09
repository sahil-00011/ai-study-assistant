<?php
$pageTitle = 'AI Notes Generator';
$currentPage = 'notes';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

// Fetch subjects for dropdown
$subjects = [];
$savedNotes = [];
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT name, code FROM subjects WHERE is_active = 1 ORDER BY name ASC");
    $subjects = $stmt->fetchAll();

    $noteStmt = $db->prepare("SELECT id, subject, topic, format, created_at, SUBSTRING(content, 1, 160) as excerpt FROM notes WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
    $noteStmt->execute([$userId]);
    $savedNotes = $noteStmt->fetchAll();
} catch (Exception $e) {
    error_log("Notes page error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-content py-3">
    <!-- Header Section -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-journal-richtext text-primary me-2"></i>AI Notes Generator</h4>
            <p class="text-muted small mb-0">Generate curriculum-aligned study materials, revision sheets, and exam answers instantly.</p>
        </div>

        <ul class="nav nav-pills" id="notesNavTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active py-2 px-3 fw-semibold small" id="tab-generator-btn" data-bs-toggle="pill" data-bs-target="#panel-generator" type="button">
                    <i class="bi bi-magic me-1"></i> Notes Creator
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-2 px-3 fw-semibold small" id="tab-library-btn" data-bs-toggle="pill" data-bs-target="#panel-library" type="button">
                    <i class="bi bi-collection me-1"></i> My Notes Library (<span id="savedNotesCount"><?php echo count($savedNotes); ?></span>)
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="notesTabContent">
        <!-- Panel 1: Notes Creator -->
        <div class="tab-pane fade show active" id="panel-generator" role="tabpanel">
            <div class="row g-4">
                <!-- Generator Form Column -->
                <div class="col-lg-4">
                    <div class="card p-4 shadow-sm">
                        <h5 class="fw-bold mb-3"><i class="bi bi-sliders text-primary me-1"></i> Note Specifications</h5>
                        <form id="generateNotesForm">
                            <div class="mb-3">
                                <label for="noteSubject" class="form-label small fw-semibold">Subject <span class="text-danger">*</span></label>
                                <input class="form-control" list="subjectOptions" id="noteSubject" placeholder="Select or type subject" required>
                                <datalist id="subjectOptions">
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?php echo htmlspecialchars($s['name']); ?>"><?php echo htmlspecialchars($s['code']); ?></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="mb-3">
                                <label for="noteTopic" class="form-label small fw-semibold">Topic / Concept <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="noteTopic" placeholder="e.g. Memory Management & Paging" required>
                            </div>

                            <div class="mb-3">
                                <label for="noteLevel" class="form-label small fw-semibold">Academic Level</label>
                                <select class="form-select" id="noteLevel">
                                    <option value="Undergraduate" selected>Undergraduate (B.Tech / B.Sc / BCA)</option>
                                    <option value="Postgraduate">Postgraduate (M.Tech / M.Sc / MCA)</option>
                                    <option value="High School">High School (11th & 12th Grade)</option>
                                    <option value="PhD / Advanced">PhD / Research Level</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="noteFormat" class="form-label small fw-semibold">Note Format</label>
                                <select class="form-select" id="noteFormat">
                                    <option value="detailed" selected>Detailed Notes (Comprehensive textbook style)</option>
                                    <option value="short">Short Notes (Quick definitions & formulas)</option>
                                    <option value="exam">Exam Preparation Notes (2, 5 & 10 mark layout)</option>
                                    <option value="revision">Revision Cheat Sheet (Key takeaways & tables)</option>
                                    <option value="qa">Important Questions & Model Answers</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="noteLanguage" class="form-label small fw-semibold">Output Language</label>
                                <select class="form-select" id="noteLanguage">
                                    <option value="English" selected>English</option>
                                    <option value="Hindi">Hindi (हिंदी)</option>
                                    <option value="Spanish">Spanish (Español)</option>
                                    <option value="French">French (Français)</option>
                                    <option value="German">German (Deutsch)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" id="generateNotesBtn">
                                <i class="bi bi-sparkles"></i>
                                <span>Generate Notes with AI</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Generated Notes Viewer Column -->
                <div class="col-lg-8">
                    <div class="card p-4 shadow-sm h-100" id="notesViewerCard">
                        <!-- Empty Placeholder State -->
                        <div id="notesPlaceholder" class="text-center my-auto py-5 text-muted">
                            <div class="avatar-circle mx-auto mb-3" style="width: 68px; height: 68px; font-size: 2rem; background: var(--primary-light); color: var(--primary-color);">
                                <i class="bi bi-journal-text"></i>
                            </div>
                            <h5 class="fw-bold mb-2">No Notes Generated Yet</h5>
                            <p class="small text-muted mx-auto mb-4" style="max-width: 440px;">
                                Specify your subject, topic, and desired format on the left, then click <strong>Generate Notes with AI</strong> to build your study material.
                            </p>
                            <div class="p-3 bg-body-tertiary rounded-3 border d-inline-block text-start small">
                                <span class="fw-semibold text-primary"><i class="bi bi-lightbulb-fill me-1"></i> Try popular topics:</span>
                                <ul class="mb-0 ps-3 mt-1 text-muted">
                                    <li>"Dijkstra's Shortest Path Algorithm"</li>
                                    <li>"ACID Properties & Transactions in DBMS"</li>
                                    <li>"Process Synchronization and Semaphores"</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Generating Loading Spinner -->
                        <div id="notesLoading" class="text-center my-auto py-5 d-none">
                            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                            <h5 class="fw-bold mb-1">Synthesizing Academic Notes...</h5>
                            <p class="small text-muted mb-0">Our AI professor is structuring concepts, definitions, and exam points.</p>
                        </div>

                        <!-- Active Note Display -->
                        <div id="notesDisplay" class="d-none">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 mb-3 border-bottom">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge badge-primary-soft" id="displaySubject">Computer Science</span>
                                        <span class="badge bg-secondary-subtle text-secondary" id="displayFormat">Detailed</span>
                                        <span class="badge bg-body-secondary text-muted" id="displayDate">Just now</span>
                                    </div>
                                    <h3 class="fw-bold mb-0 text-primary" id="displayTopic">Topic Title</h3>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" id="copyNoteBtn">
                                        <i class="bi bi-clipboard"></i> <span>Copy</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" id="downloadNoteBtn">
                                        <i class="bi bi-download"></i> <span>Download (.md)</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" id="printNoteBtn">
                                        <i class="bi bi-printer"></i> <span>Print</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Markdown Content -->
                            <div class="p-3 bg-body-tertiary rounded-3 border" style="max-height: 560px; overflow-y: auto;" id="displayContentBody">
                                <!-- Rendered Markdown will appear here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel 2: Notes Library -->
        <div class="tab-pane fade" id="panel-library" role="tabpanel">
            <div class="card p-4 shadow-sm">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                    <div>
                        <h5 class="fw-bold mb-1">Saved Study Notes</h5>
                        <p class="small text-muted mb-0">Access, search, and review all previous notes generated in your account.</p>
                    </div>

                    <div class="d-flex align-items-center gap-2" style="max-width: 320px; width: 100%;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchNotesInput" placeholder="Search topic or subject...">
                        </div>
                    </div>
                </div>

                <div class="row g-3" id="savedNotesGrid">
                    <?php if (empty($savedNotes)): ?>
                        <div class="col-12 text-center py-5 text-muted empty-lib-placeholder">
                            <i class="bi bi-journal-x fs-1 opacity-50 d-block mb-2"></i>
                            <h6>No saved notes in your library</h6>
                            <p class="small">Switch to the Notes Creator tab to generate your first study note.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($savedNotes as $note): ?>
                            <div class="col-md-6 col-lg-4 note-card-item" data-id="<?php echo $note['id']; ?>" data-topic="<?php echo htmlspecialchars(strtolower($note['topic'])); ?>" data-subject="<?php echo htmlspecialchars(strtolower($note['subject'])); ?>">
                                <div class="card h-100 p-3 card-hover border">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge badge-primary-soft small"><?php echo htmlspecialchars($note['subject']); ?></span>
                                        <span class="text-muted" style="font-size: 0.75rem;"><?php echo formatDate($note['created_at'], 'M d, Y'); ?></span>
                                    </div>
                                    <h6 class="fw-bold mb-2 text-truncate" title="<?php echo htmlspecialchars($note['topic']); ?>">
                                        <?php echo htmlspecialchars($note['topic']); ?>
                                    </h6>
                                    <p class="small text-muted mb-3 flex-grow-1" style="font-size: 0.82rem; line-height: 1.5;">
                                        <?php echo htmlspecialchars($note['excerpt']); ?>...
                                    </p>
                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-auto">
                                        <button type="button" class="btn btn-sm btn-outline-primary view-note-btn" data-id="<?php echo $note['id']; ?>">
                                            <i class="bi bi-eye"></i> View Note
                                        </button>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 delete-note-btn" data-id="<?php echo $note['id']; ?>" title="Delete Note">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = "<script src='assets/js/notes.js'></script>";
require_once __DIR__ . '/includes/footer.php';
?>

