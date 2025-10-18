<?php
require 'database.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

// Instalar previamente: composer require endroid/qr-code
require 'vendor/autoload.php';

$cliente_id = $_GET['id'] ?? 0;

if ($cliente_id > 0) {
    // Obtener cliente
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();
    
    if ($cliente) {
        // Generar código único si no existe
        if (empty($cliente['codigo_qr'])) {
            $codigo_qr = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("UPDATE clientes SET codigo_qr = ? WHERE id = ?");
            $stmt->execute([$codigo_qr, $cliente_id]);
        } else {
            $codigo_qr = $cliente['codigo_qr'];
        }
        
        // Crear QR
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($codigo_qr)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->build();
        
        // Mostrar imagen
        header('Content-Type: '.$result->getMimeType());
        echo $result->getString();
        exit;
    }
}

// Si hay error, mostrar imagen vacía
header('Content-Type: image/png');
echo file_get_contents('images/empty.png');