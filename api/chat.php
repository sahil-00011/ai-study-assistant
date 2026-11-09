<?php
/**
 * AI Chat API Endpoint
 * AI Study Assistant
 */

define('IS_API', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/openai.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'You must be logged in to chat with the AI Tutor.'], 401);
}

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

// Get action from query string or request body
$action = $_GET['action'] ?? '';

// Decode JSON input if application/json
$rawInput = file_get_contents('php://input');
$inputData = !empty($rawInput) ? json_decode($rawInput, true) : [];
if (!empty($inputData['action'])) {
    $action = $inputData['action'];
}

$db = getDbConnection();

switch ($action) {
    case 'get_conversations':
        // Retrieve all conversations for this user
        try {
            $stmt = $db->prepare("SELECT id, title, updated_at FROM chat_conversations WHERE user_id = ? ORDER BY updated_at DESC LIMIT 50");
            $stmt->execute([$userId]);
            $conversations = $stmt->fetchAll();
            jsonResponse(['success' => true, 'conversations' => $conversations]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to load conversations: ' . $e->getMessage()], 500);
        }
        break;

    case 'get_messages':
        // Retrieve messages for a specific conversation
        $convId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : (int)($inputData['conversation_id'] ?? 0);
        if ($convId <= 0) {
            jsonResponse(['success' => false, 'error' => 'Invalid conversation ID.'], 400);
        }

        try {
            // Verify ownership
            $checkStmt = $db->prepare("SELECT id, title FROM chat_conversations WHERE id = ? AND user_id = ? LIMIT 1");
            $checkStmt->execute([$convId, $userId]);
            $conv = $checkStmt->fetch();
            if (!$conv) {
                jsonResponse(['success' => false, 'error' => 'Conversation not found or access denied.'], 404);
            }

            $stmt = $db->prepare("SELECT id, sender, message, created_at FROM chat_messages WHERE conversation_id = ? ORDER BY id ASC");
            $stmt->execute([$convId]);
            $messages = $stmt->fetchAll();

            jsonResponse([
                'success'       => true,
                'conversation'  => $conv,
                'messages'      => $messages
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to load messages: ' . $e->getMessage()], 500);
        }
        break;

    case 'send_message':
        // CSRF validation
        validateCsrfToken(true);

        // Rate limit: 25 requests per minute
        if (!checkRateLimit('chat_' . $userId, 25, 60)) {
            jsonResponse(['success' => false, 'error' => 'Rate limit exceeded. Please wait a moment before sending another message.'], 429);
        }

        $convId = (int)($inputData['conversation_id'] ?? ($_POST['conversation_id'] ?? 0));
        $messageText = trim($inputData['message'] ?? ($_POST['message'] ?? ''));
        $answerFormat = trim($inputData['format'] ?? ($_POST['format'] ?? 'standard')); // 'standard', '2_mark', '5_mark', '10_mark', 'simplified'

        if (empty($messageText)) {
            jsonResponse(['success' => false, 'error' => 'Message cannot be empty.'], 400);
        }

        if (strlen($messageText) > 4000) {
            jsonResponse(['success' => false, 'error' => 'Message is too long (maximum 4000 characters).'], 400);
        }

        try {
            // If new conversation or no valid ID, create conversation
            $convTitle = '';
            if ($convId > 0) {
                $checkStmt = $db->prepare("SELECT id, title FROM chat_conversations WHERE id = ? AND user_id = ? LIMIT 1");
                $checkStmt->execute([$convId, $userId]);
                $convRow = $checkStmt->fetch();
                if (!$convRow) {
                    $convId = 0; // fallback to create new
                } else {
                    $convTitle = $convRow['title'];
                }
            }

            if ($convId <= 0) {
                // Generate concise title from first question
                $convTitle = mb_substr($messageText, 0, 45, 'UTF-8');
                if (mb_strlen($messageText) > 45) $convTitle .= '...';

                $cStmt = $db->prepare("INSERT INTO chat_conversations (user_id, title) VALUES (?, ?)");
                $cStmt->execute([$userId, $convTitle]);
                $convId = (int)$db->lastInsertId();
            }

            // Save user message to database
            $insMsg = $db->prepare("INSERT INTO chat_messages (conversation_id, sender, message, tokens_used) VALUES (?, 'user', ?, 0)");
            $insMsg->execute([$convId, $messageText]);

            // Load context from past messages (last 6 messages)
            $ctxStmt = $db->prepare("SELECT sender, message FROM chat_messages WHERE conversation_id = ? ORDER BY id DESC LIMIT 8");
            $ctxStmt->execute([$convId]);
            $pastRows = array_reverse($ctxStmt->fetchAll());

            $conversationPayload = [];
            foreach ($pastRows as $row) {
                $conversationPayload[] = [
                    'role'    => $row['sender'] === 'assistant' ? 'assistant' : 'user',
                    'content' => $row['message']
                ];
            }

            // Build academic tutor persona and structure
            $systemInstruction = "You are an expert academic tutor for college and university students. Your goal is to help students understand difficult topics thoroughly in clear, structured language.\n";

            switch ($answerFormat) {
                case '2_mark':
                    $systemInstruction .= "\nAnswer Requirement: Provide a concise, high-scoring 2-mark university exam answer. Include: 1) Precise Definition, 2) Key Formula or Characteristic, 3) One brief real-world or technical example. Keep it within 60-80 words.";
                    break;
                case '5_mark':
                    $systemInstruction .= "\nAnswer Requirement: Provide a structured 5-mark university exam answer. Include: 1) Formal Introduction & Definition, 2) Core Principles or Architecture (with 3-4 bullet points), 3) Concrete Example or Code Snippet, 4) Summary note.";
                    break;
                case '10_mark':
                    $systemInstruction .= "\nAnswer Requirement: Provide a comprehensive 10-mark university exam essay. Include: 1) Introduction, 2) Formal Definition, 3) Detailed Theoretical Explanation & Components, 4) Working Mechanism / Architecture, 5) Real-world Example or Programming Implementation, 6) Advantages & Disadvantages, 7) Conclusion.";
                    break;
                case 'simplified':
                    $systemInstruction .= "\nAnswer Requirement: Explain this topic in the simplest possible ELI5 (Explain Like I'm 5) style using everyday analogies, followed by a brief technical summary.";
                    break;
                default:
                    $systemInstruction .= "\nAnswer Format:\n- Introduction\n- Definition\n- Explanation\n- Important Points\n- Examples / Code Snippets (if applicable)\n- Conclusion";
                    break;
            }

            // Make the AI request via askOpenAI
            $aiResult = askOpenAI($conversationPayload, $systemInstruction);

            if (!$aiResult['success']) {
                jsonResponse([
                    'success' => false,
                    'error'   => $aiResult['error'] ?? 'AI service encountered an error.'
                ], 502);
            }

            $assistantReply = $aiResult['content'];
            $tokensUsed = (int)$aiResult['tokens'];

            // Save assistant message to database
            $insReply = $db->prepare("INSERT INTO chat_messages (conversation_id, sender, message, tokens_used) VALUES (?, 'assistant', ?, ?)");
            $insReply->execute([$convId, $assistantReply, $tokensUsed]);
            $replyId = (int)$db->lastInsertId();

            // Update conversation updated_at
            $updConv = $db->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?");
            $updConv->execute([$convId]);

            // Log activity
            logActivity($userId, 'chat', 'Asked AI Tutor: ' . mb_substr($messageText, 0, 40), $db);

            jsonResponse([
                'success'         => true,
                'conversation_id' => $convId,
                'title'           => $convTitle,
                'reply'           => $assistantReply,
                'message_id'      => $replyId,
                'created_at'      => date('M d, Y h:i A')
            ]);

        } catch (Exception $e) {
            error_log("Chat endpoint error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'A server error occurred: ' . $e->getMessage()], 500);
        }
        break;

    case 'delete_conversation':
        validateCsrfToken(true);
        $convId = (int)($inputData['conversation_id'] ?? ($_POST['conversation_id'] ?? 0));
        if ($convId <= 0) {
            jsonResponse(['success' => false, 'error' => 'Invalid conversation ID.'], 400);
        }

        try {
            $stmt = $db->prepare("DELETE FROM chat_conversations WHERE id = ? AND user_id = ?");
            $stmt->execute([$convId, $userId]);
            jsonResponse(['success' => true, 'message' => 'Conversation deleted.']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to delete: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Invalid or missing action parameter.'], 400);
        break;
}

