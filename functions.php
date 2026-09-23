<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function ensure_hospital_cover_photo_column() {
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM hospitals LIKE 'cover_photo'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE hospitals ADD COLUMN cover_photo VARCHAR(255) DEFAULT NULL AFTER license_file");
        }
    } catch (Exception $e) {
        // Migration check fail-safe
    }
}

ensure_hospital_cover_photo_column();

// Dynamic base path determination
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
$appRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
$baseUrl = ($docRoot !== '' && strpos($appRoot, $docRoot) === 0)
    ? substr($appRoot, strlen($docRoot))
    : '';
define('BASE_URL', $baseUrl);

function esc($s) {
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function is_ajax() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || isset($_POST['ajax']) || isset($_GET['ajax']);
}

function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $val = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $val;
    }
    return null;
}

function old($field) {
    return esc($_SESSION['old'][$field] ?? '');
}

function set_old($data) {
    $_SESSION['old'] = $data;
}

function clear_old() {
    unset($_SESSION['old']);
}

function validate_phone($phone) {
    $phone = trim((string)$phone);
    if ($phone === '') return true;
    $digits = preg_replace('/[^0-9]/', '', $phone);
    return strlen($digits) === 10 && preg_match('/^[0-9+\-\s]{10,15}$/', $phone) === 1;
}

function validate_blood_group($blood) {
    $blood = trim((string)$blood);
    if ($blood === '') return true;
    $valid = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    return in_array(strtoupper($blood), $valid, true);
}

function validate_future_or_today_date($dateStr) {
    $d = trim((string)$dateStr);
    if ($d === '') return false;
    $time = strtotime($d);
    return $time !== false && $time >= strtotime(date('Y-m-d'));
}

function validate_file_upload($fileArray, array $allowedExts, $maxBytes = 5242880, array $allowedMimes = []) {
    if (empty($fileArray) || !isset($fileArray['error'])) {
        return 'No file uploaded.';
    }
    if ($fileArray['error'] === UPLOAD_ERR_NO_FILE) {
        return 'File is required.';
    }
    if ($fileArray['error'] !== UPLOAD_ERR_OK) {
        return 'File upload failed with error code ' . (int)$fileArray['error'] . '.';
    }
    if ($fileArray['size'] > $maxBytes) {
        $maxMb = round($maxBytes / (1024 * 1024), 1);
        return "File size exceeds the max limit of {$maxMb}MB.";
    }

    $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return 'Invalid file extension. Allowed extensions: ' . implode(', ', $allowedExts);
    }

    if (!empty($allowedMimes) && function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileArray['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes, true)) {
            return 'Invalid file type (' . esc($mime) . ').';
        }
    }

    return null;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

function current_user() {
    global $pdo;
    if (!is_logged_in()) return null;
    static $cached = null;
    if ($cached !== null) return $cached;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cached = $stmt->fetch();
    return $cached;
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/auth/login.php');
    }
}

function require_role($role) {
    require_login();
    if (current_role() !== $role) {
        redirect('/index.php');
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf() {
    $sent = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        if (is_ajax()) {
            json_response(['success' => false, 'message' => 'Session expired. Please refresh the page.'], 419);
        }
        flash('error', 'Session expired. Please refresh the page and try again.');
        $back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/');
        header('Location: ' . $back);
        exit;
    }
}

function badge($status) {
    $map = [
        'pending'    => 'badge-pending',
        'accepted'   => 'badge-accepted',
        'approved'   => 'badge-approved',
        'available'  => 'badge-available',
        'rejected'   => 'badge-rejected',
        'occupied'   => 'badge-occupied',
        'discharged' => 'badge-discharged',
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return '<span class="badge ' . $cls . '">' . esc(ucfirst($status)) . '</span>';
}

function hospital_by_id($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM hospitals WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function category_by_id($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM bed_categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function categories_of_hospital($hospitalId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM bed_categories WHERE hospital_id = ? ORDER BY id");
    $stmt->execute([$hospitalId]);
    return $stmt->fetchAll();
}

function available_count($categoryId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM beds WHERE category_id = ? AND status = 'available'");
    $stmt->execute([$categoryId]);
    return (int)$stmt->fetchColumn();
}

function occupied_count($categoryId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM beds WHERE category_id = ? AND status = 'occupied'");
    $stmt->execute([$categoryId]);
    return (int)$stmt->fetchColumn();
}

function total_beds_of_hospital($hospitalId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM beds WHERE hospital_id = ?");
    $stmt->execute([$hospitalId]);
    return (int)$stmt->fetchColumn();
}

function available_beds_of_hospital($hospitalId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM beds WHERE hospital_id = ? AND status = 'available'");
    $stmt->execute([$hospitalId]);
    return (int)$stmt->fetchColumn();
}

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

function hospital_photos($hospitalId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM hospital_photos WHERE hospital_id = ? ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([$hospitalId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function hospital_cover_photo($hospitalId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM hospitals LIKE 'cover_photo'");
        $stmt->execute();
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("SELECT cover_photo FROM hospitals WHERE id = ?");
            $stmt->execute([$hospitalId]);
            $row = $stmt->fetch();
            if ($row && !empty($row['cover_photo'])) {
                return BASE_URL . '/assets/images/hospital_photos/' . (int)$hospitalId . '/' . rawurlencode($row['cover_photo']);
            }
        }
    } catch (Exception $e) {
        // Fallback check
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT filename FROM hospital_photos WHERE hospital_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1"
        );
        $stmt->execute([$hospitalId]);
        $row = $stmt->fetch();
        if ($row) {
            return BASE_URL . '/assets/images/hospital_photos/' . (int)$hospitalId . '/' . rawurlencode($row['filename']);
        }
    } catch (Exception $e) {
        return null;
    }

    return null;
}

function delete_hospital($hospitalId) {
    global $pdo;
    $hospitalId = (int)$hospitalId;

    $stmt = $pdo->prepare("SELECT * FROM hospitals WHERE id = ?");
    $stmt->execute([$hospitalId]);
    $h = $stmt->fetch();
    if (!$h) return false;

    $photoDir = __DIR__ . '/../assets/images/hospital_photos/' . $hospitalId;
    if (is_dir($photoDir)) {
        $files = glob($photoDir . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) @unlink($file);
            }
        }
        @rmdir($photoDir);
    }

    if (!empty($h['license_file'])) {
        $licensePath = __DIR__ . '/../assets/uploads/licenses/' . $h['license_file'];
        if (is_file($licensePath)) @unlink($licensePath);
    }

    $pdo->prepare("UPDATE users SET hospital_id = NULL WHERE hospital_id = ?")->execute([$hospitalId]);
    $pdo->prepare("DELETE FROM hospitals WHERE id = ?")->execute([$hospitalId]);

    return true;
}

function delete_booking($bookingId, $hospitalId = null) {
    global $pdo;
    $bookingId = (int)$bookingId;

    if ($hospitalId !== null) {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND hospital_id = ?");
        $stmt->execute([$bookingId, (int)$hospitalId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
    }
    $b = $stmt->fetch();
    if (!$b) return false;

    if ($b['status'] === 'accepted') {
        $stmtBed = $pdo->prepare("SELECT id FROM beds WHERE category_id = ? AND hospital_id = ? AND status = 'occupied' LIMIT 1");
        $stmtBed->execute([$b['category_id'], $b['hospital_id']]);
        $bed = $stmtBed->fetch();
        if ($bed) {
            $pdo->prepare("UPDATE beds SET status = 'available' WHERE id = ?")->execute([$bed['id']]);
        }
    }

    $pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([$bookingId]);
    return true;
}

function delete_bed_category($categoryId, $hospitalId) {
    global $pdo;
    $categoryId = (int)$categoryId;
    $hospitalId = (int)$hospitalId;

    $cat = category_by_id($categoryId);
    if (!$cat || (int)$cat['hospital_id'] !== $hospitalId) {
        return ['success' => false, 'message' => 'Bed category not found.'];
    }

    $occ = occupied_count($categoryId);
    if ($occ > 0) {
        return [
            'success' => false,
            'message' => "Cannot remove category '" . $cat['name'] . "' because " . $occ . " bed(s) are currently occupied. Please discharge or reassign patients first."
        ];
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM bookings WHERE category_id = ? AND hospital_id = ?")->execute([$categoryId, $hospitalId]);
        $pdo->prepare("DELETE FROM beds WHERE category_id = ? AND hospital_id = ?")->execute([$categoryId, $hospitalId]);
        $pdo->prepare("DELETE FROM bed_categories WHERE id = ? AND hospital_id = ?")->execute([$categoryId, $hospitalId]);
        $pdo->commit();

        return ['success' => true, 'message' => "Category '" . $cat['name'] . "' and its beds have been removed."];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Failed to delete category: ' . $e->getMessage()];
    }
}

