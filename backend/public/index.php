<?php
// Общие заголовки для всех ответов
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Ранний ответ на preflight-запросы CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Простой роутинг на основе PATH_INFO или REQUEST_URI
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --------------------------------------------------------------------
// Healthcheck / Ping (для Swarm healthcheck и балансировщиков)
// --------------------------------------------------------------------
if ($path === '/health' || $path === '/ping') {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'service' => 'php-backend']);
    exit;
}

// --------------------------------------------------------------------
// API: /api/hello
// --------------------------------------------------------------------
if ($path === '/api/hello') {
    echo json_encode([
        'message' => 'Hello from PHP!',
        'time'    => date('Y-m-d H:i:s')
    ]);
    exit;
}

// --------------------------------------------------------------------
// API: /api/info (информация о контейнере и PHP)
// --------------------------------------------------------------------
if ($path === '/api/info') {
    echo json_encode([
        'hostname'     => gethostname(),
        'php_version'  => phpversion(),
        'time'         => date('Y-m-d H:i:s')
    ]);
    exit;
}

// --------------------------------------------------------------------
// API: /api/counter (Redis)
// --------------------------------------------------------------------
if ($path === '/api/counter') {
    $redisHost = getenv('REDIS_HOST') ?: 'redis';
    $redisPort = getenv('REDIS_PORT') ?: 6379;
    try {
        $redis = new Redis();
        $redis->connect($redisHost, $redisPort);
        $counter = $redis->incr('api_counter');
        echo json_encode(['counter' => $counter, 'storage' => 'redis']);
    } catch (Exception $e) {
        http_response_code(503);
        echo json_encode(['error' => 'Redis unavailable', 'message' => $e->getMessage()]);
    }
    exit;
}

// --------------------------------------------------------------------
// API: /api/db-test (PostgreSQL)
// --------------------------------------------------------------------
if ($path === '/api/db-test') {
    $dbHost = getenv('DB_HOST') ?: 'postgres';
    $dbPort = getenv('DB_PORT') ?: 5432;
    $dbName = getenv('DB_NAME') ?: 'petdb';
    $dbUser = getenv('DB_USER') ?: 'petuser';
    
    // Приоритет: пароль из файла (если задана переменная DB_PASSWORD_FILE)
    $dbPass = null;
    $passFile = getenv('DB_PASSWORD_FILE');
    if ($passFile && file_exists($passFile)) {
        $dbPass = trim(file_get_contents($passFile));
    }
    if (!$dbPass) {
        $dbPass = getenv('DB_PASSWORD') ?: 'petpass';
    }
    
    try {
        $pdo = new PDO("pgsql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_requests (
            id SERIAL PRIMARY KEY,
            created_at TIMESTAMP DEFAULT NOW()
        )");
        $pdo->exec("INSERT INTO test_requests DEFAULT VALUES");
        $stmt = $pdo->query("SELECT id, created_at FROM test_requests ORDER BY id DESC LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok' => true, 'records' => $rows]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// --------------------------------------------------------------------
// Если ни один маршрут не подошёл
// --------------------------------------------------------------------
http_response_code(404);
echo json_encode(['error' => 'Endpoint not found']);