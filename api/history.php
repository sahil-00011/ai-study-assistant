<?php
/**
 * Learning History API Endpoint
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

$action = $_GET['action'] ?? '';
$rawInput = file_get_contents('php://input');
$inputData = !empty($rawInput) ? json_decode($rawInput, true) : [];
if (!empty($inputData['action'])) {
    $action = $inputData['action'];
}

switch ($action) {
    case 'list':
        try {
            $type = trim($_GET['type'] ?? 'all');
            $search = trim($_GET['q'] ?? '');
            $sort = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

            $sql = "SELECT id, activity_type, description, ip_address, created_at FROM activity_logs WHERE user_id = ?";
            $params = [$userId];

            if ($type !== 'all') {
                $sql .= " AND activity_type LIKE ?";
                $params[] = "%{$type}%";
            }

            if (!empty($search)) {
                $sql .= " AND description LIKE ?";
                $params[] = "%{$search}%";
            }

            $sql .= " ORDER BY created_at {$sort} LIMIT 100";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();

            jsonResponse(['success' => true, 'logs' => $logs]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to load logs: ' . $e->getMessage()], 500);
        }
        break;

    case 'delete':
        validateCsrfToken(true);
        $id = (int)($inputData['id'] ?? ($_POST['id'] ?? 0));
        if ($id <= 0) {
            jsonResponse(['success' => false, 'error' => 'Invalid record ID.'], 400);
        }

        try {
            $stmt = $db->prepare("DELETE FROM activity_logs WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            jsonResponse(['success' => true, 'message' => 'History record deleted.']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to delete record: ' . $e->getMessage()], 500);
        }
        break;

    case 'clear_all':
        validateCsrfToken(true);
        try {
            $stmt = $db->prepare("DELETE FROM activity_logs WHERE user_id = ?");
            $stmt->execute([$userId]);
            jsonResponse(['success' => true, 'message' => 'All history cleared.']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to clear history: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
        break;
}

