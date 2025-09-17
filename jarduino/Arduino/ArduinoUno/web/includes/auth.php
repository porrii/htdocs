<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function registerUser($name, $email, $password) {
    global $pdo;
    
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        return false; // Usuario ya existe
    }
    
    // Crear nuevo usuario
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $hashedPassword]);
    
    return $pdo->lastInsertId();
}

function loginUser($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $email;
        return true;
    }
    
    return false;
}

function addDevice($user_id, $device_id, $name, $description = '') {
    global $pdo;
    
    // Verificar si el dispositivo ya existe
    $stmt = $pdo->prepare("SELECT id FROM devices WHERE device_id = ?");
    $stmt->execute([$device_id]);
    
    if ($stmt->fetch()) {
        return false; // Dispositivo ya existe
    }
    
    // Añadir nuevo dispositivo
    $stmt = $pdo->prepare("INSERT INTO devices (user_id, device_id, name, description) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $device_id, $name, $description]);
}

function getUserDevices($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>