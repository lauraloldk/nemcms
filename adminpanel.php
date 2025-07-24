<?php
require_once 'userIPs.php';
require_once 'PageManager.php';
require_once 'ImageManager.php';

// Require admin access
$userManager->requireAdmin();

$pageManager = new PageManager();
$imageManager = new ImageManager();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_page':
            // Handle content elements
            $contentElements = [];
            if (isset($_POST['content_elements']) && is_array($_POST['content_elements'])) {
                foreach ($_POST['content_elements'] as $element) {
                    if ($pageManager->validateContentElement($element)) {
                        $contentElements[] = $element;
                    }
                }
            }
            
            $pageData = [
                'title' => $_POST['title'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'content' => $_POST['content'] ?? '', // Legacy support
                'content_elements' => $contentElements,
                'status' => $_POST['status'] ?? 'draft',
                'meta' => [
                    'description' => $_POST['meta_description'] ?? '',
                    'keywords' => $_POST['meta_keywords'] ?? ''
                ],
                'settings' => [
                    'show_in_menu' => isset($_POST['show_in_menu']),
                    'menu_order' => (int)($_POST['menu_order'] ?? 999),
                    'template' => $_POST['template'] ?? 'default'
                ]
            ];
            
            $result = $pageManager->createPage($pageData);
            echo json_encode(['success' => true, 'page' => $result]);
            exit;
            
        case 'update_page':
            $id = (int)($_POST['id'] ?? 0);
            
            // Handle content elements
            $contentElements = [];
            if (isset($_POST['content_elements']) && is_array($_POST['content_elements'])) {
                foreach ($_POST['content_elements'] as $element) {
                    if ($pageManager->validateContentElement($element)) {
                        $contentElements[] = $element;
                    }
                }
            }
            
            $pageData = [
                'title' => $_POST['title'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'content' => $_POST['content'] ?? '', // Legacy support
                'content_elements' => $contentElements,
                'status' => $_POST['status'] ?? 'draft',
                'meta' => [
                    'description' => $_POST['meta_description'] ?? '',
                    'keywords' => $_POST['meta_keywords'] ?? ''
                ],
                'settings' => [
                    'show_in_menu' => isset($_POST['show_in_menu']),
                    'menu_order' => (int)($_POST['menu_order'] ?? 999),
                    'template' => $_POST['template'] ?? 'default'
                ]
            ];
            
            $result = $pageManager->updatePage($id, $pageData);
            echo json_encode(['success' => $result !== null, 'page' => $result]);
            exit;
            
        case 'delete_page':
            $id = (int)($_POST['id'] ?? 0);
            $result = $pageManager->deletePage($id);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'get_page':
            $id = (int)($_POST['id'] ?? 0);
            $page = $pageManager->getPageById($id);
            echo json_encode(['success' => $page !== null, 'page' => $page]);
            exit;
            
        case 'upload_image':
            if (!isset($_FILES['image'])) {
                echo json_encode(['success' => false, 'error' => 'Ingen fil valgt']);
                exit;
            }
            
            $title = $_POST['title'] ?? '';
            $alt = $_POST['alt'] ?? '';
            
            $result = $imageManager->uploadImage($_FILES['image'], $title, $alt);
            echo json_encode($result);
            exit;
            
        case 'delete_image':
            $id = (int)($_POST['id'] ?? 0);
            $result = $imageManager->deleteImage($id);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'update_image':
            $id = (int)($_POST['id'] ?? 0);
            $updateData = [
                'title' => $_POST['title'] ?? '',
                'alt' => $_POST['alt'] ?? ''
            ];
            
            $result = $imageManager->updateImage($id, $updateData);
            echo json_encode(['success' => $result !== null, 'image' => $result]);
            exit;
            
        case 'get_images':
            $images = $imageManager->getAllImages();
            echo json_encode(['success' => true, 'images' => $images]);
            exit;
    }
}

// Get all pages for display
$pages = $pageManager->getAllPages();
$userData = $userManager->getData();
$images = $imageManager->getAllImages();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CMS System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 1.5rem; }
        .user-info { 
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .module {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .module-header {
            background: #34495e;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .module-content { padding: 2rem; }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        .pages-table {
            width: 100%;
            border-collapse: collapse;
        }
        .pages-table th,
        .pages-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .pages-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-published { background: #d4edda; color: #155724; }
        .status-draft { background: #fff3cd; color: #856404; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            width: 90%;
            max-width: 800px;
            border-radius: 8px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }
        .close {
            font-size: 2rem;
            cursor: pointer;
            color: #aaa;
        }
        .close:hover { color: #000; }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .row {
            display: flex;
            gap: 1rem;
        }
        .col {
            flex: 1;
        }
        
        /* Content Elements Editor Styles */
        .content-elements-editor {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            background: #f8f9fa;
        }
        
        .element-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .element-header {
            background: #e9ecef;
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .element-header:hover {
            background: #dee2e6;
        }
        
        .element-content {
            padding: 1rem;
            display: none;
        }
        
        .element-content.active {
            display: block;
        }
        
        .element-controls {
            display: flex;
            gap: 0.5rem;
        }
        
        .element-controls button {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .btn-move-up { background: #6c757d; color: white; }
        .btn-move-down { background: #6c757d; color: white; }
        .btn-delete-element { background: #dc3545; color: white; }
        
        .add-element-section {
            text-align: center;
            padding: 1rem;
            border: 2px dashed #ddd;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .element-form {
            display: grid;
            gap: 1rem;
        }
        
        .element-form-row {
            display: flex;
            gap: 1rem;
        }
        
        .element-form-row > div {
            flex: 1;
        }
        
        /* Image Gallery Styles */
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .image-item {
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .image-item:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .image-item.selected {
            border-color: #27ae60;
            border-width: 3px;
        }
        
        .image-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }
        
        .image-info {
            padding: 0.75rem;
        }
        
        .image-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        
        .image-details {
            font-size: 0.8rem;
            color: #666;
        }
        
        .image-controls {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .image-controls button {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .btn-edit-image { background: #3498db; color: white; }
        .btn-delete-image { background: #e74c3c; color: white; }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
            transition: border-color 0.3s;
        }
        
        .upload-area:hover {
            border-color: #3498db;
        }
        
        .upload-area.dragover {
            border-color: #27ae60;
            background: rgba(39, 174, 96, 0.1);
        }
        
        .image-selector-modal .modal-content {
            max-width: 1000px;
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
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>🎛️ CMS Admin Panel</h1>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="index.php" style="
                background: rgba(255,255,255,0.1);
                color: white;
                text-decoration: none;
                padding: 0.5rem 1rem;
                border-radius: 5px;
                font-size: 0.9rem;
                transition: background-color 0.3s;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            " onmouseover="this.style.background='rgba(255,255,255,0.2)'" 
               onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                🏠 Tilbage til Index
            </a>
            <div class="user-info">
                👤 Admin IP: <?= htmlspecialchars($userManager->getCurrentIP()) ?>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Pages Module -->
        <div class="module">
            <div class="module-header">
                <h2>📄 Sider</h2>
                <button class="btn btn-success" onclick="openPageModal()">➕ Ny Side</button>
            </div>
            <div class="module-content">
                <table class="pages-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titel</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Sidst opdateret</th>
                            <th>Handlinger</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                        <tr>
                            <td><?= $page['id'] ?></td>
                            <td><?= htmlspecialchars($page['title']) ?></td>
                            <td><?= htmlspecialchars($page['slug']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $page['status'] ?>">
                                    <?= ucfirst($page['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($page['updated_at'])) ?></td>
                            <td>
                                <button class="btn btn-primary" onclick="editPage(<?= $page['id'] ?>)">✏️ Rediger</button>
                                <button class="btn btn-danger" onclick="deletePage(<?= $page['id'] ?>)">🗑️ Slet</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Image Gallery Module -->
        <div class="module">
            <div class="module-header">
                <h2>🖼️ Billedgalleri</h2>
                <button class="btn btn-success" onclick="openImageUploadModal()">📤 Upload Billede</button>
            </div>
            <div class="module-content">
                <div class="image-gallery" id="imageGallery">
                    <?php if (empty($images)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #666;">
                            <p>Ingen billeder uploaded endnu.</p>
                            <p>Klik på "Upload Billede" for at komme i gang.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($images as $image): ?>
                        <div class="image-item" data-image-id="<?= $image['id'] ?>">
                            <img src="<?= htmlspecialchars($imageManager->getImageUrl($image['filename'])) ?>" 
                                 alt="<?= htmlspecialchars($image['alt']) ?>"
                                 onclick="viewImageLightbox('<?= htmlspecialchars($imageManager->getImageUrl($image['filename'])) ?>', '<?= htmlspecialchars($image['title']) ?>')">
                            <div class="image-info">
                                <div class="image-title"><?= htmlspecialchars($image['title']) ?></div>
                                <div class="image-details">
                                    <?= $image['width'] ?>×<?= $image['height'] ?> • 
                                    <?= $imageManager->formatFileSize($image['file_size']) ?>
                                </div>
                                <div class="image-controls">
                                    <button class="btn-edit-image" onclick="editImageModal(<?= $image['id'] ?>)">✏️ Rediger</button>
                                    <button class="btn-delete-image" onclick="deleteImage(<?= $image['id'] ?>)">🗑️ Slet</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Management Module -->
        <div class="module">
            <div class="module-header">
                <h2>👥 Brugerstyring</h2>
            </div>
            <div class="module-content">
                <div class="row">
                    <div class="col">
                        <h3>Administratorer (<?= count($userData['admins']) ?>)</h3>
                        <ul>
                            <?php foreach ($userData['admins'] as $adminIP): ?>
                            <li><?= htmlspecialchars($adminIP) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col">
                        <h3>Brugere (<?= count($userData['users']) ?>)</h3>
                        <ul>
                            <?php foreach ($userData['users'] as $userIP): ?>
                            <li><?= htmlspecialchars($userIP) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Modal -->
    <div id="pageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Ny Side</h2>
                <span class="close" onclick="closePageModal()">&times;</span>
            </div>
            <form id="pageForm">
                <input type="hidden" id="pageId" name="id">
                <input type="hidden" id="formAction" name="action" value="create_page">
                
                <div class="form-group">
                    <label for="title">Titel</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="slug">Slug (URL)</label>
                    <input type="text" id="slug" name="slug" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Indhold Elementer</label>
                    <div class="content-elements-editor" id="contentElementsEditor">
                        <div class="add-element-section">
                            <button type="button" class="btn btn-primary" onclick="addNewElement()">
                                ➕ Tilføj Nyt Element
                            </button>
                        </div>
                        <div id="elementsContainer">
                            <!-- Elements will be added here dynamically -->
                        </div>
                    </div>
                </div>
                
                <!-- Legacy content field (hidden, for backward compatibility) -->
                <input type="hidden" id="content" name="content" value="">
                
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="draft">Kladde</option>
                                <option value="published">Udgivet</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="menu_order">Menu rækkefølge</label>
                            <input type="number" id="menu_order" name="menu_order" class="form-control" value="999">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="show_in_menu" name="show_in_menu" checked>
                        <label for="show_in_menu">Vis i menu</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="meta_description">Meta beskrivelse</label>
                    <textarea id="meta_description" name="meta_description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="meta_keywords">Meta nøgleord</label>
                    <input type="text" id="meta_keywords" name="meta_keywords" class="form-control">
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="btn" onclick="closePageModal()">Annuller</button>
                    <button type="submit" class="btn btn-success">Gem</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Upload Modal -->
    <div id="imageUploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Upload Billede</h2>
                <span class="close" onclick="closeImageUploadModal()">&times;</span>
            </div>
            <form id="imageUploadForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_image">
                
                <div class="upload-area" id="uploadArea">
                    <p><strong>📤 Træk billeder hertil eller klik for at vælge</strong></p>
                    <p style="color: #666; margin-top: 0.5rem;">Tilladte formater: JPG, PNG, GIF, WebP (maks 5MB)</p>
                    <input type="file" id="imageFile" name="image" accept="image/*" style="display: none;">
                </div>
                
                <div class="form-group">
                    <label for="imageTitle">Titel</label>
                    <input type="text" id="imageTitle" name="title" class="form-control" placeholder="Billede titel">
                </div>
                
                <div class="form-group">
                    <label for="imageAlt">Alt tekst (for tilgængelighed)</label>
                    <input type="text" id="imageAlt" name="alt" class="form-control" placeholder="Beskrivelse af billedet">
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="btn" onclick="closeImageUploadModal()">Annuller</button>
                    <button type="submit" class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Edit Modal -->
    <div id="imageEditModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rediger Billede</h2>
                <span class="close" onclick="closeImageEditModal()">&times;</span>
            </div>
            <form id="imageEditForm">
                <input type="hidden" id="editImageId" name="id">
                <input type="hidden" name="action" value="update_image">
                
                <div class="form-group">
                    <label for="editImageTitle">Titel</label>
                    <input type="text" id="editImageTitle" name="title" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="editImageAlt">Alt tekst</label>
                    <input type="text" id="editImageAlt" name="alt" class="form-control">
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="btn" onclick="closeImageEditModal()">Annuller</button>
                    <button type="submit" class="btn btn-success">Gem</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Selector Modal (for choosing images in content editor) -->
    <div id="imageSelectorModal" class="modal image-selector-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Vælg Billede</h2>
                <span class="close" onclick="closeImageSelectorModal()">&times;</span>
            </div>
            <div class="image-gallery" id="imageSelectorGallery">
                <!-- Images will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Image Lightbox -->
    <div id="imageLightbox" class="image-lightbox">
        <div class="lightbox-content">
            <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
            <img id="lightboxImage" src="" alt="">
        </div>
    </div>

    <script>
        // Global variables for content elements
        let contentElements = [];
        let elementIdCounter = 1;
        
        // Available element types
        const elementTypes = <?= json_encode($pageManager->getAvailableElementTypes()) ?>;
        
        // Global variables for image gallery
        let selectedImageForElement = null;
        let currentImageElementId = null;
        
        // Image Gallery Functions
        function openImageUploadModal() {
            document.getElementById('imageUploadModal').style.display = 'block';
        }
        
        function closeImageUploadModal() {
            document.getElementById('imageUploadModal').style.display = 'none';
            document.getElementById('imageUploadForm').reset();
        }
        
        function openImageEditModal() {
            document.getElementById('imageEditModal').style.display = 'block';
        }
        
        function closeImageEditModal() {
            document.getElementById('imageEditModal').style.display = 'none';
        }
        
        function openImageSelectorModal(elementId) {
            currentImageElementId = elementId;
            loadImagesForSelector();
            document.getElementById('imageSelectorModal').style.display = 'block';
        }
        
        function closeImageSelectorModal() {
            document.getElementById('imageSelectorModal').style.display = 'none';
            selectedImageForElement = null;
            currentImageElementId = null;
        }
        
        function viewImageLightbox(imageSrc, imageTitle) {
            const lightbox = document.getElementById('imageLightbox');
            const image = document.getElementById('lightboxImage');
            
            image.src = imageSrc;
            image.alt = imageTitle;
            lightbox.classList.add('active');
        }
        
        function closeLightbox() {
            document.getElementById('imageLightbox').classList.remove('active');
        }
        
        function editImageModal(imageId) {
            // Load image data for editing
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_images'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const image = data.images.find(img => img.id == imageId);
                    if (image) {
                        document.getElementById('editImageId').value = image.id;
                        document.getElementById('editImageTitle').value = image.title;
                        document.getElementById('editImageAlt').value = image.alt;
                        openImageEditModal();
                    }
                }
            });
        }
        
        function deleteImage(imageId) {
            if (confirm('Er du sikker på at du vil slette dette billede?')) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=delete_image&id=' + imageId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Fejl ved sletning af billede');
                    }
                });
            }
        }
        
        function loadImagesForSelector() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_images'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const gallery = document.getElementById('imageSelectorGallery');
                    gallery.innerHTML = '';
                    
                    if (data.images.length === 0) {
                        gallery.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 2rem;"><p>Ingen billeder tilgængelige. Upload nogle billeder først.</p></div>';
                        return;
                    }
                    
                    data.images.forEach(image => {
                        const imageItem = document.createElement('div');
                        imageItem.className = 'image-item';
                        imageItem.onclick = () => selectImageForElement(image);
                        imageItem.innerHTML = `
                            <img src="${image.filename.startsWith('images/') ? image.filename : 'images/' + image.filename}" alt="${image.alt}">
                            <div class="image-info">
                                <div class="image-title">${image.title}</div>
                                <div class="image-details">${image.width}×${image.height}</div>
                            </div>
                        `;
                        gallery.appendChild(imageItem);
                    });
                }
            });
        }
        
        function selectImageForElement(image) {
            selectedImageForElement = image;
            
            // Clear previous selections
            document.querySelectorAll('#imageSelectorGallery .image-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Mark current selection
            event.currentTarget.classList.add('selected');
            
            // Update the element
            if (currentImageElementId) {
                const imageSrc = image.filename.startsWith('images/') ? image.filename : 'images/' + image.filename;
                updateElement(currentImageElementId, 'content', imageSrc);
                updateElement(currentImageElementId, 'attributes.alt', image.alt);
                renderElements();
            }
            
            // Close modal
            setTimeout(() => {
                closeImageSelectorModal();
            }, 500);
        }
        
        // Drag and drop functionality for image upload
        function setupImageUpload() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('imageFile');
            
            uploadArea.onclick = () => fileInput.click();
            
            uploadArea.ondragover = (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            };
            
            uploadArea.ondragleave = () => {
                uploadArea.classList.remove('dragover');
            };
            
            uploadArea.ondrop = (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    // Auto-fill title if empty
                    const titleInput = document.getElementById('imageTitle');
                    if (!titleInput.value) {
                        const fileName = files[0].name.split('.')[0];
                        titleInput.value = fileName.replace(/[_-]/g, ' ');
                    }
                }
            };
        }
        
        // Form submission handlers
        document.getElementById('imageUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeImageUploadModal();
                    location.reload();
                } else {
                    alert('Fejl ved upload: ' + (data.error || 'Ukendt fejl'));
                }
            });
        });
        
        document.getElementById('imageEditForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeImageEditModal();
                    location.reload();
                } else {
                    alert('Fejl ved opdatering af billede');
                }
            });
        });
        
        // Close lightbox when clicking outside image
        document.getElementById('imageLightbox').onclick = function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        };
        
        // Keyboard navigation for lightbox
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
                closeImageSelectorModal();
                closeImageUploadModal();
                closeImageEditModal();
            }
        });
        
        // Add new content element
        function addNewElement() {
            const elementId = 'element_' + elementIdCounter++;
            const element = {
                id: elementId,
                type: 'p',
                content: '',
                attributes: {
                    class: '',
                    id: ''
                }
            };
            
            contentElements.push(element);
            renderElements();
        }
        
        // Remove element
        function removeElement(elementId) {
            contentElements = contentElements.filter(el => el.id !== elementId);
            renderElements();
        }
        
        // Move element up
        function moveElementUp(elementId) {
            const index = contentElements.findIndex(el => el.id === elementId);
            if (index > 0) {
                [contentElements[index], contentElements[index - 1]] = [contentElements[index - 1], contentElements[index]];
                renderElements();
            }
        }
        
        // Move element down
        function moveElementDown(elementId) {
            const index = contentElements.findIndex(el => el.id === elementId);
            if (index < contentElements.length - 1) {
                [contentElements[index], contentElements[index + 1]] = [contentElements[index + 1], contentElements[index]];
                renderElements();
            }
        }
        
        // Update element data
        function updateElement(elementId, field, value) {
            const element = contentElements.find(el => el.id === elementId);
            if (element) {
                if (field.startsWith('attributes.')) {
                    const attrName = field.split('.')[1];
                    element.attributes[attrName] = value;
                } else {
                    element[field] = value;
                }
            }
        }
        
        // Toggle element editor
        function toggleElementEditor(elementId) {
            const content = document.getElementById('elementContent_' + elementId);
            content.classList.toggle('active');
        }
        
        // Render all elements
        function renderElements() {
            const container = document.getElementById('elementsContainer');
            container.innerHTML = '';
            
            contentElements.forEach((element, index) => {
                const elementHtml = createElementHtml(element, index);
                container.insertAdjacentHTML('beforeend', elementHtml);
            });
        }
        
        // Create HTML for a single element
        function createElementHtml(element, index) {
            const typeOptions = Object.entries(elementTypes)
                .map(([value, label]) => `<option value="${value}" ${element.type === value ? 'selected' : ''}>${label}</option>`)
                .join('');
            
            let contentField;
            const isListType = element.type === 'ul' || element.type === 'ol';
            const isImageType = element.type === 'img';
            
            if (isListType) {
                contentField = `<textarea onchange="updateElement('${element.id}', 'content', this.value)" placeholder="En linje per punkt">${element.content}</textarea>`;
            } else if (isImageType) {
                const currentImage = element.content ? `<img src="${element.content}" alt="${element.attributes.alt || ''}" style="max-width: 200px; max-height: 100px; object-fit: cover; margin-top: 0.5rem;">` : '';
                contentField = `
                    <div>
                        <button type="button" class="btn btn-primary" onclick="openImageSelectorModal('${element.id}')">🖼️ Vælg Billede</button>
                        <input type="text" onchange="updateElement('${element.id}', 'content', this.value)" value="${element.content}" placeholder="Eller indtast billede URL" style="margin-top: 0.5rem;">
                        ${currentImage}
                    </div>`;
            } else {
                contentField = `<input type="text" onchange="updateElement('${element.id}', 'content', this.value)" value="${element.content}" placeholder="Indhold">`;
            }
            
            // Additional fields for different element types
            let additionalFields = '';
            if (isImageType) {
                additionalFields = `
                    <div class="element-form-row">
                        <div>
                            <label>Alt tekst:</label>
                            <input type="text" onchange="updateElement('${element.id}', 'attributes.alt', this.value)" value="${element.attributes.alt || ''}" placeholder="Alt beskrivelse">
                        </div>
                    </div>`;
            } else if (element.type === 'a') {
                additionalFields = `
                    <div class="element-form-row">
                        <div>
                            <label>Link URL:</label>
                            <input type="text" onchange="updateElement('${element.id}', 'attributes.href', this.value)" value="${element.attributes.href || ''}" placeholder="https://example.com">
                        </div>
                    </div>`;
            }
            
            return `
                <div class="element-item">
                    <div class="element-header" onclick="toggleElementEditor('${element.id}')">
                        <span><strong>${elementTypes[element.type]}</strong>: ${element.content.substring(0, 50)}${element.content.length > 50 ? '...' : ''}</span>
                        <div class="element-controls" onclick="event.stopPropagation()">
                            ${index > 0 ? `<button type="button" class="btn-move-up" onclick="moveElementUp('${element.id}')">↑</button>` : ''}
                            ${index < contentElements.length - 1 ? `<button type="button" class="btn-move-down" onclick="moveElementDown('${element.id}')">↓</button>` : ''}
                            <button type="button" class="btn-delete-element" onclick="removeElement('${element.id}')">🗑️</button>
                        </div>
                    </div>
                    <div class="element-content" id="elementContent_${element.id}">
                        <div class="element-form">
                            <div class="element-form-row">
                                <div>
                                    <label>Element Type:</label>
                                    <select onchange="updateElement('${element.id}', 'type', this.value); renderElements();">
                                        ${typeOptions}
                                    </select>
                                </div>
                                <div>
                                    <label>CSS Class:</label>
                                    <input type="text" onchange="updateElement('${element.id}', 'attributes.class', this.value)" value="${element.attributes.class || ''}" placeholder="CSS klasse">
                                </div>
                            </div>
                            <div class="element-form-row">
                                <div>
                                    <label>Indhold:</label>
                                    ${contentField}
                                </div>
                            </div>
                            ${additionalFields}
                        </div>
                    </div>
                </div>`;
        }
        
        function openPageModal(pageId = null) {
            const modal = document.getElementById('pageModal');
            const form = document.getElementById('pageForm');
            const modalTitle = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            
            if (pageId) {
                modalTitle.textContent = 'Rediger Side';
                formAction.value = 'update_page';
                
                // Load page data
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_page&id=' + pageId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const page = data.page;
                        document.getElementById('pageId').value = page.id;
                        document.getElementById('title').value = page.title;
                        document.getElementById('slug').value = page.slug;
                        document.getElementById('content').value = page.content;
                        document.getElementById('status').value = page.status;
                        document.getElementById('menu_order').value = page.settings.menu_order;
                        document.getElementById('show_in_menu').checked = page.settings.show_in_menu;
                        document.getElementById('meta_description').value = page.meta.description;
                        document.getElementById('meta_keywords').value = page.meta.keywords;
                        
                        // Load content elements
                        contentElements = [];
                        elementIdCounter = 1;
                        
                        if (page.content_elements && Array.isArray(page.content_elements)) {
                            page.content_elements.forEach(element => {
                                contentElements.push({
                                    id: 'element_' + elementIdCounter++,
                                    type: element.type,
                                    content: element.content,
                                    attributes: element.attributes || {}
                                });
                            });
                        }
                        
                        renderElements();
                    }
                });
            } else {
                modalTitle.textContent = 'Ny Side';
                formAction.value = 'create_page';
                form.reset();
                document.getElementById('pageId').value = '';
                
                // Reset content elements
                contentElements = [];
                elementIdCounter = 1;
                renderElements();
            }
            
            modal.style.display = 'block';
        }
        
        function closePageModal() {
            document.getElementById('pageModal').style.display = 'none';
        }
        
        function editPage(id) {
            openPageModal(id);
        }
        
        function deletePage(id) {
            if (confirm('Er du sikker på at du vil slette denne side?')) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=delete_page&id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Fejl ved sletning af side');
                    }
                });
            }
        }
        
        document.getElementById('pageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Add content elements to form data
            contentElements.forEach((element, index) => {
                // Remove the temporary id property before sending
                const elementData = {
                    type: element.type,
                    content: element.content,
                    attributes: element.attributes
                };
                
                formData.append(`content_elements[${index}][type]`, elementData.type);
                formData.append(`content_elements[${index}][content]`, elementData.content);
                formData.append(`content_elements[${index}][attributes][class]`, elementData.attributes.class || '');
                formData.append(`content_elements[${index}][attributes][id]`, elementData.attributes.id || '');
            });
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closePageModal();
                    location.reload();
                } else {
                    alert('Fejl ved gemning af side');
                }
            });
        });
        
        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function() {
            const slug = document.getElementById('slug');
            if (!slug.value || slug.value === '') {
                const title = this.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slug.value = title;
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('pageModal');
            if (event.target === modal) {
                closePageModal();
            }
            
            // Handle other modals
            const imageUploadModal = document.getElementById('imageUploadModal');
            if (event.target === imageUploadModal) {
                closeImageUploadModal();
            }
            
            const imageEditModal = document.getElementById('imageEditModal');
            if (event.target === imageEditModal) {
                closeImageEditModal();
            }
            
            const imageSelectorModal = document.getElementById('imageSelectorModal');
            if (event.target === imageSelectorModal) {
                closeImageSelectorModal();
            }
        }
        
        // Initialize drag and drop when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupImageUpload();
        });
    </script>
</body>
</html>