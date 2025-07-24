<?php
require_once 'PageManager.php';
require_once 'userIPs.php';

$pageManager = new PageManager();
$userManager = new UserIPManager();

// Get all pages
$pages = $pageManager->getAllPages();
$userData = $userManager->getData();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alle Sider - CMS System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .nav-links {
            text-align: center;
            margin-bottom: 2rem;
        }
        .nav-links a {
            display: inline-block;
            margin: 0 1rem;
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .nav-links a:hover {
            background: #2980b9;
        }
        .page-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .page-header {
            background: #34495e;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-content {
            padding: 2rem;
        }
        .page-meta {
            background: #f8f9fa;
            padding: 1rem 2rem;
            border-top: 1px solid #eee;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            font-size: 0.9rem;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-published { background: #d4edda; color: #155724; }
        .status-draft { background: #fff3cd; color: #856404; }
        .system-info {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        /* Responsive image sizing */
        .page-content img {
            max-width: 400px;
            width: 100%;
            height: auto;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        @media (max-width: 768px) {
            .page-content img {
                max-width: 300px;
            }
        }
        
        @media (max-width: 480px) {
            .page-content img {
                max-width: 250px;
            }
        }
        
        /* Image Lightbox Styles */
        .page-content img,
        .lightbox-image {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid transparent;
        }
        
        .page-content img:hover,
        .lightbox-image:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border-color: #3498db;
        }
        
        .image-lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .image-lightbox.active {
            display: flex;
        }
        
        .lightbox-content {
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }
        
        .lightbox-content img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .lightbox-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .lightbox-close:hover {
            color: #ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Alle Sider - CMS System</h1>
            <p>Oversigt over alle sider i systemet</p>
        </div>

        <div class="nav-links">
            <a href="index.php">🏠 Forside</a>
            <a href="adminpanel.php">🎛️ Admin Panel</a>
        </div>

        <div class="system-info">
            <h2>📊 System Information</h2>
            <div class="info-grid">
                <div>
                    <h3>📈 Statistik</h3>
                    <ul>
                        <li>Antal sider: <?= count($pages) ?></li>
                        <li>Udgivne sider: <?= count(array_filter($pages, fn($p) => $p['status'] === 'published')) ?></li>
                        <li>Kladder: <?= count(array_filter($pages, fn($p) => $p['status'] === 'draft')) ?></li>
                    </ul>
                </div>
                <div>
                    <h3>👥 Brugere</h3>
                    <ul>
                        <li>Administratorer: <?= count($userData['admins']) ?></li>
                        <li>Brugere: <?= count($userData['users']) ?></li>
                        <li>Din IP: <?= htmlspecialchars($userManager->getCurrentIP()) ?></li>
                    </ul>
                </div>
                <div>
                    <h3>🛠️ System</h3>
                    <ul>
                        <li>PHP Version: <?= phpversion() ?></li>
                        <li>Server: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Ukendt' ?></li>
                        <li>Tidzone: <?= date_default_timezone_get() ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (empty($pages)): ?>
        <div class="page-card">
            <div class="page-content">
                <h2>Ingen sider fundet</h2>
                <p>Der er endnu ikke oprettet nogen sider i systemet.</p>
                <p><a href="adminpanel.php">Gå til admin panelet for at oprette din første side</a></p>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($pages as $page): ?>
            <div class="page-card">
                <div class="page-header">
                    <div>
                        <h2><?= htmlspecialchars($page['title']) ?></h2>
                        <span class="status-badge status-<?= $page['status'] ?>">
                            <?= ucfirst($page['status']) ?>
                        </span>
                    </div>
                    <div>
                        <a href="index.php?page=<?= urlencode($page['slug']) ?>" 
                           style="color: #3498db; text-decoration: none;">
                            👁️ Se side
                        </a>
                    </div>
                </div>
                
                <div class="page-content">
                    <?php 
                    $content = $page['content'];
                    // Trim content if too long
                    if (strlen($content) > 500) {
                        $content = substr($content, 0, 500) . '...';
                    }
                    echo $content;
                    ?>
                </div>
                
                <div class="page-meta">
                    <div><strong>ID:</strong> <?= $page['id'] ?></div>
                    <div><strong>Slug:</strong> <?= htmlspecialchars($page['slug']) ?></div>
                    <div><strong>Oprettet:</strong> <?= date('d/m/Y H:i', strtotime($page['created_at'])) ?></div>
                    <div><strong>Opdateret:</strong> <?= date('d/m/Y H:i', strtotime($page['updated_at'])) ?></div>
                    <div><strong>I menu:</strong> <?= $page['settings']['show_in_menu'] ? 'Ja' : 'Nej' ?></div>
                    <div><strong>Menu orden:</strong> <?= $page['settings']['menu_order'] ?></div>
                    <?php if (!empty($page['meta']['description'])): ?>
                    <div><strong>Beskrivelse:</strong> <?= htmlspecialchars($page['meta']['description']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($page['meta']['keywords'])): ?>
                    <div><strong>Nøgleord:</strong> <?= htmlspecialchars($page['meta']['keywords']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Image Lightbox -->
    <div id="imageLightbox" class="image-lightbox">
        <div class="lightbox-content">
            <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
            <img id="lightboxImage" src="" alt="">
        </div>
    </div>

    <script>
        // Image lightbox functionality
        function viewImageLightbox(imageSrc, imageTitle) {
            const lightbox = document.getElementById('imageLightbox');
            const image = document.getElementById('lightboxImage');
            
            image.src = imageSrc;
            image.alt = imageTitle || '';
            lightbox.classList.add('active');
        }
        
        function closeLightbox() {
            document.getElementById('imageLightbox').classList.remove('active');
        }
        
        // Close lightbox when clicking outside image
        document.getElementById('imageLightbox').onclick = function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        };
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
        
        // Auto-detect images in content and make them clickable
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.page-content img');
            images.forEach(img => {
                img.classList.add('lightbox-image');
                img.onclick = function() {
                    viewImageLightbox(this.src, this.alt);
                };
            });
        });
    </script>
</body>
</html>