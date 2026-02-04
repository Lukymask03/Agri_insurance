<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';
require_once 'auth.php';
require_once 'controllers.php';

/* Provide a fallback getConnection() if db.php doesn't define it */
if (!function_exists('getConnection')) {
    function getConnection() {
        // reuse existing PDO connection if present in globals
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO) {
            return $GLOBALS['conn'];
        }

        // try common config locations: constants or global variables
        $host = defined('DB_HOST') ? constant('DB_HOST') : ($GLOBALS['DB_HOST'] ?? ($GLOBALS['db_host'] ?? null));
        $user = defined('DB_USER') ? constant('DB_USER') : ($GLOBALS['DB_USER'] ?? ($GLOBALS['db_user'] ?? null));
        $pass = defined('DB_PASS') ? constant('DB_PASS') : ($GLOBALS['DB_PASS'] ?? ($GLOBALS['db_pass'] ?? null));
        $name = defined('DB_NAME') ? constant('DB_NAME') : ($GLOBALS['DB_NAME'] ?? ($GLOBALS['db_name'] ?? null));
        $charset = defined('DB_CHARSET') ? constant('DB_CHARSET') : ($GLOBALS['DB_CHARSET'] ?? ($GLOBALS['db_charset'] ?? 'utf8mb4'));

        if ($host && $user !== null && $name) {
            try {
                $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                return $pdo;
            } catch (PDOException $e) {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            }
        }

        throw new Exception('Database connection details not set. Define DB_HOST/DB_USER/DB_PASS/DB_NAME or provide getConnection in db.php');
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($endpoint) {
        case 'auth':
            handleAuthAPI($action, $method, $input);
            break;

        case 'farmers':
            handleFarmersAPI($action, $method, $input);
            break;

        case 'dashboard':
            handleDashboardAPI($action, $method);
            break;

        default:
            sendResponse(false, 'Invalid endpoint', null, 404);
    }
} catch (Exception $e) {
    sendResponse(false, 'API Error: ' . $e->getMessage(), null, 500);
}



/* ============================================================
   AUTHENTICATION ENDPOINTS
   ============================================================ */
function handleAuthAPI($action, $method, $input) {
    switch ($action) {

        /* ---------------------- LOGIN ---------------------- */
        case 'login':
            if ($method !== 'POST') 
                sendResponse(false, 'Method not allowed', null, 405);

            if (empty($input['username']) || empty($input['password'])) 
                sendResponse(false, 'Username and password required', null, 400);

            $result = login($input['username'], $input['password']);

            if (!$result['success']) {
                sendResponse(false, $result['message'], null);
            }

            // 🔥 FIXED: return standardized structure for frontend
            sendResponse(
                true,
                $result['message'],
                [
                    "user_id"  => $result["user"]["user_id"],
                    "username" => $result["user"]["username"],
                    "role"     => $result["user"]["role"]
                ]
            );
            break;


        /* ---------------------- LOGOUT ---------------------- */
        case 'logout':
            $result = logout();
            sendResponse($result['success'], $result['message']);
            break;

        default:
            sendResponse(false, 'Invalid action', null, 400);
    }
}




/* ============================================================
   FARMERS ENDPOINTS
   ============================================================ */
function handleFarmersAPI($action, $method, $input) {
    $conn = getConnection();

    switch ($action) {
        case 'list':
            $farmers = getFarmers($conn, $_GET);
            sendResponse(true, 'Farmers retrieved', $farmers);
            break;

        case 'get':
            if (empty($_GET['id'])) 
                sendResponse(false, 'Farmer ID required', null, 400);

            $farmer = getFarmerById($conn, $_GET['id']);

            if (!$farmer) 
                sendResponse(false, 'Farmer not found', null, 404);

            sendResponse(true, 'Farmer retrieved', $farmer);
            break;

        default:
            sendResponse(false, 'Invalid action', null, 400);
    }
}




/* ============================================================
   DASHBOARD ENDPOINTS
   ============================================================ */
function handleDashboardAPI($action, $method) {
    if ($method !== 'GET') 
        sendResponse(false, 'Only GET method allowed', null, 405);

    $conn = getConnection();
    $stats = getDashboardStats($conn);

    sendResponse(true, 'Dashboard data retrieved', $stats);
}

/* ============================================================
   HELPER FUNCTIONS
   ============================================================ */
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    // Set the HTTP response code (200, 400, 404, 500, etc.)
    http_response_code($statusCode);

    // Create the response array
    $response = [
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ];

    // Output the JSON
    echo json_encode($response);
    
    // Stop the script to prevent any extra output
    exit;
}

?>
