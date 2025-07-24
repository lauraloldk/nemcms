<?php
require_once 'userIPs.php';

$userManager = new UserIPManager();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Debug - CMS System</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 2rem; 
            background: #f5f5f5; 
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ip-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            font-family: monospace;
        }
        .debug-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .debug-table th,
        .debug-table td {
            padding: 0.75rem;
            text-align: left;
            border: 1px solid #ddd;
        }
        .debug-table th {
            background: #f8f9fa;
        }
        .status {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-yes { background: #d4edda; color: #155724; }
        .status-no { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 IP Debug Information</h1>
        
        <h2>Din aktuelle IP information:</h2>
        <div class="ip-info">
            <strong>Detekteret IP:</strong> <?= htmlspecialchars($userManager->getCurrentIP()) ?>
        </div>
        
        <h2>Alle tilgængelige IP kilder:</h2>
        <table class="debug-table">
            <tr>
                <th>Kilde</th>
                <th>Værdi</th>
            </tr>
            <tr>
                <td>REMOTE_ADDR</td>
                <td><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Ikke sat') ?></td>
            </tr>
            <tr>
                <td>HTTP_CLIENT_IP</td>
                <td><?= htmlspecialchars($_SERVER['HTTP_CLIENT_IP'] ?? 'Ikke sat') ?></td>
            </tr>
            <tr>
                <td>HTTP_X_FORWARDED_FOR</td>
                <td><?= htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'Ikke sat') ?></td>
            </tr>
            <tr>
                <td>HTTP_X_REAL_IP</td>
                <td><?= htmlspecialchars($_SERVER['HTTP_X_REAL_IP'] ?? 'Ikke sat') ?></td>
            </tr>
            <tr>
                <td>HTTP_CF_CONNECTING_IP</td>
                <td><?= htmlspecialchars($_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'Ikke sat') ?></td>
            </tr>
        </table>
        
        <h2>Admin status:</h2>
        <table class="debug-table">
            <tr>
                <th>Test</th>
                <th>Resultat</th>
            </tr>
            <tr>
                <td>Er du admin?</td>
                <td>
                    <span class="status <?= $userManager->isAdmin() ? 'status-yes' : 'status-no' ?>">
                        <?= $userManager->isAdmin() ? 'JA' : 'NEJ' ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td>Er du bruger?</td>
                <td>
                    <span class="status <?= $userManager->isUser() ? 'status-yes' : 'status-no' ?>">
                        <?= $userManager->isUser() ? 'JA' : 'NEJ' ?>
                    </span>
                </td>
            </tr>
        </table>
        
        <h2>Nuværende admin liste:</h2>
        <?php $userData = $userManager->getData(); ?>
        <div class="ip-info">
            <?php if (empty($userData['admins'])): ?>
                <em>Ingen admins registreret</em>
            <?php else: ?>
                <?php foreach ($userData['admins'] as $adminIP): ?>
                    <div><?= htmlspecialchars($adminIP) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <h2>Handlinger:</h2>
        <form method="post" style="margin: 1rem 0;">
            <button type="submit" name="action" value="add_current_admin" style="padding: 0.75rem 1.5rem; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Tilføj nuværende IP som admin
            </button>
        </form>
        
        <form method="post" style="margin: 1rem 0;">
            <input type="text" name="ip_address" placeholder="Indtast IP adresse" style="padding: 0.75rem; margin-right: 0.5rem;">
            <button type="submit" name="action" value="add_custom_admin" style="padding: 0.75rem 1.5rem; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Tilføj custom IP som admin
            </button>
        </form>
        
        <div style="margin-top: 2rem;">
            <a href="index.php" style="margin-right: 1rem;">🏠 Forside</a>
            <a href="adminpanel.php" style="margin-right: 1rem;">🎛️ Admin Panel</a>
            <a href="showpages.php">📄 Alle Sider</a>
        </div>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'add_current_admin') {
                $currentIP = $userManager->getCurrentIP();
                if ($userManager->addAdmin($currentIP)) {
                    echo "<div style='background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>✅ IP $currentIP tilføjet som admin!</div>";
                } else {
                    echo "<div style='background: #fff3cd; color: #856404; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>⚠️ IP $currentIP er allerede admin!</div>";
                }
                echo "<script>setTimeout(() => location.reload(), 1000);</script>";
            } elseif ($action === 'add_custom_admin' && !empty($_POST['ip_address'])) {
                $customIP = $_POST['ip_address'];
                if ($userManager->addAdmin($customIP)) {
                    echo "<div style='background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>✅ IP $customIP tilføjet som admin!</div>";
                } else {
                    echo "<div style='background: #fff3cd; color: #856404; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>⚠️ IP $customIP er allerede admin!</div>";
                }
                echo "<script>setTimeout(() => location.reload(), 1000);</script>";
            }
        }
        ?>
    </div>
</body>
</html>
