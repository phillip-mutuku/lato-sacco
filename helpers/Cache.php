<?php
class SimpleCache {
    private static $cacheDir;
    private static $defaultTTL = 3600; // 1 hour
    
    public static function init($dir = null) {
        self::$cacheDir = $dir ?: __DIR__ . '/../storage/cache/';
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
    
    public static function get($key, $default = null) {
        $file = self::getCacheFile($key);
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return $default;
        }
        
        return $data['value'];
    }
    
    public static function set($key, $value, $ttl = null) {
        $ttl = $ttl ?: self::$defaultTTL;
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        $file = self::getCacheFile($key);
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    public static function delete($key) {
        $file = self::getCacheFile($key);
        return !file_exists($file) || unlink($file);
    }
    
    private static function getCacheFile($key) {
        return self::$cacheDir . md5($key) . '.cache';
    }
    
    public static function clear() {
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

// Initialize cache
SimpleCache::init();
?>