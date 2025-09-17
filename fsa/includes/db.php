<?php
class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $pdo;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchValue($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    public function getPDO() {
        return $this->pdo;
    }
}

// Create global database instance
$db = new Database();
?>
