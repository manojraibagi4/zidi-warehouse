<?php
function validateLogin(array $data): array {
    $errors = [];
    if (empty(trim($data['username'] ?? ''))) {
        $errors[] = "Username is required.";
    }
    if (empty(trim($data['password'] ?? ''))) {
        $errors[] = "Password is required.";
    }
    return $errors;
}

function validateSignup(array $data): array {
    $errors = [];
    if (empty(trim($data['username'] ?? '')) || !preg_match('/^[A-Za-z0-9_]{3,20}$/', $data['username'])) {
        $errors[] = "Username must be 3–20 alphanumeric characters.";
    }
    if (empty(trim($data['email'] ?? '')) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($data['password']) || strlen($data['password']) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    return $errors;
}

function validateItem(array $data): array {
    $errors = [];
    
    $productname = trim($data['productname'] ?? '');
    $length = strlen($productname);
    
    // Allow letters, numbers, spaces, and common punctuation: ().-_
    if (empty($productname) || $length < 3 || $length > 50 || !preg_match('/^[A-Za-z0-9\s()\.\-_]+$/', $productname)) {
        $errors[] = "Product name must be 3–50 characters and contain only letters, numbers, spaces, and basic punctuation.";
    }
    
    if (!empty($data['manufacturer']) && strlen($data['manufacturer']) > 50) {
        $errors[] = "Manufacturer name too long.";
    }
    
    if (!empty($data['quantity']) && (!is_numeric($data['quantity']) || $data['quantity'] < 0)) {
        $errors[] = "Quantity must be a positive number.";
    }
    
    return $errors;
}
