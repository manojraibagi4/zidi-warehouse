<?php
// src/config/cors.php - Fixed and Simplified CORS Implementation

/**
 * Simple and reliable CORS handler
 * Fixed all deprecated warnings and environment issues
 */

class CorsHandler {
    
    private static $config = null;
    private static $environment = null;

    /**
     * Get current environment
     */
    private static function getEnvironment(): string {
        if (self::$environment !== null) {
            return self::$environment;
        }

        // Check multiple sources for environment
        $env = null;
        
        // Try $_ENV first
        if (isset($_ENV['APP_ENV'])) {
            $env = $_ENV['APP_ENV'];
        }
        // Try getenv() as fallback
        elseif (getenv('APP_ENV') !== false) {
            $env = getenv('APP_ENV');
        }
        // Try $_SERVER as another fallback
        elseif (isset($_SERVER['APP_ENV'])) {
            $env = $_SERVER['APP_ENV'];
        }
        // Default to development for safety
        else {
            $env = 'development';
        }

        self::$environment = $env;
        return $env;
    }

    /**
     * Initialize CORS configuration
     */
    private static function initConfig(): void {
        if (self::$config !== null) {
            return;
        }

        $environment = self::getEnvironment();

        // Get allowed origins from environment variable
        $allowedOrigins = self::getAllowedOrigins();

        // Default development origins
        $defaultDevOrigins = [
            'http://localhost',
            'http://localhost:3000',
            'http://localhost:8000',
            'http://localhost:8080',
            'http://127.0.0.1',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8000',
            'http://127.0.0.1:8080'
        ];

        // Default production origins
        $defaultProdOrigins = [
            'https://yourdomain.com',
            'https://www.yourdomain.com'
        ];

        self::$config = [
            'development' => [
                'allowed_origins' => !empty($allowedOrigins) ? $allowedOrigins : $defaultDevOrigins,
                'allow_credentials' => self::getEnvBool('CORS_ALLOW_CREDENTIALS', true),
                'max_age' => self::getEnvInt('CORS_MAX_AGE', 3600),
                'strict_validation' => self::getEnvBool('CORS_STRICT_VALIDATION', false),
                'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
                'allowed_headers' => [
                    'Content-Type',
                    'Authorization',
                    'X-Requested-With',
                    'X-CSRF-Token',
                    'Accept',
                    'Origin'
                ]
            ],
            'production' => [
                'allowed_origins' => !empty($allowedOrigins) ? $allowedOrigins : $defaultProdOrigins,
                'allow_credentials' => self::getEnvBool('CORS_ALLOW_CREDENTIALS', true),
                'max_age' => self::getEnvInt('CORS_MAX_AGE', 86400),
                'strict_validation' => self::getEnvBool('CORS_STRICT_VALIDATION', true),
                'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'allowed_headers' => [
                    'Content-Type',
                    'Authorization',
                    'X-Requested-With',
                    'X-CSRF-Token',
                    'Accept'
                ]
            ]
        ];
    }

    /**
     * Get allowed origins from environment variable
     */
    private static function getAllowedOrigins(): array {
        $originsEnv = self::getEnvValue('CORS_ALLOWED_ORIGINS');

        if (empty($originsEnv)) {
            return [];
        }

        // Split by comma and trim whitespace
        $origins = array_map('trim', explode(',', $originsEnv));

        // Filter out empty values
        return array_filter($origins);
    }

    /**
     * Get environment value
     */
    private static function getEnvValue(string $key, $default = null) {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return $default;
    }

    /**
     * Get boolean environment value
     */
    private static function getEnvBool(string $key, bool $default = false): bool {
        $value = self::getEnvValue($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get integer environment value
     */
    private static function getEnvInt(string $key, int $default = 0): int {
        $value = self::getEnvValue($key);

        if ($value === null) {
            return $default;
        }

        return (int)$value;
    }

    /**
     * Get current environment configuration
     */
    private static function getConfig(): array {
        self::initConfig();
        $environment = self::getEnvironment();
        return self::$config[$environment] ?? self::$config['development'];
    }

    /**
     * Get request origin with fallback
     */
    private static function getRequestOrigin(): ?string {
        // Check HTTP_ORIGIN first (most reliable for CORS)
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            return rtrim($_SERVER['HTTP_ORIGIN'], '/');
        }

        // Fallback to HTTP_REFERER if no origin
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $parsed = parse_url($_SERVER['HTTP_REFERER']);
            if ($parsed && isset($parsed['scheme'], $parsed['host'])) {
                $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
                return $parsed['scheme'] . '://' . $parsed['host'] . $port;
            }
        }

        // Final fallback: construct from current request
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            return $scheme . '://' . $_SERVER['HTTP_HOST'];
        }

        return null;
    }

    /**
     * Validate origin
     */
    private static function validateOrigin(string $origin): bool {
        // Basic URL validation
        if (!filter_var($origin, FILTER_VALIDATE_URL)) {
            error_log("CORS: Invalid origin format: " . $origin);
            return false;
        }

        $config = self::getConfig();
        
        // Check if origin is in allowed list
        if (in_array($origin, $config['allowed_origins'], true)) {
            return true;
        }

        // For development, be more permissive
        if (self::getEnvironment() === 'development') {
            $parsed = parse_url($origin);
            if ($parsed && isset($parsed['host'])) {
                // Allow localhost variations
                if (in_array($parsed['host'], ['localhost', '127.0.0.1'])) {
                    return true;
                }
            }
        }

        error_log("CORS: Origin not allowed: " . $origin);
        return false;
    }

    /**
     * Set CORS headers
     */
    private static function setCorsHeaders(string $origin): void {
        $config = self::getConfig();
        
        header("Access-Control-Allow-Origin: " . $origin);
        header("Access-Control-Allow-Methods: " . implode(', ', $config['allowed_methods']));
        header("Access-Control-Allow-Headers: " . implode(', ', $config['allowed_headers']));
        
        if ($config['allow_credentials']) {
            header("Access-Control-Allow-Credentials: true");
        }
        
        header("Access-Control-Max-Age: " . $config['max_age']);
        header("Vary: Origin");
    }

    /**
     * Handle preflight OPTIONS request
     */
    private static function handlePreflight(): void {
        http_response_code(200);
        exit;
    }

    /**
     * Main CORS handler
     */
    public static function handle(): bool {
        try {

            $cors_enabled = self::getEnvBool('CORS_ENABLED', false);

            if (!$cors_enabled) {
                // CORS is disabled
                return true;
            }
            $origin = self::getRequestOrigin();
            
            // If no origin, it might be a same-origin request
            if (!$origin) {
                return true;
            }

            // Validate origin
            if (!self::validateOrigin($origin)) {
                return false;
            }

            // Set CORS headers
            self::setCorsHeaders($origin);

            // Handle preflight
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                self::handlePreflight();
            }

            return true;

        } catch (Exception $e) {
            error_log("CORS Handler Error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Legacy function for backward compatibility
 */
function handleCors(): bool {
    return CorsHandler::handle();
}

/**
 * Simple middleware function
 */
function applyCorsMiddleware(): bool {
    
    if (!CorsHandler::handle()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'CORS_ERROR',
            'message' => 'Cross-origin request not allowed'
        ]);
        return false;
    }
    return true;
}