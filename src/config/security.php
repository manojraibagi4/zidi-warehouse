<?php
// src/config/security.php

/**
 * Security configuration and headers
 * Implements production-ready security best practices
 */

class SecurityConfig {

    /**
     * Apply security headers
     */
    public static function applySecurityHeaders(): void {
        // Prevent clickjacking
        header("X-Frame-Options: SAMEORIGIN");

        // XSS Protection
        header("X-XSS-Protection: 1; mode=block");

        // Prevent MIME type sniffing
        header("X-Content-Type-Options: nosniff");

        // Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // Content Security Policy (adjust based on your needs)
        if (Environment::isProduction()) {
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;");
        }

        // Force HTTPS in production
        if (Environment::isProduction() && !self::isHttps()) {
            header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
            exit();
        }

        // HTTP Strict Transport Security (HSTS) - only in production
        if (Environment::isProduction() && self::isHttps()) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
    }

    /**
     * Check if connection is HTTPS
     */
    private static function isHttps(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Configure PHP error reporting based on environment
     */
    public static function configureErrorReporting(): void {
        if (Environment::isProduction()) {
            // Production: log errors, don't display them
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('log_errors', '1');
            ini_set('error_log', __DIR__ . '/../../php-errors.log');
        } else {
            // Development: display all errors
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        }
    }

    /**
     * Configure session security
     */
    public static function configureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Prevent session fixation
            ini_set('session.use_strict_mode', '1');

            // Use cookies only for session
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');

            // Prevent JavaScript access to session cookie
            ini_set('session.cookie_httponly', '1');

            // Force HTTPS for session cookie in production
            if (Environment::isProduction() && self::isHttps()) {
                ini_set('session.cookie_secure', '1');
            }

            // Set SameSite attribute
            ini_set('session.cookie_samesite', 'Lax');

            // Session lifetime
            $lifetime = (int)Environment::get('SESSION_LIFETIME', 3600);
            ini_set('session.gc_maxlifetime', (string)$lifetime);
            ini_set('session.cookie_lifetime', (string)$lifetime);

            session_start();

            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 1800) {
                // Regenerate session ID every 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }

    /**
     * Sanitize input data
     */
    public static function sanitizeInput(string $data): string {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    /**
     * Validate and sanitize array of inputs
     */
    public static function sanitizeArray(array $data): array {
        $cleaned = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $cleaned[$key] = self::sanitizeArray($value);
            } else {
                $cleaned[$key] = self::sanitizeInput((string)$value);
            }
        }
        return $cleaned;
    }

    /**
     * Custom error handler
     */
    public static function customErrorHandler($errno, $errstr, $errfile, $errline): bool {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $error_type = self::getErrorType($errno);
        $message = "[$error_type] $errstr in $errfile on line $errline";

        error_log($message);

        if (Environment::isDevelopment()) {
            echo "<b>$error_type:</b> $errstr in <b>$errfile</b> on line <b>$errline</b><br>";
        }

        return true;
    }

    /**
     * Get error type name
     */
    private static function getErrorType($errno): string {
        switch ($errno) {
            case E_ERROR:
            case E_USER_ERROR:
                return 'ERROR';
            case E_WARNING:
            case E_USER_WARNING:
                return 'WARNING';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'NOTICE';
            default:
                return 'UNKNOWN';
        }
    }

    /**
     * Initialize all security configurations
     */
    public static function init(): void {
        // Load environment first
        require_once __DIR__ . '/environment.php';
        Environment::load();

        // Configure error reporting
        self::configureErrorReporting();

        // Set custom error handler
        set_error_handler([self::class, 'customErrorHandler']);

        // Apply security headers
        self::applySecurityHeaders();

        // Configure session security
        self::configureSession();
    }
}
