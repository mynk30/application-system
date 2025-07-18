<?php
class Logger {
    private static $instance = null;
    private $logFile;
    
    private function __construct() {
        $this->logFile = __DIR__ . '/../logs/app.log';
        // Create logs directory if it doesn't exist
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0777, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function formatMessage($message, $type = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return "[$timestamp] [$type] [$ip] $message\n";
    }
    
    public function log($message, $type = 'INFO') {
        $formatted = $this->formatMessage($message, $type);
        error_log($formatted, 3, $this->logFile);
    }
    
    public function error($message) {
        $this->log($message, 'ERROR');
    }
    
    public function info($message) {
        $this->log($message, 'INFO');
    }
    
    public function debug($message) {
        $this->log($message, 'DEBUG');
    }
    
    public function warning($message) {
        $this->log($message, 'WARNING');
    }
}
?>
