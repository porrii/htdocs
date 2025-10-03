<?php
    $title = 'Mis Dispositivos - SmartGarden';
    include 'includes/header.php';

    // Obtener dispositivos del usuario con su configuración
    $stmt = $pdo->prepare("SELECT d.*, dc.auto_irrigation, dc.threshold, dc.duration 
                        FROM devices d 
                        LEFT JOIN device_config dc ON d.device_id = dc.device_id 
                        WHERE d.user_id = ?");
    $stmt->execute([$user_id]);
    $devices_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar edición de dispositivo
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_device'])) {
        $device_id = $_POST['device_id'];
        $name = $_POST['name'];
        $location = $_POST['location'];
        $soil_threshold = $_POST['soil_threshold'];
        $auto_irrigation = isset($_POST['auto_irrigation']) ? 1 : 0;
        $irrigation_duration = $_POST['irrigation_duration'];

        $stmt = $pdo->prepare("SELECT device_id FROM devices WHERE device_id = ? AND user_id = ?");
        $stmt->execute([$device_id, $user_id]);

        if ($stmt->fetch()) {
            // Actualizar información básica del dispositivo
            $stmt = $pdo->prepare("UPDATE devices SET name = ?, location= ? WHERE device_id = ?");
            if ($stmt->execute([$name, $location, $device_id])) {
                // Actualizar o insertar configuración
                $stmt = $pdo->prepare("INSERT INTO device_config (device_id, auto_irrigation, threshold, duration) 
                                    VALUES (?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE 
                                    auto_irrigation = VALUES(auto_irrigation), 
                                    threshold = VALUES(threshold), 
                                    duration = VALUES(duration)");
                if ($stmt->execute([$device_id, $auto_irrigation, $soil_threshold, $irrigation_duration])) {
                    $success = "Dispositivo actualizado correctamente";
                } else {
                    $error = "Error al actualizar la configuración del dispositivo";
                }
            } else {
                $error = "Error al actualizar el dispositivo";
            }
        } else {
            $error = "Dispositivo no encontrado o no tienes permisos";
        }
    }

    // Obtener estado en línea de los dispositivos
    $online_status = [];
    if (!empty($devices_bd)) {
        $device_ids = array_column($devices_bd, 'device_id');
        $placeholders = implode(',', array_fill(0, count($device_ids), '?'));
        
        $stmt = $pdo->prepare("
            SELECT device_id, 
                MAX(created_at) as last_seen,
                TIMESTAMPDIFF(MINUTE, MAX(created_at), NOW()) as minutes_ago,
                CASE WHEN TIMESTAMPDIFF(MINUTE, MAX(created_at), NOW()) <= 5 THEN 1 ELSE 0 END as is_online
            FROM sensor_data 
            WHERE device_id IN ($placeholders) 
            GROUP BY device_id
        ");
        $stmt->execute($device_ids);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $online_status[$row['device_id']] = $row;
        }
    }
?>

<body>
    
    <div class="container">
        <div class="row">
            <main class="px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mis Dispositivos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                            <i class="fas fa-plus me-1"></i>Añadir Dispositivo
                        </button>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <?php if (empty($devices_bd)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No tienes dispositivos registrados.
                                <a href="#" data-bs-toggle="modal" data-bs-target="#addDeviceModal">Añade tu primer dispositivo</a>.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($devices_bd as $device): 
                            // Valores reales de la BD con fallback seguro
                            $auto_irrigation = isset($device['auto_irrigation']) ? (bool)$device['auto_irrigation'] : false;
                            $soil_threshold = $device['threshold'] ?? 50;
                            $irrigation_duration = $device['duration'] ?? 10;
                            
                            // Estado en línea
                            $is_online = isset($online_status[$device['device_id']]) && 
                                        $online_status[$device['device_id']]['is_online'];
                            $last_seen = isset($online_status[$device['device_id']]) ? 
                                        $online_status[$device['device_id']]['last_seen'] : 'Nunca';
                        ?>
                            <div class="col-md-6 col-lg-4 mb-4" id="device-<?php echo $device['device_id']; ?>">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($device['name']); ?></h5>
                                        <span class="badge bg-<?php echo $is_online ? 'success' : 'danger'; ?>">
                                            <i class="fas fa-circle me-1"></i>
                                            <?php echo $is_online ? 'En línea' : 'Offline'; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                ID del dispositivo
                                                <span class="text-muted"><?php echo $device['device_id']; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Ubicación
                                                <span class="text-muted"><?php echo $device['location']; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Última conexión
                                                <span class="text-muted"><?php echo $last_seen; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Umbral de humedad
                                                <span class="text-muted"><?php echo $soil_threshold; ?>%</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Duración riego
                                                <span class="text-muted"><?php echo $irrigation_duration; ?>s</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Riego automático
                                                <span class="badge bg-<?php echo $auto_irrigation ? 'success' : 'danger'; ?>">
                                                    <?php echo $auto_irrigation ? 'Activado' : 'Desactivado'; ?>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between">
                                        <button class="btn btn-outline-primary" onclick="editDevice('<?php echo $device['device_id']; ?>')"><i class="fas fa-edit me-1"></i>Editar</button>
                                        <button class="btn btn-outline-danger" onclick="deleteDevice('<?php echo $device['device_id']; ?>')">Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Añadir Dispositivo -->
    <?php include 'includes/modal_addDevice.php'; ?>

    <!-- Modal Editar Dispositivo -->
    <?php include 'includes/modal_editDevice.php'; ?>

</body>