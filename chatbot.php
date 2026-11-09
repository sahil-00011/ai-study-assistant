<?php
$pageTitle = 'AI Academic Tutor';
$currentPage = 'chatbot';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

// Check if a specific conversation ID was requested in query string
$requestedConvId = isset($_GET['conv_id']) ? (int)$_GET['conv_id'] : 0;

// Fetch user's existing conversations
$conversations = [];
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id, title, updated_at FROM chat_conversations WHERE user_id = ? ORDER BY updated_at DESC LIMIT 50");
    $stmt->execute([$userId]);
    $conversations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Failed to load user conversations: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-content py-3">
    <div class="chat-wrapper shadow-sm">
        <!-- Sidebar: Conversation History -->
        <div class="chat-history-sidebar">
            <div class="chat-history-header d-flex align-items-center justify-content-between">
                <button type="button" class="btn btn-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-2" id="newChatBtn">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Chat</span>
                </button>
            </div>

            <div class="chat-history-list" id="conversationsList">
                <?php if (empty($conversations)): ?>
                    <div class="text-center text-muted small p-4 empty-conv-placeholder">
                        <i class="bi bi-chat-square-dots fs-3 d-block mb-2 opacity-50"></i>
                        No conversations yet. Start asking academic questions!
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $c): ?>
                        <div class="chat-history-item <?php echo $c['id'] == $requestedConvId ? 'active' : ''; ?>" data-id="<?php echo $c['id']; ?>">
                            <div class="d-flex align-items-center gap-2 text-truncate conv-title-btn" style="cursor: pointer; flex: 1;">
                                <i class="bi bi-chat-text text-primary"></i>
                                <span class="text-truncate"><?php echo htmlspecialchars($c['title']); ?></span>
                            </div>
                            <button type="button" class="btn btn-link text-muted p-0 delete-conv-btn" data-id="<?php echo $c['id']; ?>" title="Delete Conversation" style="font-size: 0.85rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-main-area">
            <!-- Chat Header -->
            <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2 bg-body-tertiary">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-circle" style="width: 34px; height: 34px; background: linear-gradient(135deg, #4F46E5, #06B6D4); font-size: 0.9rem;">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0" id="currentChatTitle">Academic AI Tutor</h6>
                        <span class="badge badge-success-soft" style="font-size: 0.7rem;"><i class="bi bi-check-circle-fill"></i> Ready</span>
                    </div>
                </div>

                <!-- Answer Format Selector -->
                <div class="d-flex align-items-center gap-2">
                    <label for="answerFormatSelect" class="small text-muted fw-semibold mb-0 d-none d-sm-inline">Exam Format:</label>
                    <select class="form-select form-select-sm" id="answerFormatSelect" style="width: auto; font-size: 0.85rem;">
                        <option value="standard" selected>Standard Explanation (Structured)</option>
                        <option value="2_mark">2-Mark Answer (Brief Definition & Example)</option>
                        <option value="5_mark">5-Mark Answer (Pointwise & Architecture)</option>
                        <option value="10_mark">10-Mark Answer (Detailed Exam Essay)</option>
                        <option value="simplified">Simplified / ELI5 (Analogy based)</option>
                    </select>
                </div>
            </div>

            <!-- Messages Window -->
            <div class="chat-messages" id="chatMessages">
                <!-- Welcome / Empty State Card -->
                <div id="chatWelcomeState" class="text-center my-auto py-4">
                    <div class="avatar-circle mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem; background: var(--primary-light); color: var(--primary-color);">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-2">How can I help your studies today?</h5>
                    <p class="text-muted small mx-auto mb-4" style="max-width: 500px;">
                        I am your personalized AI tutor. Ask questions, request programming solutions, or ask for structured exam answers.
                    </p>

                    <div class="row g-2 justify-content-center mx-auto" style="max-width: 700px;">
                        <div class="col-sm-6">
                            <button class="btn btn-outline-secondary btn-sm w-100 text-start p-2 quick-prompt-btn" data-prompt="Explain Normalization in DBMS with 1NF, 2NF, and 3NF examples.">
                                <i class="bi bi-database me-1 text-primary"></i> <strong>DBMS:</strong> Explain 1NF, 2NF, 3NF &rarr;
                            </button>
                        </div>
                        <div class="col-sm-6">
                            <button class="btn btn-outline-secondary btn-sm w-100 text-start p-2 quick-prompt-btn" data-prompt="How does Virtual Memory and Paging work in modern Operating Systems?">
                                <i class="bi bi-cpu me-1 text-primary"></i> <strong>OS:</strong> Virtual Memory & Paging &rarr;
                            </button>
                        </div>
                        <div class="col-sm-6">
                            <button class="btn btn-outline-secondary btn-sm w-100 text-start p-2 quick-prompt-btn" data-prompt="Write a Binary Search algorithm in Python with step-by-step time complexity analysis.">
                                <i class="bi bi-code-slash me-1 text-primary"></i> <strong>DSA:</strong> Binary Search & Big-O &rarr;
                            </button>
                        </div>
                        <div class="col-sm-6">
                            <button class="btn btn-outline-secondary btn-sm w-100 text-start p-2 quick-prompt-btn" data-prompt="What is the difference between TCP and UDP protocols with real-world examples?">
                                <i class="bi bi-hdd-network me-1 text-primary"></i> <strong>Networks:</strong> TCP vs UDP &rarr;
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Input Area -->
            <div class="chat-input-container">
                <form id="chatForm">
                    <div class="chat-input-box">
                        <textarea id="chatInput" class="chat-textarea" rows="1" placeholder="Type your academic question or problem here... (Shift + Enter for new line)" required></textarea>
                        <button type="submit" class="btn btn-primary px-3 py-2 d-flex align-items-center gap-1 flex-shrink-0" id="sendBtn">
                            <span>Send</span>
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </form>
                <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                    <span class="text-muted" style="font-size: 0.75rem;">
                        <i class="bi bi-shield-check text-success"></i> Powered by OpenAI API. Student responses are saved to your account.
                    </span>
                    <span class="text-muted" style="font-size: 0.75rem;" id="charCounter">0 / 4000</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$initConvId = (int)$requestedConvId;
$extraScripts = "
<script>
    const INITIAL_CONVERSATION_ID = {$initConvId};
</script>
<script src='assets/js/chatbot.js'></script>
";

require_once __DIR__ . '/includes/footer.php';
?>

