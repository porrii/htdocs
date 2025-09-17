<?php
require_once '../config/config.php';

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        require_once '../config/database.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM admins WHERE username = :username AND active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            redirect('index.php');
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    } else {
        $error = 'Por favor, completa todos los campos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - FSA Studio Admin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Space Grotesk', 'system-ui', 'sans-serif'],
                        'body': ['DM Sans', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: '#164e63',
                        secondary: '#f59e0b',
                        accent: '#f59e0b',
                        muted: '#475569',
                    }
                }
            }
        }
    </script>
</head>
<body class="font-body bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-primary mb-2">FSA Studio</h1>
                <p class="text-muted">Panel de Administración</p>
            </div>
            
            <?php if($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-muted mb-2">Usuario</label>
                    <input type="text" name="username" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-muted mb-2">Contraseña</label>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg hover:bg-primary/90 transition-colors font-medium">
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="../index.html" class="text-secondary hover:text-secondary/80 text-sm">
                    ← Volver al sitio web
                </a>
            </div>
        </div>
        
        <div class="mt-6 text-center text-sm text-muted">
            <p>Usuario por defecto: <strong>admin</strong></p>
            <p>Contraseña por defecto: <strong>admin123</strong></p>
        </div>
    </div>
</body>
</html>
