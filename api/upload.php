<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Файл зображення не було отримано. Перевірте форму.']);
    exit;
}

if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['image']['error'];
    $errMessage = match ($errCode) {
        UPLOAD_ERR_INI_SIZE => 'Файл занадто великий (перевищує upload_max_filesize в php.ini).',
        UPLOAD_ERR_FORM_SIZE => 'Розмір файлу перевищує ліміт форми.',
        UPLOAD_ERR_PARTIAL => 'Файл завантажено лише частково.',
        UPLOAD_ERR_NO_FILE => 'Файл не був завантажений.',
        UPLOAD_ERR_NO_TMP_DIR => 'На сервері відсутня тимчасова папка (temp directory).',
        UPLOAD_ERR_CANT_WRITE => 'Не вдалося записати файл на диск (помилка прав доступу).',
        UPLOAD_ERR_EXTENSION => 'Завантаження зупинено розширенням PHP.',
        default => 'Невідома помилка завантаження (код: ' . $errCode . ').'
    };
    http_response_code(400);
    echo json_encode(['error' => $errMessage]);
    exit;
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$mimeType = '';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} elseif (function_exists('mime_content_type')) {
    $mimeType = mime_content_type($file['tmp_name']);
} else {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mimeType = match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => 'application/octet-stream'
    };
}

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.']);
    exit;
}

$uploadsDir = __DIR__ . '/../uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
if (empty($extension)) {
    $extension = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'bin'
    };
}

$newFileName = uniqid('img_', true) . '.' . $extension;
$destination = $uploadsDir . '/' . $newFileName;

function compressAndResizeImage($sourcePath, $destPath, $mimeType, $maxWidth = 1600, $maxHeight = 1600, $quality = 80) {
    if (!function_exists('imagecreatefromjpeg')) {
        return move_uploaded_file($sourcePath, $destPath);
    }
    

    list($width, $height) = getimagesize($sourcePath);
    if (!$width || !$height) {
        return move_uploaded_file($sourcePath, $destPath);
    }
    
    $newWidth = $width;
    $newHeight = $height;
    
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = $width / $height;
        if ($newWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = round($newWidth / $ratio);
        }
        if ($newHeight > $maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = round($newHeight * $ratio);
        }
    }
    

    $srcImage = null;
    switch ($mimeType) {
        case 'image/jpeg':
            $srcImage = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $srcImage = @imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $srcImage = @imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $srcImage = @imagecreatefromwebp($sourcePath);
            break;
    }
    
    if (!$srcImage) {
        return move_uploaded_file($sourcePath, $destPath);
    }
    

    $dstImage = imagecreatetruecolor($newWidth, $newHeight);
    

    if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
        imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    

    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    

    $success = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $success = imagejpeg($dstImage, $destPath, $quality);
            break;
        case 'image/png':
            $pngQuality = round((100 - $quality) / 10);
            $success = imagepng($dstImage, $destPath, $pngQuality);
            break;
        case 'image/gif':
            $success = imagegif($dstImage, $destPath);
            break;
        case 'image/webp':
            $success = imagewebp($dstImage, $destPath, $quality);
            break;
    }
    

    imagedestroy($srcImage);
    imagedestroy($dstImage);
    
    if (!$success) {
        return move_uploaded_file($sourcePath, $destPath);
    }
    
    return true;
}

if (compressAndResizeImage($file['tmp_name'], $destination, $mimeType)) {
    $relativeUrl = 'uploads/' . $newFileName;
    echo json_encode(['success' => true, 'url' => $relativeUrl]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save uploaded file']);
}
