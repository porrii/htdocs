<?php
// require_once __DIR__ . '/../vendor/autoload.php'; // Para TCPDF o Dompdf
require_once __DIR__ . '/../includes/TCPDF/tcpdf.php';

function generateIrrigationReportPDF($data, $date_range, $device_id = null) {
    // Usar TCPDF o Dompdf según tu preferencia
    // Aquí un ejemplo con TCPDF
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurar documento
    $pdf->SetCreator('SmartGarden');
    $pdf->SetAuthor('SmartGarden System');
    $pdf->SetTitle('Reporte de Riego');
    $pdf->SetSubject('Reporte de actividades de riego');
    
    // Agregar página
    $pdf->AddPage();
    
    // Logo
    $pdf->Image('assets/images/logo.png', 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    
    // Título
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 20, 'Reporte de Actividades de Riego', 0, 1, 'C');
    
    // Información del reporte
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Fecha de generación: ' . date('d/m/Y H:i'), 0, 1);
    $pdf->Cell(0, 10, 'Rango: ' . $date_range, 0, 1);
    
    if ($device_id) {
        $pdf->Cell(0, 10, 'Dispositivo: ' . $device_id, 0, 1);
    }
    
    $pdf->Ln(10);
    
    // Tabla de datos
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Fecha', 1, 0, 'C');
    $pdf->Cell(40, 10, 'Dispositivo', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Riegos', 1, 0, 'C');
    $pdf->Cell(40, 10, 'Duración Total', 1, 0, 'C');
    $pdf->Cell(40, 10, 'Consumo (L)', 1, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    
    $total_irrigations = 0;
    $total_duration = 0;
    $total_water = 0;
    
    foreach ($data as $row) {
        $water_consumption = $row['total_duration'] * 2; // 2L por segundo
        
        $pdf->Cell(40, 10, date('d/m/Y', strtotime($row['irrigation_date'])), 1, 0, 'C');
        $pdf->Cell(40, 10, $row['device_name'], 1, 0, 'C');
        $pdf->Cell(30, 10, $row['total_irrigations'], 1, 0, 'C');
        $pdf->Cell(40, 10, gmdate('H:i:s', $row['total_duration']), 1, 0, 'C');
        $pdf->Cell(40, 10, number_format($water_consumption, 2), 1, 1, 'C');
        
        $total_irrigations += $row['total_irrigations'];
        $total_duration += $row['total_duration'];
        $total_water += $water_consumption;
    }
    
    // Totales
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(110, 10, 'TOTALES', 1, 0, 'R');
    $pdf->Cell(30, 10, $total_irrigations, 1, 0, 'C');
    $pdf->Cell(40, 10, gmdate('H:i:s', $total_duration), 1, 0, 'C');
    $pdf->Cell(40, 10, number_format($total_water, 2), 1, 1, 'C');
    
    // Pie de página
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Página ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    
    // Salida
    $pdf->Output('reporte_riego_' . date('Ymd_His') . '.pdf', 'D');
    exit();
}
?>