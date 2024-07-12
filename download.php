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

function downloadChunk($url, $start, $chunkSize) {
    $fileName = basename(parse_url($url, PHP_URL_PATH));
    $savePath = __DIR__ . '/' . $fileName;

    $fp = fopen($savePath, 'a');
    if ($fp === false) {
        return ['error' => 'Could not open: ' . $savePath];
    }

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

    return ['downloaded' => $start + strlen($data), 'file' => $fileName];
}

ob_start(); // Start output buffering

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];
    if (isset($_POST['fileUrl']) && filter_var($_POST['fileUrl'], FILTER_VALIDATE_URL)) {
        $fileUrl = $_POST['fileUrl'];
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $chunkSize = 1024 * 1024; // 1MB chunks

        if ($start == 0) {
            $totalSize = getFileSize($fileUrl);
            if ($totalSize === false) {
                $response['error'] = 'Could not get file size';
                echo json_encode($response);
                exit;
            }
            $response['totalSize'] = $totalSize;
        } else {
            $response['totalSize'] = isset($_POST['totalSize']) ? intval($_POST['totalSize']) : 0;
        }

        $result = downloadChunk($fileUrl, $start, $chunkSize);
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
