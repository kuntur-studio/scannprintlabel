<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

// Manejo de Preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$storage_dir = 'labels/';
if (!is_dir($storage_dir)) mkdir($storage_dir, 0777, true);

// Limpieza automática (Borra archivos de más de 1 hora)
foreach (glob($storage_dir . "*.png") as $file) {
    if (time() - filemtime($file) > 3600) unlink($file);
}

// MODO GET: Thermer viene a buscar el JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = basename($_GET['id']);
    $file_path = $storage_dir . $id . ".png";
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        echo json_encode(["0" => ["type" => 0, "content" => "Error: No existe el archivo"]]);
        exit;
    }

    $image_url = "https://splabel.kunturstudio.com.ar/" . $file_path;
    
    // Formato forzado de objeto para la App
    $response = [
        "0" => [
            "type" => 1,
            "path" => $image_url,
            "align" => 1
        ],
        "1" => [
            "type" => 0,
            "content" => " \n\n",
            "bold" => 0,
            "align" => 0
        ]
    ];
    echo json_encode($response);
    exit;
}

// MODO POST: La PWA envía la imagen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['image'])) {
        $img = $data['image'];
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data_decoded = base64_decode($img);
        
        $file_id = uniqid('label_');
        $file_name = $file_id . ".png";
        
        if (file_put_contents($storage_dir . $file_name, $data_decoded)) {
            echo json_encode(['status' => 'success', 'id' => $file_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Fallo al escribir en disco']);
        }
    }
    exit;
}