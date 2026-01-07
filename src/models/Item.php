<?php
// src/models/Item.php

class Item {
    public $id;
    public $productname;
    public $manufacturer;
    public $description;
    public $size;
    public $color;
    public $quantity;
    public $img; // Path to image (e.g., /img/uploaded/my-image.jpg)
    public ?string $mime_type = 'image/png'; // <-- add this

    public $grafted; // Boolean (stored as TINYINT(1) in DB, 0 or 1)
    public $club; // Text or null
    public $expiration_year; // Year (e.g., 2028)
    public $last_change; // DATETIME string (e.g., 'YYYY-MM-DD HH:MM:SS')
    public $last_edited_by; // <-- NEW FIELD

    public ?string $category = null;
    public ?string $article_no = null;
    public ?string $expiry_date = null;
    public ?string $color_number = null;
    public ?float $unit_price = null;
    public ?float $total_price = null;
	public ?string $supplier = null;

    // The Item class constructor might not strictly need the connection
    // if all DB operations are handled by ItemRepository.
    // However, including it makes the object more self-contained if needed later.
    public function __construct(?mysqli $conn = null) {
        // You can use $conn here if this class itself needs to interact with the DB,
        // but typically the repository handles that.
        // For now, it's just accepting it.
    }
}
?>