<?php
/**
 * Study Planner Tasks API Endpoint
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
            $status = trim($_GET['status'] ?? 'all');
            $priority = trim($_GET['priority'] ?? 'all');
            $category = trim($_GET['category'] ?? 'all');
            $search = trim($_GET['q'] ?? '');

            $sql = "SELECT id, title, description, category, priority, due_date, status, created_at, updated_at FROM study_tasks WHERE user_id = ?";
            $params = [$userId];

            if ($status === 'pending' || $status === 'completed') {
                $sql .= " AND status = ?";
                $params[] = $status;
            }

            if (in_array($priority, ['low', 'medium', 'high'])) {
                $sql .= " AND priority = ?";
                $params[] = $priority;
            }

            if (!empty($category) && $category !== 'all') {
                $sql .= " AND category = ?";
                $params[] = $category;
            }

            if (!empty($search)) {
                $sql .= " AND (title LIKE ? OR description LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            $sql .= " ORDER BY (status = 'pending') DESC, (due_date IS NULL), due_date ASC, created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $tasks = $stmt->fetchAll();

            // Compute summary statistics
            $statStmt = $db->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'pending' AND due_date IS NOT NULL AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue
                FROM study_tasks WHERE user_id = ?");
            $statStmt->execute([$userId]);
            $stats = $statStmt->fetch();

            jsonResponse([
                'success' => true,
                'tasks'   => $tasks,
                'stats'   => [
                    'total'     => (int)($stats['total'] ?? 0),
                    'pending'   => (int)($stats['pending'] ?? 0),
                    'completed' => (int)($stats['completed'] ?? 0),
                    'overdue'   => (int)($stats['overdue'] ?? 0)
                ]
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    case 'create':
        validateCsrfToken(true);

        $title = trim($inputData['title'] ?? ($_POST['title'] ?? ''));
        $description = trim($inputData['description'] ?? ($_POST['description'] ?? ''));
        $category = trim($inputData['category'] ?? ($_POST['category'] ?? 'General'));
        $priority = strtolower(trim($inputData['priority'] ?? ($_POST['priority'] ?? 'medium')));
        $dueDate = !empty($inputData['due_date']) ? $inputData['due_date'] : (!empty($_POST['due_date']) ? $_POST['due_date'] : null);

        if (empty($title)) {
            jsonResponse(['success' => false, 'error' => 'Task title is required.'], 400);
        }

        if (!in_array($priority, ['low', 'medium', 'high'])) {
            $priority = 'medium';
        }

        try {
            $stmt = $db->prepare("INSERT INTO study_tasks (user_id, title, description, category, priority, due_date, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$userId, $title, $description, $category, $priority, $dueDate]);
            $taskId = (int)$db->lastInsertId();

            logActivity($userId, 'task_created', "Created task: {$title}", $db);

            $fetchStmt = $db->prepare("SELECT * FROM study_tasks WHERE id = ?");
            $fetchStmt->execute([$taskId]);
            $newTask = $fetchStmt->fetch();

            jsonResponse(['success' => true, 'task' => $newTask]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to create task: ' . $e->getMessage()], 500);
        }
        break;

    case 'toggle_status':
        validateCsrfToken(true);
        $taskId = (int)($inputData['id'] ?? ($_POST['id'] ?? 0));
        if ($taskId <= 0) {
            jsonResponse(['success' => false, 'error' => 'Invalid task ID.'], 400);
        }

        try {
            $checkStmt = $db->prepare("SELECT id, title, status FROM study_tasks WHERE id = ? AND user_id = ? LIMIT 1");
            $checkStmt->execute([$taskId, $userId]);
            $task = $checkStmt->fetch();
            if (!$task) {
                jsonResponse(['success' => false, 'error' => 'Task not found or access denied.'], 404);
            }

            $newStatus = ($task['status'] === 'pending') ? 'completed' : 'pending';
            $updStmt = $db->prepare("UPDATE study_tasks SET status = ? WHERE id = ?");
            $updStmt->execute([$newStatus, $taskId]);

            if ($newStatus === 'completed') {
                logActivity($userId, 'task_completed', "Completed task: {$task['title']}", $db);
            }

            jsonResponse(['success' => true, 'id' => $taskId, 'status' => $newStatus]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to update task: ' . $e->getMessage()], 500);
        }
        break;

    case 'update':
        validateCsrfToken(true);
        $taskId = (int)($inputData['id'] ?? ($_POST['id'] ?? 0));
        $title = trim($inputData['title'] ?? ($_POST['title'] ?? ''));
        $description = trim($inputData['description'] ?? ($_POST['description'] ?? ''));
        $category = trim($inputData['category'] ?? ($_POST['category'] ?? 'General'));
        $priority = strtolower(trim($inputData['priority'] ?? ($_POST['priority'] ?? 'medium')));
        $dueDate = !empty($inputData['due_date']) ? $inputData['due_date'] : (!empty($_POST['due_date']) ? $_POST['due_date'] : null);

        if ($taskId <= 0 || empty($title)) {
            jsonResponse(['success' => false, 'error' => 'Task ID and title are required.'], 400);
        }

        if (!in_array($priority, ['low', 'medium', 'high'])) {
            $priority = 'medium';
        }

        try {
            $stmt = $db->prepare("UPDATE study_tasks SET title = ?, description = ?, category = ?, priority = ?, due_date = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $description, $category, $priority, $dueDate, $taskId, $userId]);
            jsonResponse(['success' => true, 'message' => 'Task updated successfully.']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to update task: ' . $e->getMessage()], 500);
        }
        break;

    case 'delete':
        validateCsrfToken(true);
        $taskId = (int)($inputData['id'] ?? ($_POST['id'] ?? 0));
        if ($taskId <= 0) {
            jsonResponse(['success' => false, 'error' => 'Invalid task ID.'], 400);
        }

        try {
            $stmt = $db->prepare("DELETE FROM study_tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$taskId, $userId]);
            jsonResponse(['success' => true, 'message' => 'Task deleted successfully.']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to delete task: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
        break;
}

