<?php
// test_nolog.php - Con verificación de sesión
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

header('Content-Type: application/json');

// Verificar sesión
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

// Si no hay sesión válida, devolver error
if ($role != 1 || $isAdmin != 1) {
    echo json_encode([
        'success' => false, 
        'message' => 'No autenticado',
        'session' => [
            'usr_admin' => $isAdmin,
            'role' => $role,
            'session_id' => session_id()
        ]
    ]);
    exit;
}

$response = [
    'success' => true,
    'method' => $_SERVER['REQUEST_METHOD'],
    'session_id' => session_id(),
    'post' => $_POST,
    'files' => []
];

if (!empty($_FILES)) {
    foreach ($_FILES as $key => $file) {
        $response['files'][$key] = [
            'name' => $file['name'],
            'size' => $file['size'],
            'error' => $file['error']
        ];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/catalogo/images/test/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $destFile = $uploadDir . $file['name'];
            if (move_uploaded_file($file['tmp_name'], $destFile)) {
                $response['files'][$key]['saved'] = true;
                $response['files'][$key]['path'] = $destFile;
            }
        }
    }
}

echo json_encode($response);
?>