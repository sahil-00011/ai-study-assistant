<?php
/**
 * Server-Side Quiz Scoring and Submission API
 * AI Study Assistant
 */

define('IS_API', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
}

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$db = getDbConnection();

// Validate CSRF
validateCsrfToken(true);

$rawInput = file_get_contents('php://input');
$inputData = !empty($rawInput) ? json_decode($rawInput, true) : $_POST;

$quizId = (int)($inputData['quiz_id'] ?? 0);
$answers = $inputData['answers'] ?? []; // Associative array: question_id => selected_answer
$timeTaken = (int)($inputData['time_taken_seconds'] ?? 0);

if ($quizId <= 0) {
    jsonResponse(['success' => false, 'error' => 'Invalid quiz identifier.'], 400);
}

try {
    // 1. Verify quiz exists and belongs to the user
    $qCheck = $db->prepare("SELECT id, subject, topic, difficulty, question_count FROM quizzes WHERE id = ? AND user_id = ? LIMIT 1");
    $qCheck->execute([$quizId, $userId]);
    $quiz = $qCheck->fetch();

    if (!$quiz) {
        jsonResponse(['success' => false, 'error' => 'Quiz not found or unauthorized.'], 404);
    }

    // 2. Fetch all real questions, options, correct answers and explanations from database
    $qqStmt = $db->prepare("SELECT id, question, options, correct_answer, explanation, question_order FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order ASC");
    $qqStmt->execute([$quizId]);
    $questions = $qqStmt->fetchAll();

    if (empty($questions)) {
        jsonResponse(['success' => false, 'error' => 'This quiz contains no questions.'], 400);
    }

    $totalQuestions = count($questions);
    $correctCount = 0;
    $reviewData = [];

    // Begin transaction for saving attempt and answers
    $db->beginTransaction();

    // 3. Grade each question server-side
    foreach ($questions as $q) {
        $qId = (int)$q['id'];
        $correctAnswer = trim($q['correct_answer']);
        $submittedAnswer = isset($answers[$qId]) ? trim((string)$answers[$qId]) : '';

        // Case-insensitive comparison or exact match
        $isCorrect = (strcasecmp($correctAnswer, $submittedAnswer) === 0) ? 1 : 0;
        if ($isCorrect) {
            $correctCount++;
        }

        $options = json_decode($q['options'], true) ?: [];

        $reviewData[] = [
            'question_id'     => $qId,
            'question_order'  => (int)$q['question_order'],
            'question'        => $q['question'],
            'options'         => $options,
            'selected_answer' => $submittedAnswer,
            'correct_answer'  => $correctAnswer,
            'is_correct'      => (bool)$isCorrect,
            'explanation'     => $q['explanation']
        ];
    }

    $scorePercentage = round(($correctCount / $totalQuestions) * 100, 2);
    $incorrectCount = $totalQuestions - $correctCount;

    // 4. Save attempt in quiz_attempts table
    $attStmt = $db->prepare("INSERT INTO quiz_attempts (quiz_id, user_id, total_questions, correct_answers, score_percentage, time_taken_seconds) VALUES (?, ?, ?, ?, ?, ?)");
    $attStmt->execute([$quizId, $userId, $totalQuestions, $correctCount, $scorePercentage, $timeTaken]);
    $attemptId = (int)$db->lastInsertId();

    // 5. Save each individual question answer in quiz_answers table
    $ansStmt = $db->prepare("INSERT INTO quiz_answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
    foreach ($reviewData as $rev) {
        $ansStmt->execute([$attemptId, $rev['question_id'], $rev['selected_answer'], $rev['is_correct'] ? 1 : 0]);
    }

    $db->commit();

    // Formulate encouraging feedback message
    $performanceMessage = '';
    if ($scorePercentage >= 90) {
        $performanceMessage = 'Outstanding! You have mastered this academic concept with high precision.';
    } elseif ($scorePercentage >= 70) {
        $performanceMessage = 'Great job! Strong conceptual grasp. Review the explanations below to refine minor gaps.';
    } elseif ($scorePercentage >= 50) {
        $performanceMessage = 'Good effort! You passed, but reviewing the study notes on this topic will help achieve top marks.';
    } else {
        $performanceMessage = 'Keep practicing! Review the detailed explanations below and generate revision notes on this topic.';
    }

    // Log activity
    logActivity($userId, 'quiz_attempt', "Completed quiz: {$quiz['topic']} with {$scorePercentage}% ({$correctCount}/{$totalQuestions})", $db);

    jsonResponse([
        'success'             => true,
        'attempt_id'          => $attemptId,
        'quiz_id'             => $quizId,
        'subject'             => $quiz['subject'],
        'topic'               => $quiz['topic'],
        'total_questions'     => $totalQuestions,
        'correct_answers'     => $correctCount,
        'incorrect_answers'   => $incorrectCount,
        'score_percentage'    => $scorePercentage,
        'time_taken_seconds'  => $timeTaken,
        'performance_message' => $performanceMessage,
        'review'              => $reviewData
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Quiz submit error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Error grading quiz: ' . $e->getMessage()], 500);
}

