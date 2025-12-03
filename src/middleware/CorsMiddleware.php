<?php
// src/middleware/CorsMiddleware.php

/**
 * CORS Middleware for request processing
 * Integrates CORS validation into the request lifecycle
 */

class CorsMiddleware {
    
    /**
     * Process request through CORS middleware
     * 
     * @param callable|null $next Next middleware/handler
     * @return mixed
     */
    public static function handle(?callable $next = null) {
        // Apply CORS handling
        if (!CorsHandler::handle()) {
            // CORS validation failed
            self::sendCorsError();
            return false;
        }
        
        // Continue to next middleware or handler
        if ($next && is_callable($next)) {
            return $next();
        }
        
        return true;
    }
    
    /**
     * Send CORS error response
     */
    private static function sendCorsError(): void {
        header('Content-Type: application/json');
        
        $error = [
            'error' => 'CORS_ERROR',
            'message' => 'Cross-origin request not allowed',
            'timestamp' => date('c')
        ];
        
        echo json_encode($error);
        exit;
    }
    
    /**
     * Validate specific request for CORS compliance
     * 
     * @param array $request_data Request data to validate
     * @return bool
     */
    public static function validateRequest(array $request_data = []): bool {
        // Additional request-specific CORS validation can go here
        
        // Validate content type for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            $allowedContentTypes = [
                'application/json',
                'application/x-www-form-urlencoded',
                'multipart/form-data',
                'text/plain'
            ];
            
            $isValidContentType = false;
            foreach ($allowedContentTypes as $allowed) {
                if (strpos($contentType, $allowed) === 0) {
                    $isValidContentType = true;
                    break;
                }
            }
            
            if (!$isValidContentType) {
                error_log("CORS: Invalid content type: " . $contentType);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Set additional security headers
     */
    public static function setSecurityHeaders(): void {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Content type sniffing protection
        header('X-Content-Type-Options: nosniff');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Don't cache sensitive responses
        if (self::isSensitiveEndpoint()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    /**
     * Check if current endpoint handles sensitive data
     */
    private static function isSensitiveEndpoint(): bool {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        $sensitivePatterns = [
            '/login',
            '/logout',
            '/signup',
            '/settings',
            '/users',
            '/delete',
            '/backup',
            '/restore'
        ];
        
        foreach ($sensitivePatterns as $pattern) {
            if (strpos($uri, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
}