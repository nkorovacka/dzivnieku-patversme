<?php
// api/my_applications.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../db_conn.php'; // izmanto esošo savienojumu

try {
    // Ja tev ir sesijas autentifikācija, vari izmantot $_SESSION['user_id']
    // session_start();
    // $userId = $_SESSION['user_id'] ?? null;

    $conn->set_charset("utf8mb4");

    // Filtri (neobligāti): status, animal_type
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $atype  = isset($_GET['type']) ? trim($_GET['type']) : null;
    $q      = "SELECT id, user_id, applicant_name, applicant_email, applicant_phone, animal_name, animal_type, shelter_branch, message, status, created_at
               FROM applications WHERE 1=1";
    $params = [];
    $types  = "";

    if ($status) {
        $q .= " AND status = ?";
        $params[] = $status;
        $types   .= "s";
    }
    if ($atype) {
        $q .= " AND animal_type = ?";
        $params[] = $atype;
        $types   .= "s";
    }

    $q .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($q);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode([
        "ok" => true,
        "count" => count($rows),
        "data" => $rows
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
