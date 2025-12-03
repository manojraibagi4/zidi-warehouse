<?php
// src/config/environment.php

/**
 * Environment configuration for CORS and security settings
 * This file should be loaded early in your application bootstrap
 */

class Environment {
    
    private static $loaded = false;
    
    /**
     * Load environment configuration
     */
    public static function load(): void {
        if (self::$loaded) {
            return;
        }
        
        // Try to load from .env file first
        self::loadFromEnvFile();
        
        // Set default environment variables if not already set
        self::setDefaults();
        
        self::$loaded = true;
    }
    
    /**
     * Load environment variables from .env file if it exists
     */
    private static function loadFromEnvFile(): void {
        $envFile = __DIR__ . '/../../.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Skip comments
            }
            
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(\'(.*)\'|"(.*)")$/', $value, $matches)) {
                    $value = isset($matches[2]) ? $matches[2] : $matches[3];
                }
                
                if (!isset($_ENV[$name])) {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }
    }
    
    /**
     * Set default environment values
     */
    private static function setDefaults(): void {
        $defaults = [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'CORS_ENABLED' => 'true',
            'CORS_STRICT_VALIDATION' => 'true',
            'CORS_MAX_AGE' => '86400',
            'CORS_ALLOW_CREDENTIALS' => 'true'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    
    /**
     * Get environment value with optional default
     */
    public static function get(string $key, $default = null) {
        self::load();
        return $_ENV[$key] ?? $default;
    }
    
    /**
     * Check if running in development mode
     */
    public static function isDevelopment(): bool {
        return self::get('APP_ENV') === 'development';
    }
    
    /**
     * Check if running in production mode
     */
    public static function isProduction(): bool {
        return self::get('APP_ENV') === 'production';
    }
    
    /**
     * Check if debug mode is enabled
     */
    public static function isDebug(): bool {
        return self::get('APP_DEBUG') === 'true';
    }
}