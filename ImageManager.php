<?php
class ImageManager {
    private $dataFile = 'images.json';
    private $uploadPath = 'images/';
    
    public function __construct() {
        if (!file_exists($this->dataFile)) {
            $this->initializeDataFile();
        }
        
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    private function initializeDataFile() {
        $defaultData = [
            'images' => [],
            'meta' => [
                'version' => '1.0',
                'last_modified' => date('c'),
                'upload_path' => $this->uploadPath,
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'max_file_size' => 5242880 // 5MB
            ]
        ];
        file_put_contents($this->dataFile, json_encode($defaultData, JSON_PRETTY_PRINT));
    }
    
    public function getData() {
        if (!file_exists($this->dataFile)) {
            $this->initializeDataFile();
        }
        
        $data = file_get_contents($this->dataFile);
        $decoded = json_decode($data, true);
        
        if ($decoded === null || !is_array($decoded)) {
            $this->initializeDataFile();
            $data = file_get_contents($this->dataFile);
            $decoded = json_decode($data, true);
        }
        
        if (!isset($decoded['images'])) {
            $decoded['images'] = [];
        }
        
        return $decoded;
    }
    
    public function saveData($data) {
        if (!is_array($data)) {
            $data = [];
        }
        
        if (!isset($data['images'])) {
            $data['images'] = [];
        }
        
        $data['meta']['last_modified'] = date('c');
        
        $result = file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT));
        if ($result === false) {
            error_log("Failed to save image data to " . $this->dataFile);
        }
        return $result;
    }
    
    public function getAllImages() {
        $data = $this->getData();
        return $data['images'];
    }
    
    public function getImageById($id) {
        $data = $this->getData();
        foreach ($data['images'] as $image) {
            if ($image['id'] == $id) {
                return $image;
            }
        }
        return null;
    }
    
    public function getImageByFilename($filename) {
        $data = $this->getData();
        foreach ($data['images'] as $image) {
            if ($image['filename'] === $filename) {
                return $image;
            }
        }
        return null;
    }
    
    public function uploadImage($file, $title = '', $alt = '') {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Ingen gyldig fil uploaded'];
        }
        
        // Check file size
        $data = $this->getData();
        $maxSize = $data['meta']['max_file_size'];
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Filen er for stor. Maksimum: ' . ($maxSize / 1024 / 1024) . 'MB'];
        }
        
        // Check file type
        $allowedTypes = $data['meta']['allowed_types'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            return ['success' => false, 'error' => 'Filtype ikke tilladt. Tilladte: ' . implode(', ', $allowedTypes)];
        }
        
        // Generate unique filename
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeFilename = $this->sanitizeFilename($originalName);
        $newFilename = $safeFilename . '.' . $fileExtension;
        $counter = 1;
        
        while (file_exists($this->uploadPath . $newFilename)) {
            $newFilename = $safeFilename . '_' . $counter . '.' . $fileExtension;
            $counter++;
        }
        
        // Move uploaded file
        $targetPath = $this->uploadPath . $newFilename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'Fejl ved upload af fil'];
        }
        
        // Get image dimensions
        $imageInfo = getimagesize($targetPath);
        $width = $imageInfo ? $imageInfo[0] : 0;
        $height = $imageInfo ? $imageInfo[1] : 0;
        
        // Create image record
        $imageRecord = [
            'id' => $this->getNextId(),
            'filename' => $newFilename,
            'original_name' => $file['name'],
            'title' => $title ?: $originalName,
            'alt' => $alt ?: $title ?: $originalName,
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'width' => $width,
            'height' => $height,
            'uploaded_at' => date('c')
        ];
        
        // Save to database
        $data['images'][] = $imageRecord;
        $this->saveData($data);
        
        return ['success' => true, 'image' => $imageRecord];
    }
    
    public function deleteImage($id) {
        $data = $this->getData();
        
        foreach ($data['images'] as $key => $image) {
            if ($image['id'] == $id) {
                // Delete file
                $filePath = $this->uploadPath . $image['filename'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Remove from database
                unset($data['images'][$key]);
                $data['images'] = array_values($data['images']); // Reindex
                $this->saveData($data);
                
                return true;
            }
        }
        
        return false;
    }
    
    public function updateImage($id, $updateData) {
        $data = $this->getData();
        
        foreach ($data['images'] as &$image) {
            if ($image['id'] == $id) {
                if (isset($updateData['title'])) {
                    $image['title'] = $updateData['title'];
                }
                if (isset($updateData['alt'])) {
                    $image['alt'] = $updateData['alt'];
                }
                
                $this->saveData($data);
                return $image;
            }
        }
        
        return null;
    }
    
    private function getNextId() {
        $data = $this->getData();
        $maxId = 0;
        
        foreach ($data['images'] as $image) {
            if ($image['id'] > $maxId) {
                $maxId = $image['id'];
            }
        }
        
        return $maxId + 1;
    }
    
    private function sanitizeFilename($filename) {
        // Remove special characters and spaces
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        $filename = trim($filename, '_');
        
        return $filename;
    }
    
    public function getImageUrl($filename) {
        return $this->uploadPath . $filename;
    }
    
    public function getImagePath($filename) {
        return $this->uploadPath . $filename;
    }
    
    public function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
?>
