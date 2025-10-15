<?php
// api/my_applications.php — return current user's applications from pieteikumi
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../db_conn.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode([ 'ok' => true, 'count' => 0, 'data' => [] ]);
    exit;
}

try {
    $conn->set_charset('utf8mb4');

    $status = isset($_GET['status']) ? trim($_GET['status']) : null; // jauns/procesā/apstiprināts/atteikts
    $atype  = isset($_GET['type']) ? trim($_GET['type']) : null;     // suns/kaķis

    $back = [ 'jauns' => 'gaida_apstiprinajumu', 'procesā' => 'procesa', 'apstiprināts' => 'apstiprinats', 'atteikts' => 'atteikts' ];
    $toFront = function($s) {
        switch ($s) {
            case 'gaida_apstiprinajumu': return 'jauns';
            case 'procesa': return 'procesā';
            case 'apstiprinats': return 'apstiprināts';
            case 'atteikts': return 'atteikts';
            default: return 'jauns';
        }
    };

    $sql = "SELECT p.id, p.lietotaja_id, p.pieteikuma_teksts, p.statuss,
                   d.vards AS animal_name, d.suga AS animal_type
            FROM pieteikumi p
            JOIN dzivnieki d ON d.id = p.dzivnieka_id
            WHERE p.lietotaja_id = ?";

    $types = 'i';
    $params = [ $userId ];
    if ($status && isset($back[$status])) {
        $sql .= ' AND p.statuss = ?';
        $types .= 's';
        $params[] = $back[$status];
    }
    if ($atype) {
        $sql .= ' AND d.suga = ?';
        $types .= 's';
        $params[] = $atype;
    }

    // Order newest first by primary key as a proxy for creation time
    $sql .= ' ORDER BY p.id DESC';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'id' => (int)$r['id'],
            'user_id' => (int)$r['lietotaja_id'],
            'applicant_name' => $_SESSION['lietotajvards'] ?? '',
            'applicant_email' => $_SESSION['epasts'] ?? '',
            'applicant_phone' => null,
            'animal_name' => $r['animal_name'],
            'animal_type' => $r['animal_type'],
            'shelter_branch' => null,
            'message' => $r['pieteikuma_teksts'] ?? '',
            'status' => $toFront($r['statuss'] ?? ''),
            'created_at' => date('c'),
        ];
    }

    echo json_encode([ 'ok' => true, 'count' => count($rows), 'data' => $rows ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([ 'ok' => false, 'error' => $e->getMessage() ], JSON_UNESCAPED_UNICODE);
}
