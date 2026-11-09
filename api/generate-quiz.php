<?php
/**
 * AI Quiz Generator API
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

// CSRF check for quiz generation
validateCsrfToken(true);

if (!checkRateLimit('quiz_gen_' . $userId, 10, 60)) {
    jsonResponse(['success' => false, 'error' => 'Rate limit exceeded. Please wait a moment before generating another quiz.'], 429);
}

$rawInput = file_get_contents('php://input');
$inputData = !empty($rawInput) ? json_decode($rawInput, true) : $_POST;

$subject = trim($inputData['subject'] ?? '');
$topic = trim($inputData['topic'] ?? '');
$difficulty = strtolower(trim($inputData['difficulty'] ?? 'medium'));
$questionCount = (int)($inputData['question_count'] ?? 5);
$questionType = strtolower(trim($inputData['question_type'] ?? 'mcq')); // 'mcq' or 'true_false'

if (empty($subject) || empty($topic)) {
    jsonResponse(['success' => false, 'error' => 'Subject and Topic are required.'], 400);
}

// Bound question count to 5, 10, 15, or 20
if (!in_array($questionCount, [5, 10, 15, 20])) {
    $questionCount = 5;
}

if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
    $difficulty = 'medium';
}

if (!in_array($questionType, ['mcq', 'true_false'])) {
    $questionType = 'mcq';
}

// Prompt OpenAI to generate strict JSON
$typeDesc = $questionType === 'mcq' 
    ? 'Multiple Choice Questions with exactly 4 options labelled A, B, C, D'
    : 'True or False questions with exactly 2 options ("True", "False")';

$prompt = "You are an automated academic examination engine. Generate a rigorous {$difficulty}-level academic quiz for university students.\n" .
          "- Subject: {$subject}\n" .
          "- Topic: {$topic}\n" .
          "- Difficulty: {$difficulty}\n" .
          "- Total Questions: {$questionCount}\n" .
          "- Question Type: {$typeDesc}\n\n" .
          "CRITICAL REQUIREMENT: Output strictly a valid, raw JSON array of objects. Do not write any conversational preamble or markdown code fence around the JSON.\n" .
          "Each object in the array MUST strictly follow this JSON schema:\n" .
          "[\n" .
          "  {\n" .
          "    \"question\": \"The exact question text?\",\n" .
          ($questionType === 'mcq' 
            ? "    \"options\": {\"A\": \"First choice\", \"B\": \"Second choice\", \"C\": \"Third choice\", \"D\": \"Fourth choice\"},\n    \"correct_answer\": \"A\",\n"
            : "    \"options\": {\"True\": \"True\", \"False\": \"False\"},\n    \"correct_answer\": \"True\",\n") .
          "    \"explanation\": \"Clear, step-by-step academic explanation why this answer is correct.\"\n" .
          "  }\n" .
          "]";

$systemInstruction = "You are an AI exam evaluation engine that generates strictly formatted JSON quiz data without formatting errors.";

$aiResult = askOpenAI($prompt, $systemInstruction, null, 0.4);

if (!$aiResult['success']) {
    jsonResponse(['success' => false, 'error' => $aiResult['error'] ?? 'AI quiz generation failed.'], 502);
}

// Parse AI response into JSON
$rawContent = trim($aiResult['content']);
$rawContent = preg_replace('/^```(?:json)?/mi', '', $rawContent);
$rawContent = preg_replace('/```\s*$/m', '', $rawContent);
$rawContent = trim($rawContent);

$parsedQuestions = json_decode($rawContent, true);

if (!is_array($parsedQuestions) || empty($parsedQuestions)) {
    // Attempt fallback json extraction via regex (matches first '[' to last ']')
    $firstBracket = strpos($rawContent, '[');
    $lastBracket = strrpos($rawContent, ']');
    if ($firstBracket !== false && $lastBracket !== false && $lastBracket > $firstBracket) {
        $jsonCandidate = substr($rawContent, $firstBracket, $lastBracket - $firstBracket + 1);
        $parsedQuestions = json_decode($jsonCandidate, true);
    }
}

if (!is_array($parsedQuestions) || empty($parsedQuestions)) {
    error_log("Failed to parse quiz JSON from OpenAI: " . substr($rawContent, 0, 300));
    jsonResponse(['success' => false, 'error' => 'AI returned an unparseable response. Please try again.'], 502);
}

try {
    $db->beginTransaction();

    // 1. Create quiz record
    $qStmt = $db->prepare("INSERT INTO quizzes (user_id, subject, topic, difficulty, question_count, question_type) VALUES (?, ?, ?, ?, ?, ?)");
    $qStmt->execute([$userId, $subject, $topic, $difficulty, count($parsedQuestions), $questionType]);
    $quizId = (int)$db->lastInsertId();

    $sanitizedQuestionsForBrowser = [];

    // 2. Insert questions with answer key in DB
    $insQ = $db->prepare("INSERT INTO quiz_questions (quiz_id, question, options, correct_answer, explanation, question_order) VALUES (?, ?, ?, ?, ?, ?)");

    $order = 1;
    foreach ($parsedQuestions as $q) {
        $questionText = trim($q['question'] ?? 'Question ' . $order);
        $options = $q['options'] ?? [];
        $correct = trim((string)($q['correct_answer'] ?? 'A'));
        $explanation = trim($q['explanation'] ?? 'Standard correct option.');

        $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);
        $insQ->execute([$quizId, $questionText, $optionsJson, $correct, $explanation, $order]);
        $questionDbId = (int)$db->lastInsertId();

        // STRICT SECURITY REQUIREMENT:
        // Exclude correct_answer and explanation from the browser payload!
        $sanitizedQuestionsForBrowser[] = [
            'id'             => $questionDbId,
            'question_order' => $order,
            'question'       => $questionText,
            'options'        => $options
        ];

        $order++;
    }

    $db->commit();
    logActivity($userId, 'quiz_created', "Created quiz: {$topic} ({$difficulty})", $db);

    jsonResponse([
        'success'        => true,
        'quiz_id'        => $quizId,
        'subject'        => $subject,
        'topic'          => $topic,
        'difficulty'     => $difficulty,
        'question_type'  => $questionType,
        'question_count' => count($sanitizedQuestionsForBrowser),
        'questions'      => $sanitizedQuestionsForBrowser
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Quiz creation error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Database error saving quiz: ' . $e->getMessage()], 500);
}

