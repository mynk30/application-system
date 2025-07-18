<?php
class BrowserLogger {
    private static $instance = null;
    private $logs = [];

    private function __construct() {
        // Private constructor to prevent direct instantiation
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function formatMessage($message, $type = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return "[$timestamp] [$type] [$ip] $message";
    }

    private function logToConsole($message, $type = 'info') {
        $this->logs[] = $message;
        
        // Generate JavaScript to log the message
        echo '<script>';
        echo 'if (typeof console !== "undefined" && typeof console.log === "function") {';
        echo 'console.' . $type . '("' . addslashes($message) . '");';
        echo '}';
        echo '</script>';
    }

    public function log($message) {
        $this->logToConsole($this->formatMessage($message, 'info'), 'info');
    }

    public function error($message) {
        $this->logToConsole($this->formatMessage($message, 'error'), 'error');
    }

    public function warn($message) {
        $this->logToConsole($this->formatMessage($message, 'warn'), 'warn');
    }

    public function debug($message) {
        $this->logToConsole($this->formatMessage($message, 'debug'), 'debug');
    }
}
?>