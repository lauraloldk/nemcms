<?php
session_start();

class UserIPManager {
    private $dataFile = 'usersAndAdmins.json';
    
    public function __construct() {
        if (!file_exists($this->dataFile)) {
            $this->initializeDataFile();
        }
    }
    
    private function initializeDataFile() {
        $defaultData = [
            'admins' => [],
            'users' => [],
            'settings' => [
                'auto_register_first_admin' => true,
                'site_name' => 'CMS System',
                'version' => '1.0'
            ]
        ];
        file_put_contents($this->dataFile, json_encode($defaultData, JSON_PRETTY_PRINT));
    }
    
    public function getCurrentIP() {
        // Priority order for IP detection
        $ipSources = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Shared Internet
            'HTTP_X_FORWARDED_FOR',      // Proxy/Load Balancer
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ipSources as $source) {
            if (!empty($_SERVER[$source])) {
                $ip = $_SERVER[$source];
                
                // Handle comma-separated IPs (X-Forwarded-For can contain multiple)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP format
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
                
                // Also accept private/local IPs for development
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to localhost if nothing found
        return '127.0.0.1';
    }
    
    public function getData() {
        if (!file_exists($this->dataFile)) {
            $this->initializeDataFile();
        }
        
        $data = file_get_contents($this->dataFile);
        $decoded = json_decode($data, true);
        
        // If JSON decode failed or returned null, reinitialize
        if ($decoded === null || !is_array($decoded)) {
            $this->initializeDataFile();
            $data = file_get_contents($this->dataFile);
            $decoded = json_decode($data, true);
        }
        
        // Ensure required keys exist
        if (!isset($decoded['admins'])) {
            $decoded['admins'] = [];
        }
        if (!isset($decoded['users'])) {
            $decoded['users'] = [];
        }
        if (!isset($decoded['settings'])) {
            $decoded['settings'] = [
                'auto_register_first_admin' => true,
                'site_name' => 'CMS System',
                'version' => '1.0'
            ];
        }
        
        return $decoded;
    }
    
    public function saveData($data) {
        // Ensure data is properly structured
        if (!is_array($data)) {
            $data = [];
        }
        
        if (!isset($data['admins'])) {
            $data['admins'] = [];
        }
        
        if (!isset($data['users'])) {
            $data['users'] = [];
        }
        
        if (!isset($data['settings'])) {
            $data['settings'] = [
                'auto_register_first_admin' => true,
                'site_name' => 'CMS System',
                'version' => '1.0'
            ];
        }
        
        $result = file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT));
        if ($result === false) {
            error_log("Failed to save user data to " . $this->dataFile);
        }
        return $result;
    }
    
    public function isAdmin($ip = null) {
        if ($ip === null) {
            $ip = $this->getCurrentIP();
        }
        
        $data = $this->getData();
        
        // Ensure admins array exists and is an array
        if (!isset($data['admins']) || !is_array($data['admins'])) {
            $data['admins'] = [];
        }
        
        // Auto-register first admin if no admins exist
        if (empty($data['admins']) && isset($data['settings']['auto_register_first_admin']) && $data['settings']['auto_register_first_admin']) {
            $this->registerFirstAdmin($ip);
            return true;
        }
        
        return in_array($ip, $data['admins']);
    }
    
    public function isUser($ip = null) {
        if ($ip === null) {
            $ip = $this->getCurrentIP();
        }
        
        $data = $this->getData();
        
        // Ensure users array exists and is an array
        if (!isset($data['users']) || !is_array($data['users'])) {
            $data['users'] = [];
        }
        
        return in_array($ip, $data['users']);
    }
    
    private function registerFirstAdmin($ip) {
        $data = $this->getData();
        
        // Ensure admins array exists
        if (!isset($data['admins']) || !is_array($data['admins'])) {
            $data['admins'] = [];
        }
        
        // Ensure settings exist
        if (!isset($data['settings'])) {
            $data['settings'] = [];
        }
        
        $data['admins'][] = $ip;
        $data['settings']['auto_register_first_admin'] = false;
        $this->saveData($data);
        
        // Log the registration
        error_log("First admin registered with IP: " . $ip);
    }
    
    public function addAdmin($ip) {
        $data = $this->getData();
        if (!in_array($ip, $data['admins'])) {
            $data['admins'][] = $ip;
            $this->saveData($data);
            return true;
        }
        return false;
    }
    
    public function removeAdmin($ip) {
        $data = $this->getData();
        $key = array_search($ip, $data['admins']);
        if ($key !== false) {
            unset($data['admins'][$key]);
            $data['admins'] = array_values($data['admins']); // Reindex array
            $this->saveData($data);
            return true;
        }
        return false;
    }
    
    public function addUser($ip) {
        $data = $this->getData();
        if (!in_array($ip, $data['users'])) {
            $data['users'][] = $ip;
            $this->saveData($data);
            return true;
        }
        return false;
    }
    
    public function removeUser($ip) {
        $data = $this->getData();
        $key = array_search($ip, $data['users']);
        if ($key !== false) {
            unset($data['users'][$key]);
            $data['users'] = array_values($data['users']); // Reindex array
            $this->saveData($data);
            return true;
        }
        return false;
    }
    
    public function requireAdmin() {
        if (!$this->isAdmin()) {
            http_response_code(403);
            die('Access denied. Admin privileges required.');
        }
    }
    
    public function requireUser() {
        if (!$this->isUser() && !$this->isAdmin()) {
            http_response_code(403);
            die('Access denied. User privileges required.');
        }
    }
}

// Create global instance
$userManager = new UserIPManager();
?>