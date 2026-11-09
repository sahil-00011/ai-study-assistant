<?php
/**
 * PDF Summarization API Endpoint
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
    case 'list_documents':
        try {
            $stmt = $db->prepare("SELECT d.id, d.original_filename, d.file_size, d.created_at, s.id as summary_id 
                                  FROM pdf_documents d 
                                  LEFT JOIN pdf_summaries s ON d.id = s.document_id 
                                  WHERE d.user_id = ? 
                                  ORDER BY d.created_at DESC LIMIT 30");
            $stmt->execute([$userId]);
            $docs = $stmt->fetchAll();
            jsonResponse(['success' => true, 'documents' => $docs]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
        break;

    case 'get_summary':
        $docId = (int)($_GET['document_id'] ?? ($inputData['document_id'] ?? 0));
        try {
            $stmt = $db->prepare("SELECT s.*, d.original_filename FROM pdf_summaries s 
                                  JOIN pdf_documents d ON s.document_id = d.id 
                                  WHERE s.document_id = ? AND s.user_id = ? LIMIT 1");
            $stmt->execute([$docId, $userId]);
            $summary = $stmt->fetch();
            if (!$summary) {
                jsonResponse(['success' => false, 'error' => 'Summary not found.'], 404);
            }
            jsonResponse(['success' => true, 'summary' => $summary]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
        break;

    case 'summarize':
        validateCsrfToken(true);

        if (!checkRateLimit('pdf_sum_' . $userId, 10, 60)) {
            jsonResponse(['success' => false, 'error' => 'Rate limit exceeded. Please wait a moment.'], 429);
        }

        $docId = (int)($inputData['document_id'] ?? ($_POST['document_id'] ?? 0));
        $summaryType = trim($inputData['summary_type'] ?? 'comprehensive');

        if ($docId <= 0) {
            jsonResponse(['success' => false, 'error' => 'Document ID is required.'], 400);
        }

        try {
            // Verify ownership
            $stmt = $db->prepare("SELECT id, original_filename, extracted_text FROM pdf_documents WHERE id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$docId, $userId]);
            $doc = $stmt->fetch();

            if (!$doc) {
                jsonResponse(['success' => false, 'error' => 'Document not found or access denied.'], 404);
            }

            $extractedText = trim($doc['extracted_text']);
            if (empty($extractedText) || strlen($extractedText) < 20) {
                jsonResponse(['success' => false, 'error' => 'Document text is too short or empty to summarize.'], 400);
            }

            // Truncate text if excessively large to protect token limits (approx 15,000 chars)
            $textForAi = mb_substr($extractedText, 0, 15000, 'UTF-8');

            $prompt = "You are an expert academic research assistant and university tutor. Summarize and analyze the following academic document:\n" .
                      "Document Name: {$doc['original_filename']}\n\n" .
                      "Document Text:\n\"\"\"\n{$textForAi}\n\"\"\"\n\n" .
                      "Please generate a structured educational study guide with the following sections:\n" .
                      "1. EXECUTIVE SUMMARY: Clear overview of the document's core thesis and purpose.\n" .
                      "2. KEY CONCEPTS & DEFINITIONS: Crucial terms and definitions defined in the text.\n" .
                      "3. REVISION BULLET POINTS: High-yield points, laws, mechanisms, or takeaways for quick revision.\n" .
                      "4. PRACTICE QUESTIONS: 5 potential exam/viva questions with answers based strictly on this document.\n" .
                      "Use clear Markdown formatting with ## headings and bullet points.";

            $systemInstruction = "You are an expert university research analyst who creates concise, high-yield academic summaries from syllabus handouts and research papers.";

            $aiResult = askOpenAI($prompt, $systemInstruction, null, 0.4);

            if (!$aiResult['success']) {
                jsonResponse(['success' => false, 'error' => $aiResult['error'] ?? 'AI summarization failed.'], 502);
            }

            $fullContent = $aiResult['content'];

            // Store summary in database
            // Delete old summary if re-summarizing
            $delStmt = $db->prepare("DELETE FROM pdf_summaries WHERE document_id = ?");
            $delStmt->execute([$docId]);

            $insStmt = $db->prepare("INSERT INTO pdf_summaries (document_id, user_id, summary_type, summary_content) VALUES (?, ?, ?, ?)");
            $insStmt->execute([$docId, $userId, $summaryType, $fullContent]);
            $summaryId = (int)$db->lastInsertId();

            logActivity($userId, 'pdf_summarized', "Summarized document: {$doc['original_filename']}", $db);

            jsonResponse([
                'success'           => true,
                'summary_id'        => $summaryId,
                'document_id'       => $docId,
                'original_filename' => $doc['original_filename'],
                'summary_content'   => $fullContent,
                'created_at'        => date('M d, Y h:i A')
            ]);

        } catch (Exception $e) {
            error_log("Summarize API error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
        break;
}

