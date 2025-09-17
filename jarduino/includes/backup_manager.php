<?php
// Gestor de copias de seguridad para SmartGarden

class BackupManager {
    private $pdo;
    private $backup_path;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->backup_path = __DIR__ . '/../backups/';
        
        // Crear directorio de backups si no existe
        if (!file_exists($this->backup_path)) {
            mkdir($this->backup_path, 0755, true);
        }
    }
    
    // Crear una copia de seguridad completa
    public function createBackup($user_id, $description = '') {
        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $filepath = $this->backup_path . $filename;
        
        // Obtener todas las tablas
        $tables = $this->getTables();
        
        // Crear archivo de backup
        $backup_content = "-- SmartGarden Backup\n";
        $backup_content .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- Description: $description\n\n";
        
        // Desactivar claves foráneas
        $backup_content .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        // Volcar cada tabla
        foreach ($tables as $table) {
            $backup_content .= $this->getTableStructure($table);
            $backup_content .= $this->getTableData($table);
        }
        
        // Reactivar claves foráneas
        $backup_content .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Guardar archivo
        if (file_put_contents($filepath, $backup_content)) {
            // Registrar backup en la base de datos
            $filesize = filesize($filepath);
            $stmt = $this->pdo->prepare("
                INSERT INTO backups (user_id, filename, size, description) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $filename, $filesize, $description]);
            
            return [
                'success' => true,
                'filename' => $filename,
                'size' => $this->formatSize($filesize)
            ];
        }
        
        return ['success' => false, 'error' => 'No se pudo crear el archivo de backup'];
    }
    
    // Restaurar desde una copia de seguridad
    public function restoreBackup($filename) {
        $filepath = $this->backup_path . $filename;
        
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Archivo de backup no encontrado'];
        }
        
        // Leer y ejecutar el archivo SQL
        $sql = file_get_contents($filepath);
        
        try {
            $this->pdo->exec($sql);
            return ['success' => true, 'message' => 'Backup restaurado correctamente'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Error al restaurar: ' . $e->getMessage()];
        }
    }
    
    // Obtener lista de backups disponibles
    public function getBackups() {
        $stmt = $this->pdo->prepare("
            SELECT b.*, u.username 
            FROM backups b 
            JOIN users u ON b.user_id = u.user_id 
            ORDER BY b.created_at DESC
        ");
        $stmt->execute();
        $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agregar información del archivo
        foreach ($backups as &$backup) {
            $filepath = $this->backup_path . $backup['filename'];
            $backup['file_exists'] = file_exists($filepath);
            $backup['formatted_size'] = $this->formatSize($backup['size']);
            $backup['formatted_date'] = date('d/m/Y H:i', strtotime($backup['created_at']));
        }
        
        return $backups;
    }
    
    // Eliminar un backup
    public function deleteBackup($backup_id) {
        // Obtener información del backup
        $stmt = $this->pdo->prepare("SELECT filename FROM backups WHERE id = ?");
        $stmt->execute([$backup_id]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($backup) {
            // Eliminar archivo
            $filepath = $this->backup_path . $backup['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Eliminar registro de la base de datos
            $stmt = $this->pdo->prepare("DELETE FROM backups WHERE id = ?");
            $stmt->execute([$backup_id]);
            
            return ['success' => true, 'message' => 'Backup eliminado correctamente'];
        }
        
        return ['success' => false, 'error' => 'Backup no encontrado'];
    }
    
    // Obtener todas las tablas de la base de datos
    private function getTables() {
        $stmt = $this->pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Obtener estructura de una tabla
    private function getTableStructure($table) {
        $stmt = $this->pdo->query("SHOW CREATE TABLE $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['Create Table'] . ";\n\n";
    }
    
    // Obtener datos de una tabla
    private function getTableData($table) {
        $output = "";
        $stmt = $this->pdo->query("SELECT * FROM $table");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns = implode('`, `', array_keys($row));
            $values = array_map([$this, 'escapeValue'], array_values($row));
            $values = implode(', ', $values);
            
            $output .= "INSERT INTO `$table` (`$columns`) VALUES ($values);\n";
        }
        
        return $output . "\n";
    }
    
    // Escapar valores para SQL
    private function escapeValue($value) {
        if ($value === null) {
            return 'NULL';
        }
        
        return $this->pdo->quote($value);
    }
    
    // Formatear tamaño de archivo
    private function formatSize($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
    
    // Crear backup automático programado
    public function scheduledBackup() {
        // Realizar backup solo si es el primer día del mes
        if (date('j') === '1') {
            $result = $this->createBackup(1, 'Backup automático mensual');
            
            if ($result['success']) {
                error_log("Backup automático creado: " . $result['filename']);
            } else {
                error_log("Error en backup automático: " . $result['error']);
            }
            
            return $result;
        }
        
        return ['success' => false, 'message' => 'No es día de backup automático'];
    }
}