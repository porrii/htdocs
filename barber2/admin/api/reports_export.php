<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../vendor/tfpdf/tfpdf.php';

$database = new Database();
$db = $database->getConnection();

// Capturar filtros desde GET
$fechaInicio = trim($_GET['from_date'] ?? '');
$fechaFin    = trim($_GET['to_date'] ?? '');
$cliente     = trim($_GET['client_name'] ?? '');
$estado      = trim($_GET['status'] ?? '');

// Query base
$query = "SELECT a.*, s.name AS service_name, s.price 
          FROM appointments a 
          JOIN services s ON a.service_id = s.id 
          WHERE 1=1";

$params = [];

// Aplicar filtros si tienen valor
if ($fechaInicio !== '') { 
    $query .= " AND a.appointment_date >= ?"; 
    $params[] = $fechaInicio; 
}
if ($fechaFin !== '') { 
    $query .= " AND a.appointment_date <= ?"; 
    $params[] = $fechaFin; 
}
if ($cliente !== '') { 
    $query .= " AND a.client_name LIKE ?"; 
    $params[] = "%$cliente%"; 
}
if ($estado !== '') { 
    $query .= " AND a.status = ?"; 
    $params[] = $estado; 
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totales solo de completadas
$totalCitas = 0;
$totalIngresos = 0;

foreach ($appointments as $appt) {
    if ($appt['status'] === 'completed') {
        $totalCitas++;
        $totalIngresos += $appt['price'];
    }
}

// ========================
// PDF con tFPDF
// ========================
$pdf = new tFPDF();
$pdf->AddPage();

// Fuente con soporte UTF-8
$pdf->AddFont('DejaVu','','DejaVuSans.ttf',true);
$pdf->AddFont('DejaVu','B','DejaVuSans-Bold.ttf',true);
$pdf->AddFont('DejaVu','I','DejaVuSans-Oblique.ttf',true);
$pdf->AddFont('DejaVu','BI','DejaVuSans-BoldOblique.ttf',true);
$pdf->SetFont('DejaVu','',12);

// Encabezado empresa
$pdf->SetTextColor(22,78,99); // azul oscuro
$pdf->SetFont('DejaVu','',18);
$pdf->Cell(0,10,SITE_NAME,0,1,'C');

$pdf->SetFont('DejaVu','',10);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(0,6,SITE_LOCATION,0,1,'C');
$pdf->Cell(0,6,'Tel: '.SITE_NUMBER,0,1,'C');
$pdf->Cell(0,6,SITE_URL,0,1,'C');

$pdf->Ln(8);

// Título informe
$pdf->SetFont('DejaVu','',14);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(0,10,'Informe de Citas',0,1,'L');

$pdf->SetFont('DejaVu','',10);
$pdf->Cell(0,6,'Generado: '.date('d/m/Y H:i'),0,1,'L');

if ($fechaInicio || $fechaFin || $cliente || $estado) {
    $pdf->Ln(2);
    $pdf->SetFont('DejaVu','',9);
    $pdf->SetTextColor(100,100,100);
    $filtros = [];
    if ($fechaInicio) $filtros[] = "Desde: $fechaInicio";
    if ($fechaFin) $filtros[] = "Hasta: $fechaFin";
    if ($cliente) $filtros[] = "Cliente: $cliente";
    if ($estado) $filtros[] = "Estado: $estado";
    $pdf->MultiCell(0,6,"Filtros aplicados: ".implode(" | ", $filtros));
}
$pdf->Ln(5);

// Tabla encabezado
$pdf->SetFont('DejaVu','B',10);
$pdf->SetFillColor(22,78,99);
$pdf->SetTextColor(255,255,255);
$pdf->Cell(10,8,'#',1,0,'C',true);
$pdf->Cell(40,8,'Cliente',1,0,'C',true);
$pdf->Cell(40,8,'Servicio',1,0,'C',true);
$pdf->Cell(25,8,'Fecha',1,0,'C',true);
$pdf->Cell(25,8,'Hora',1,0,'C',true);
$pdf->Cell(25,8,'Estado',1,0,'C',true);
$pdf->Cell(25,8,'Precio',1,1,'C',true);

// Tabla datos
$pdf->SetFont('DejaVu','',9);
$pdf->SetTextColor(0,0,0);
$fill = false;
foreach ($appointments as $i => $a) {
    $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
    $pdf->Cell(10,7,$i+1,1,0,'C',true);
    $pdf->Cell(40,7,$a['client_name'],1,0,'L',true);
    $pdf->Cell(40,7,$a['service_name'],1,0,'L',true);
    $pdf->Cell(25,7,date('d/m/Y',strtotime($a['appointment_date'])),1,0,'C',true);
    $pdf->Cell(25,7,substr($a['appointment_time'],0,5),1,0,'C',true);
    $pdf->Cell(25,7,ucfirst($a['status']),1,0,'C',true);
    $pdf->Cell(25,7,'€'.number_format($a['price'],2,',','.'),1,1,'R',true);
    $fill = !$fill;
}

// Totales
$pdf->Ln(5);
$pdf->SetFont('DejaVu','B',11);
$pdf->SetTextColor(22,78,99);
$pdf->Cell(0,6,"Totales",0,1);

$pdf->SetFont('DejaVu','',10);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(0,6,"Citas completadas: $totalCitas",0,1);
$pdf->Cell(0,6,"Ingresos totales: €".number_format($totalIngresos,2,',','.'),0,1);

$pdf->Ln(10);
$pdf->SetFont('DejaVu','I',9);
$pdf->SetTextColor(100,100,100);
$pdf->MultiCell(0,5,"Este informe ha sido generado automáticamente por ".SITE_NAME.". Para cualquier consulta, visite ".SITE_URL);

$pdf->Output('I','informe_citas.pdf');
