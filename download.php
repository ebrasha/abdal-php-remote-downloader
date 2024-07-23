<?php
/*
 **********************************************************************
 * -------------------------------------------------------------------
 * Project Name : Abdal PHP Remote Downloader
 * File Name    : download.php
 * Author       : Ebrahim Shafiei (EbraSha)
 * Email        : Prof.Shafiei@Gmail.com
 * Created On   : 2024-07-12 2:43 AM
 * Description  : [A brief description of what this file does]
 * -------------------------------------------------------------------
 *
 * "Coding is an engaging and beloved hobby for me. I passionately and insatiably pursue knowledge in cybersecurity and programming."
 * – Ebrahim Shafiei
 *
 **********************************************************************
 */

ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');

header('Content-Type: application/json');

function getFileSize($url) {
    $headers = get_headers($url, 1);
    if (isset($headers['Content-Length'])) {
        return $headers['Content-Length'];
    }
    return false;
}

function downloadChunk($url, $start, $chunkSize, $savePath) {
    $fp = fopen($savePath, 'c');
    if ($fp === false) {
        return ['error' => 'Could not open: ' . $savePath];
    }

    fseek($fp, $start);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RANGE, $start . '-' . ($start + $chunkSize - 1));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; CrOS x86_64 8172.45.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/250.0.2704.64 Safari/537.36'); // Add User Agent

    $data = curl_exec($ch);
    if ($data === false) {
        return ['error' => 'Download error: ' . curl_error($ch)];
    }

    fwrite($fp, $data);
    fclose($fp);
    curl_close($ch);

    return ['downloaded' => $start + strlen($data), 'file' => $savePath];
}

ob_start(); // Start output buffering

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];
    if (isset($_POST['fileUrl']) && filter_var($_POST['fileUrl'], FILTER_VALIDATE_URL)) {
        $fileUrl = $_POST['fileUrl'];
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $chunkSize = 5* 1024 * 1024; // 5MB chunks

        $fileName = basename(parse_url($fileUrl, PHP_URL_PATH));
        $savePath = __DIR__ . '/' . $fileName;

        if ($start == 0) {
            if (file_exists($savePath)) {
                unlink($savePath); // Remove existing file to start fresh
            }
            $totalSize = getFileSize($fileUrl);
            if ($totalSize === false) {
                $response['error'] = 'Could not get file size';
                echo json_encode($response);
                exit;
            }
            $response['totalSize'] = $totalSize;
        } else {
            $response['totalSize'] = isset($_POST['totalSize']) ? intval($_POST['totalSize']) : 0;
            $currentSize = file_exists($savePath) ? filesize($savePath) : 0;
            if ($currentSize != $start) {
                $start = $currentSize;
            }
        }

        $result = downloadChunk($fileUrl, $start, $chunkSize, $savePath);
        if (isset($result['error'])) {
            $response['error'] = $result['error'];
        } else {
            $response['downloaded'] = $result['downloaded'];
            $response['file'] = $result['file'];
            $response['continue'] = $result['downloaded'] < $response['totalSize'];
        }
    } else {
        $response['error'] = 'Invalid URL';
    }
} else {
    $response['error'] = 'Invalid request method';
}

ob_clean(); // Clear output buffer to avoid any extra output
echo json_encode($response);
?>
