<?php
/**
 * AI Notes Generation & Management API
 * AI Study Assistant
 */

define('IS_API', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/openai.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
}

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$db = getDbConnection();

$action = $_GET['action'] ?? '';
$rawInput = file_get_contents('php://input');
$inputData = !empty($rawInput) ? json_decode($rawInput, true) : [];
if (!empty($inputData['action'])) {
    $action = $inputData['action'];
}

switch ($action) {
    case 'list':
        try {
            $search = trim($_GET['q'] ?? '');
            $subject = trim($_GET['subject'] ?? '');

            $sql = "SELECT id, subject, topic, academic_level, format, language, created_at, SUBSTRING(content, 1, 180) as excerpt FROM notes WHERE user_id = ?";
            $params = [$userId];

            if (!empty($search)) {
                $sql .= " AND (topic LIKE ? OR subject LIKE ? OR content LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
            if (!empty($subject)) {
                $sql .= " AND subject = ?";
                $params[] = $subject;
            }

            $sql .= " ORDER BY created_at DESC LIMIT 60";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $notes = $stmt->fetchAll();

            jsonResponse(['success' => true, 'notes' => $notes]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to load notes: ' . $e->getMessage()], 500);
        }
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? ($inputData['id'] ?? 0));
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'Invalid note ID.'], 400);
        }

        try {
            $stmt = $db->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$id, $userId]);
            $note = $stmt->fetch();
            if (!$note) {
                jsonResponse(['success' => false, 'error' => 'Note not found or access denied.'], 404);
            }
            jsonResponse(['success' => true, 'note' => $note]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to load note: ' . $e->getMessage()], 500);
        }
        break;

    case 'generate':
        validateCsrfToken(true);

        if (!checkRateLimit('notes_' . $userId, 15, 60)) {
            jsonResponse(['success' => false, 'error' => 'Rate limit exceeded. Please wait a moment before generating another set of notes.'], 429);
        }

        $subject = trim($inputData['subject'] ?? ($_POST['subject'] ?? ''));
        $topic = trim($inputData['topic'] ?? ($_POST['topic'] ?? ''));
        $academicLevel = trim($inputData['academic_level'] ?? ($_POST['academic_level'] ?? 'College/University'));
        $format = trim($inputData['format'] ?? ($_POST['format'] ?? 'detailed'));
        $language = trim($inputData['language'] ?? ($_POST['language'] ?? 'English'));

        if (empty($subject) || empty($topic)) {
            jsonResponse(['success' => false, 'error' => 'Both Subject and Topic are required.'], 400);
        }

        // Format descriptions
        $formatInstructions = match($format) {
            'short'     => "Format: High-impact Short Notes. Focus heavily on concise definitions, core formulas, essential bullet points, and quick memory cues.",
            'exam'      => "Format: Exam Preparation Notes. Focus on frequently asked university questions, marking schemes, 2-mark definitions, 5-mark conceptual breakdowns, and 10-mark essay structures.",
            'revision'  => "Format: Rapid Revision Cheat Sheet. Focus on summary tables, acronyms, differences/comparisons, flowcharts in text form, and key takeaways.",
            'qa'        => "Format: Important Questions & Answers. Provide 5-7 high-probability exam questions with structured model answers, code/diagram explanations, and marks allocation.",
            default     => "Format: Comprehensive Detailed Notes. Provide full textbook-style coverage with complete conceptual depth, architectural breakdowns, code or formulas, and real-world applications."
        };

        $prompt = "Generate comprehensive academic notes for:\n" .
                  "- Subject: {$subject}\n" .
                  "- Topic: {$topic}\n" .
                  "- Academic Level: {$academicLevel}\n" .
                  "- Note Style: {$format}\n" .
                  "- Language: {$language}\n\n" .
                  $formatInstructions . "\n\n" .
                  "Please ensure the output strictly includes:\n" .
                  "1. Executive Summary & Introduction\n" .
                  "2. Standard Formal Definition(s)\n" .
                  "3. Core Theoretical Concepts & Architecture\n" .
                  "4. Detailed Explanation with Pointwise Analysis\n" .
                  "5. Practical Examples, Algorithms, or Code Snippets (if applicable)\n" .
                  "6. Key Advantages & Limitations / Trade-offs\n" .
                  "7. Exam Key Points & Viva Questions\n" .
                  "8. Conclusion & Summary\n" .
                  "Use clear Markdown formatting with headers (##, ###), bullet points, bold keywords, and formatted code blocks.";

        $systemInstruction = "You are a distinguished university professor and academic author specializing in {$subject}. You provide deeply pedagogical, structured, accurate, and syllabus-aligned notes for students.";

        $aiResult = askOpenAI($prompt, $systemInstruction, null, 0.5);

        if (!$aiResult['success']) {
            jsonResponse(['success' => false, 'error' => $aiResult['error'] ?? 'AI generation failed.'], 502);
        }

        $content = $aiResult['content'];

        try {
            $stmt = $db->prepare("INSERT INTO notes (user_id, subject, topic, academic_level, format, language, content) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $subject, $topic, $academicLevel, $format, $language, $content]);
            $noteId = (int)$db->lastInsertId();

            logActivity($userId, 'notes_generated', "Generated notes for: {$topic} ({$subject})", $db);

            jsonResponse([
                'success' => true,
                'note'    => [
                    'id'             => $noteId,
                    'subject'        => $subject,
                    'topic'          => $topic,
                    'academic_level' => $academicLevel,
                    'format'         => $format,
                    'language'       => $language,
                    'content'        => $content,
                    'created_at'     => date('M d, Y h:i A')
                ]
            ]);
        } catch (Exception $e) {
            error_log("Failed to save note: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Database error while saving note: ' . $e->getMessage()], 500);
        }
        break;

    case 'delete':
        validateCsrfToken(true);
        $id = (int)($inputData['id'] ?? ($_POST['id'] ?? 0));
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'Invalid note ID.'], 400);
        }

        try {
            $stmt = $db->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            jsonResponse(['success' => true, 'message' => 'Note deleted successfully.']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to delete note: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
        break;
}

