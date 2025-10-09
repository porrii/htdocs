<?php
require_once __DIR__ . '/../../../config/cors.php';
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Método no permitido", 405);
}

try {
    AuthMiddleware::requireAdmin();

    $database = new Database();
    $db = $database->getConnection();

    // Total de citas
    $totalQuery = "SELECT COUNT(*) as total FROM citas";
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute();
    $total = $totalStmt->fetch()['total'];

    // Citas por estado
    $statusQuery = "SELECT estado, COUNT(*) as count FROM citas GROUP BY estado";
    $statusStmt = $db->prepare($statusQuery);
    $statusStmt->execute();
    $byStatus = $statusStmt->fetchAll();

    // Ingresos totales (solo citas completadas)
    $revenueQuery = "SELECT SUM(s.precio) as total 
                     FROM citas c 
                     INNER JOIN servicios s ON c.servicio_id = s.id 
                     WHERE c.estado = 'completada'";
    $revenueStmt = $db->prepare($revenueQuery);
    $revenueStmt->execute();
    $revenue = $revenueStmt->fetch()['total'] ?? 0;

    // Citas del mes actual
    $monthQuery = "SELECT COUNT(*) as count 
                   FROM citas 
                   WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) 
                   AND YEAR(fecha) = YEAR(CURRENT_DATE())";
    $monthStmt = $db->prepare($monthQuery);
    $monthStmt->execute();
    $thisMonth = $monthStmt->fetch()['count'];

    Response::success([
        'total' => $total,
        'byStatus' => $byStatus,
        'revenue' => $revenue,
        'thisMonth' => $thisMonth
    ], "Estadísticas obtenidas correctamente");

} catch (Exception $e) {
    Response::serverError("Error al obtener estadísticas: " . $e->getMessage());
}
?>
