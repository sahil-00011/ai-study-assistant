<?php
/**
 * PDF Upload & Extraction API Endpoint
 * AI Study Assistant
 */

define('IS_API', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/PdfExtractor.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
}

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$db = getDbConnection();

// CSRF check
validateCsrfToken(true);

if (!checkRateLimit('pdf_up_' . $userId, 10, 60)) {
    jsonResponse(['success' => false, 'error' => 'Upload rate limit reached. Please wait a minute.'], 429);
}

if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE;
    $msg = match($errCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds the maximum allowed size.',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No PDF file was provided.',
        default => 'An error occurred during file upload.'
    };
    jsonResponse(['success' => false, 'error' => $msg], 400);
}

$file = $_FILES['pdf'];
$originalName = basename($file['name']);
$fileSize = (int)$file['size'];
$tmpPath = $file['tmp_name'];

// 1. Restrict File Size: Max 10MB
if ($fileSize > 10 * 1024 * 1024) {
    jsonResponse(['success' => false, 'error' => 'File size exceeds maximum limit of 10MB.'], 400);
}

// 2. Validate Extension
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    jsonResponse(['success' => false, 'error' => 'Only PDF (.pdf) documents are accepted.'], 400);
}

// 3. Validate MIME type using fileinfo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $tmpPath);
finfo_close($finfo);

if ($mimeType !== 'application/pdf') {
    jsonResponse(['success' => false, 'error' => 'Invalid file content: Not a valid PDF document.'], 400);
}

// 4. Generate Random Server-side Filename
if (!is_dir(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
}

$storedName = 'doc_' . bin2hex(random_bytes(16)) . '.pdf';
$destPath = UPLOADS_PATH . DIRECTORY_SEPARATOR . $storedName;

if (!move_uploaded_file($tmpPath, $destPath)) {
    jsonResponse(['success' => false, 'error' => 'Failed to store uploaded document on server.'], 500);
}

// 5. Extract Text from PDF
$extractedText = PdfExtractor::extractText($destPath);

if (empty(trim($extractedText))) {
    // If the PDF is scanned / empty, inform the student
    $extractedText = "Notice: This document appears to be a scanned image or uses non-standard embedded font streams. Basic text could not be extracted automatically.";
}

try {
    $stmt = $db->prepare("INSERT INTO pdf_documents (user_id, original_filename, stored_filename, file_path, file_size, extracted_text) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $originalName, $storedName, $storedName, $fileSize, $extractedText]);
    $docId = (int)$db->lastInsertId();

    logActivity($userId, 'pdf_upload', "Uploaded document: {$originalName}", $db);

    $previewText = mb_substr($extractedText, 0, 300, 'UTF-8');
    if (mb_strlen($extractedText) > 300) $previewText .= '...';

    jsonResponse([
        'success'           => true,
        'document_id'       => $docId,
        'original_filename' => $originalName,
        'file_size'         => $fileSize,
        'char_count'        => mb_strlen($extractedText, 'UTF-8'),
        'preview_text'      => $previewText
    ]);

} catch (Exception $e) {
    // Clean up file if database insert fails
    if (file_exists($destPath)) {
        unlink($destPath);
    }
    error_log("PDF upload DB error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Database error recording document: ' . $e->getMessage()], 500);
}

