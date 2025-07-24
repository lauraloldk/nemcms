<?php
class PageManager {
    private $dataFile = 'pages.json';
    
    public function __construct() {
        if (!file_exists($this->dataFile)) {
            $this->initializeDataFile();
        }
    }
    
    private function initializeDataFile() {
        $defaultData = [
            'pages' => [],
            'meta' => [
                'next_id' => 1,
                'version' => '1.0',
                'last_modified' => date('c')
            ]
        ];
        file_put_contents($this->dataFile, json_encode($defaultData, JSON_PRETTY_PRINT));
    }
    
    public function getData() {
        $data = file_get_contents($this->dataFile);
        return json_decode($data, true);
    }
    
    public function saveData($data) {
        $data['meta']['last_modified'] = date('c');
        return file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function getAllPages($status = null) {
        $data = $this->getData();
        $pages = $data['pages'];
        
        if ($status !== null) {
            $pages = array_filter($pages, function($page) use ($status) {
                return $page['status'] === $status;
            });
        }
        
        return $pages;
    }
    
    public function getPageById($id) {
        $data = $this->getData();
        foreach ($data['pages'] as $page) {
            if ($page['id'] == $id) {
                return $page;
            }
        }
        return null;
    }
    
    public function getPageBySlug($slug) {
        $data = $this->getData();
        foreach ($data['pages'] as $page) {
            if ($page['slug'] === $slug) {
                return $page;
            }
        }
        return null;
    }
    
    public function createPage($pageData) {
        $data = $this->getData();
        
        // Generate new ID
        $newId = $data['meta']['next_id'];
        $data['meta']['next_id']++;
        
        // Default page structure
        $defaultPage = [
            'id' => $newId,
            'title' => '',
            'slug' => '',
            'content' => '', // Legacy content field for backward compatibility
            'content_elements' => [], // New modular content structure
            'status' => 'draft',
            'created_at' => date('c'),
            'updated_at' => date('c'),
            'meta' => [
                'description' => '',
                'keywords' => ''
            ],
            'settings' => [
                'show_in_menu' => true,
                'menu_order' => 999,
                'template' => 'default'
            ]
        ];
        
        // Merge with provided data
        $newPage = array_merge($defaultPage, $pageData);
        $newPage['id'] = $newId; // Ensure ID is not overwritten
        
        // Generate slug if not provided
        if (empty($newPage['slug'])) {
            $newPage['slug'] = $this->generateSlug($newPage['title']);
        }
        
        // Validate slug uniqueness
        if ($this->getPageBySlug($newPage['slug'])) {
            $newPage['slug'] = $this->generateUniqueSlug($newPage['slug']);
        }
        
        $data['pages'][] = $newPage;
        $this->saveData($data);
        
        return $newPage;
    }
    
    public function updatePage($id, $pageData) {
        $data = $this->getData();
        
        foreach ($data['pages'] as &$page) {
            if ($page['id'] == $id) {
                // Preserve certain fields
                $pageData['id'] = $page['id'];
                $pageData['created_at'] = $page['created_at'];
                $pageData['updated_at'] = date('c');
                
                // Update page
                $page = array_merge($page, $pageData);
                
                $this->saveData($data);
                return $page;
            }
        }
        
        return null;
    }
    
    public function deletePage($id) {
        $data = $this->getData();
        
        foreach ($data['pages'] as $key => $page) {
            if ($page['id'] == $id) {
                unset($data['pages'][$key]);
                $data['pages'] = array_values($data['pages']); // Reindex
                $this->saveData($data);
                return true;
            }
        }
        
        return false;
    }
    
    private function generateSlug($title) {
        // Simple slug generation
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
    
    private function generateUniqueSlug($baseSlug) {
        $counter = 1;
        $newSlug = $baseSlug . '-' . $counter;
        
        while ($this->getPageBySlug($newSlug)) {
            $counter++;
            $newSlug = $baseSlug . '-' . $counter;
        }
        
        return $newSlug;
    }
    
    public function getMenuPages() {
        $pages = $this->getAllPages('published');
        $menuPages = array_filter($pages, function($page) {
            return $page['settings']['show_in_menu'];
        });
        
        // Sort by menu order
        usort($menuPages, function($a, $b) {
            return $a['settings']['menu_order'] - $b['settings']['menu_order'];
        });
        
        return $menuPages;
    }
    
    /**
     * Convert content elements to HTML
     */
    public function renderContentElements($contentElements) {
        if (empty($contentElements) || !is_array($contentElements)) {
            return '';
        }
        
        $html = '';
        foreach ($contentElements as $element) {
            $html .= $this->renderSingleElement($element);
        }
        
        return $html;
    }
    
    /**
     * Render a single content element
     */
    private function renderSingleElement($element) {
        if (!isset($element['type']) || !isset($element['content'])) {
            return '';
        }
        
        $type = $element['type'];
        $content = htmlspecialchars($element['content']);
        $attributes = $element['attributes'] ?? [];
        
        // Build attribute string
        $attrString = '';
        if (!empty($attributes)) {
            foreach ($attributes as $key => $value) {
                if (!empty($value)) {
                    $attrString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
                }
            }
        }
        
        switch ($type) {
            case 'h1':
                return "<h1{$attrString}>{$content}</h1>\n";
            case 'h2':
                return "<h2{$attrString}>{$content}</h2>\n";
            case 'h3':
                return "<h3{$attrString}>{$content}</h3>\n";
            case 'p':
                return "<p{$attrString}>{$content}</p>\n";
            case 'div':
                return "<div{$attrString}>{$content}</div>\n";
            case 'span':
                return "<span{$attrString}>{$content}</span>\n";
            case 'blockquote':
                return "<blockquote{$attrString}>{$content}</blockquote>\n";
            case 'ul':
                // For lists, content should be array of items
                $items = is_array($element['content']) ? $element['content'] : explode("\n", $element['content']);
                $listHTML = "<ul{$attrString}>\n";
                foreach ($items as $item) {
                    if (!empty(trim($item))) {
                        $listHTML .= "  <li>" . htmlspecialchars(trim($item)) . "</li>\n";
                    }
                }
                $listHTML .= "</ul>\n";
                return $listHTML;
            case 'ol':
                // For ordered lists
                $items = is_array($element['content']) ? $element['content'] : explode("\n", $element['content']);
                $listHTML = "<ol{$attrString}>\n";
                foreach ($items as $item) {
                    if (!empty(trim($item))) {
                        $listHTML .= "  <li>" . htmlspecialchars(trim($item)) . "</li>\n";
                    }
                }
                $listHTML .= "</ol>\n";
                return $listHTML;
            case 'img':
                $src = htmlspecialchars($element['content']);
                $alt = htmlspecialchars($attributes['alt'] ?? '');
                // Add CSS class for responsive images and lightbox functionality
                $class = isset($attributes['class']) ? $attributes['class'] . ' content-image' : 'content-image';
                return "<img src=\"{$src}\" alt=\"{$alt}\" class=\"{$class}\" onclick=\"viewImageLightbox('{$src}', '{$alt}')\"{$attrString}>\n";
            case 'a':
                $href = htmlspecialchars($attributes['href'] ?? '');
                return "<a href=\"{$href}\"{$attrString}>{$content}</a>\n";
            default:
                // For unknown types, render as div
                return "<div{$attrString}>{$content}</div>\n";
        }
    }
    
    /**
     * Get available element types
     */
    public function getAvailableElementTypes() {
        return [
            'h1' => 'Stor Overskrift (H1)',
            'h2' => 'Medium Overskrift (H2)', 
            'h3' => 'Lille Overskrift (H3)',
            'p' => 'Normal Tekst (Paragraph)',
            'div' => 'Container (Div)',
            'blockquote' => 'Citat',
            'ul' => 'Punktliste',
            'ol' => 'Nummereret Liste',
            'img' => 'Billede',
            'a' => 'Link'
        ];
    }
    
    /**
     * Validate content element
     */
    public function validateContentElement($element) {
        if (!is_array($element)) {
            return false;
        }
        
        if (!isset($element['type']) || !isset($element['content'])) {
            return false;
        }
        
        $allowedTypes = array_keys($this->getAvailableElementTypes());
        if (!in_array($element['type'], $allowedTypes)) {
            return false;
        }
        
        return true;
    }
}
?>
