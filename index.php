<?php
require_once 'PageManager.php';

$pageManager = new PageManager();

// Check if we're viewing a specific page
$requestedSlug = $_GET['page'] ?? null;
$viewMode = $requestedSlug ? 'single' : 'overview';

if ($viewMode === 'single') {
    // Show single page
    $page = $pageManager->getPageBySlug($requestedSlug);
    
    // If page not found, show 404
    if (!$page) {
        $page = [
            'id' => 0,
            'title' => '404 - Side ikke fundet',
            'content' => '<h1>Side ikke fundet</h1><p>Den anmodede side kunne ikke findes.</p><p><a href="index.php">← Tilbage til alle sider</a></p>',
            'status' => 'published'
        ];
    }
} else {
    // Show overview of all pages
    $allPages = $pageManager->getAllPages('published');
}

// Get menu pages
$menuPages = $pageManager->getMenuPages();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $viewMode === 'single' ? htmlspecialchars($page['title']) . ' - CMS System' : 'Alle Sider - CMS System' ?></title>
    
    <?php if ($viewMode === 'single' && isset($page['meta'])): ?>
    <meta name="description" content="<?= htmlspecialchars($page['meta']['description'] ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page['meta']['keywords'] ?? '') ?>">
    <?php else: ?>
    <meta name="description" content="Oversigt over alle sider i CMS systemet">
    <?php endif; ?>
    
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .nav-menu a:hover,
        .nav-menu a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .admin-link {
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .admin-link:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .content {
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .content h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 2.5rem;
        }
        
        .content h2 {
            color: #34495e;
            margin: 2rem 0 1rem 0;
            font-size: 2rem;
        }
        
        .content h3 {
            color: #34495e;
            margin: 1.5rem 0 1rem 0;
            font-size: 1.5rem;
        }
        
        .content p {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            line-height: 1.8;
        }
        
        .content img {
            max-width: 400px;
            width: 100%;
            height: auto;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        /* Responsive images - smaller on mobile */
        @media (max-width: 768px) {
            .content img {
                max-width: 300px;
            }
        }
        
        @media (max-width: 480px) {
            .content img {
                max-width: 250px;
            }
        }
        
        .content ul, .content ol {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .content li {
            margin-bottom: 0.5rem;
        }
        
        .content blockquote {
            border-left: 4px solid #667eea;
            padding-left: 1.5rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #666;
        }
        
        .content code {
            background: #f1f3f4;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .content pre {
            background: #f1f3f4;
            padding: 1rem;
            border-radius: 5px;
            overflow-x: auto;
            margin: 1rem 0;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }
        
        .footer p {
            margin: 0;
        }

        /* Page Cards for Overview */
        .page-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .page-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .page-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .page-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .page-card-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 0 0.5rem 0;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }
        
        .page-card-title:hover {
            text-decoration: underline;
        }
        
        .page-card-meta {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .page-card-content {
            padding: 1.5rem;
        }
        
        .page-card-excerpt {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .page-card-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .read-more-btn {
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .read-more-btn:hover {
            background: #5a6fd8;
        }
        
        .overview-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .overview-header h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .overview-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .page-meta {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Image Lightbox Styles */
        .content img,
        .content-image {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid transparent;
        }
        
        .content img:hover,
        .content-image:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border-color: #667eea;
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
        
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
            
            .container {
                padding: 1rem;
            }
            
            .page-cards {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .page-card-header {
                padding: 1rem;
            }
            
            .page-card-title {
                font-size: 1.3rem;
            }
            
            .overview-header h1 {
                font-size: 2rem;
            }
            
            .content {
                padding: 2rem 1.5rem;
            }
            
            .content h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">🌐 CMS System</a>
            
            <?php if (!empty($menuPages)): ?>
            <nav>
                <ul class="nav-menu">
                    <li>
                        <a href="index.php" class="<?= ($viewMode === 'overview') ? 'active' : '' ?>">
                            🏠 Alle Sider
                        </a>
                    </li>
                    <?php foreach ($menuPages as $menuPage): ?>
                    <li>
                        <a href="index.php?page=<?= urlencode($menuPage['slug']) ?>" 
                           class="<?= ($menuPage['slug'] === $requestedSlug) ? 'active' : '' ?>">
                            <?= htmlspecialchars($menuPage['title']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php else: ?>
            <nav>
                <ul class="nav-menu">
                    <li>
                        <a href="index.php" class="<?= ($viewMode === 'overview') ? 'active' : '' ?>">
                            🏠 Alle Sider
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <a href="adminpanel.php" class="admin-link">🎛️ Admin</a>
        </div>
    </header>

    <main class="container">
        <?php if ($viewMode === 'single'): ?>
            <!-- Single Page View -->
            <?php if (isset($page['meta']) && !empty($page['meta']['description'])): ?>
            <div class="page-meta">
                📄 <?= htmlspecialchars($page['meta']['description']) ?>
            </div>
            <?php endif; ?>
            
            <article class="content">
                <?php 
                // Use new modular content system if available, fallback to legacy content
                if (isset($page['content_elements']) && !empty($page['content_elements'])) {
                    echo $pageManager->renderContentElements($page['content_elements']);
                } else {
                    echo $page['content'];
                }
                ?>
            </article>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="index.php" class="read-more-btn">← Tilbage til alle sider</a>
            </div>
            
        <?php else: ?>
            <!-- Page Overview -->
            <div class="overview-header">
                <h1>Velkommen til CMS Systemet</h1>
                <p>Klik på en side titel for at læse den fulde artikel</p>
            </div>
            
            <?php if (empty($allPages)): ?>
                <div class="content">
                    <h2>Ingen sider fundet</h2>
                    <p>Der er endnu ikke oprettet nogen sider i systemet.</p>
                    <p><a href="adminpanel.php">Gå til admin panelet</a> for at oprette din første side.</p>
                </div>
            <?php else: ?>
                <div class="page-cards">
                    <?php foreach ($allPages as $pageItem): ?>
                        <div class="page-card">
                            <div class="page-card-header">
                                <a href="index.php?page=<?= urlencode($pageItem['slug']) ?>" class="page-card-title">
                                    <?= htmlspecialchars($pageItem['title']) ?>
                                </a>
                                <div class="page-card-meta">
                                    📅 <?= date('d/m/Y', strtotime($pageItem['created_at'])) ?>
                                    <?php if (isset($pageItem['updated_at']) && $pageItem['updated_at'] !== $pageItem['created_at']): ?>
                                        • Opdateret: <?= date('d/m/Y', strtotime($pageItem['updated_at'])) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="page-card-content">
                                <div class="page-card-excerpt">
                                    <?php 
                                    // Create excerpt from content
                                    $excerpt = '';
                                    if (isset($pageItem['content_elements']) && !empty($pageItem['content_elements'])) {
                                        // Extract text from content elements
                                        foreach ($pageItem['content_elements'] as $element) {
                                            if (in_array($element['type'], ['p', 'h1', 'h2', 'h3'])) {
                                                $excerpt .= $element['content'] . ' ';
                                            }
                                        }
                                    } else {
                                        $excerpt = strip_tags($pageItem['content']);
                                    }
                                    
                                    // Limit excerpt length
                                    $excerpt = trim($excerpt);
                                    if (strlen($excerpt) > 150) {
                                        $excerpt = substr($excerpt, 0, 150) . '...';
                                    }
                                    
                                    echo htmlspecialchars($excerpt ?: 'Ingen beskrivelse tilgængelig.');
                                    ?>
                                </div>
                            </div>
                            
                            <div class="page-card-footer">
                                <div class="page-status">
                                    <?php if (isset($pageItem['meta']['description'])): ?>
                                        📝 <?= htmlspecialchars(substr($pageItem['meta']['description'], 0, 50)) ?>
                                        <?= strlen($pageItem['meta']['description']) > 50 ? '...' : '' ?>
                                    <?php endif; ?>
                                </div>
                                <a href="index.php?page=<?= urlencode($pageItem['slug']) ?>" class="read-more-btn">
                                    Læs mere →
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> CMS System. Lavet med ❤️</p>
    </footer>

    <!-- Image Lightbox -->
    <div id="imageLightbox" class="image-lightbox">
        <div class="lightbox-content">
            <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
            <img id="lightboxImage" src="" alt="">
        </div>
    </div>

    <script>
        // Simple analytics tracking
        if (window.location.hostname !== 'localhost') {
            console.log('Page view: ' + window.location.pathname);
        }
        
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
            const images = document.querySelectorAll('.content img');
            images.forEach(img => {
                const originalOnclick = img.onclick;
                img.onclick = function(e) {
                    e.preventDefault();
                    viewImageLightbox(this.src, this.alt);
                };
                img.style.cursor = 'pointer';
            });
        });
        
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>