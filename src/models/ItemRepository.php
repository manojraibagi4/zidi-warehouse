<?php
// src/models/ItemRepository.php

class ItemRepository {
    private $conn; // This will hold the mysqli connection object

    private array $appConfig; // To hold the application configuration

    /**
     * Constructor for ItemRepository.
     *
     * @param mysqli $conn The MySQLi database connection object.
     */
    public function __construct(mysqli $conn) {
        $this->conn = $conn;
        $this->appConfig = require __DIR__ . '/../config/app.php';
        error_log("DEBUG: ItemRepository - Constructor called with MySQLi connection.");
    }

    /**
     * Retrieves all items from the database, optionally filtered.
     *
     * @param array $filters An associative array of filter criteria (e.g., productname, manufacturer).
     * @return array An array of item data, each as an associative array.
     */


    public function getAll(array $filters, ?int $limit = null, ?int $offset = null): array
    {
        $conditions = [];
        $params = [];
        $types = "";

        $threshold = $this->appConfig['low_stock_threshold'] ?? 10;

        if (!empty($filters['productname'])) {
            $conditions[] = "productname LIKE ?";
            $params[] = "%" . $filters['productname'] . "%";
            $types .= "s";
        }
        
        // ✅ FIXED: Add article_no filter
        if (!empty($filters['article_no'])) {
            $conditions[] = "article_no LIKE ?";
            $params[] = "%" . $filters['article_no'] . "%";
            $types .= "s";
        }
        
        if (!empty($filters['manufacturer'])) {
            $conditions[] = "manufacturer = ?";
            $params[] = $filters['manufacturer'];
            $types .= "s";
        }
        
        if (!empty($filters['color'])) {
            $conditions[] = "color LIKE ?";
            $params[] = "%" . $filters['color'] . "%";
            $types .= "s";
        }
        
        if (!empty($filters['size'])) {
            $conditions[] = "size LIKE ?";
            $params[] = "%" . $filters['size'] . "%";
            $types .= "s";
        }
        
        // ✅ FIXED: Add supplier filter
        if (!empty($filters['supplier'])) {
            $conditions[] = "supplier = ?";
            $params[] = $filters['supplier'];
            $types .= "s";
        }
        
        // ✅ FIXED: Add category filter
        if (!empty($filters['category'])) {
            $conditions[] = "category = ?";
            $params[] = $filters['category'];
            $types .= "s";
        }
        
        if ($filters['grafted'] !== '') {
            $conditions[] = "grafted = ?";
            $params[] = $filters['grafted'];
            $types .= "s";
        }
        
        if (!empty($filters['club'])) {
            $conditions[] = "club = ?";
            $params[] = $filters['club'];
            $types .= "s";
        }
        
        if (!empty($filters['lowstock'])) {
            $conditions[] = "quantity < $threshold";
        }

        $sql = "SELECT * FROM items";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // Sorting support
        $allowedSorts = ['id', 'productname', 'manufacturer', 'size', 'color', 'quantity', 'grafted', 'club', 'expiration_year', 'last_change', 'image'];
        $sort = $filters['sort'] ?? 'id';
        $order = strtoupper($filters['order'] ?? 'DESC');

        if (!in_array($sort, $allowedSorts)) {
            $sort = 'id';
        }
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $sql .= " ORDER BY $sort $order";

        // Pagination
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
        }

        $stmt = $this->conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $items;
    }

    public function countAll(array $filters): int
    {
        $conditions = [];
        $params = [];
        $types = "";

        $threshold = $this->appConfig['low_stock_threshold'] ?? 10;

        if (!empty($filters['productname'])) {
            $conditions[] = "productname LIKE ?";
            $params[] = "%" . $filters['productname'] . "%";
            $types .= "s";
        }
        
        // ✅ FIXED: Add article_no filter
        if (!empty($filters['article_no'])) {
            $conditions[] = "article_no LIKE ?";
            $params[] = "%" . $filters['article_no'] . "%";
            $types .= "s";
        }
        
        if (!empty($filters['manufacturer'])) {
            $conditions[] = "manufacturer = ?";
            $params[] = $filters['manufacturer'];
            $types .= "s";
        }
        
        if (!empty($filters['color'])) {
            $conditions[] = "color LIKE ?";
            $params[] = "%" . $filters['color'] . "%";
            $types .= "s";
        }
        
        if (!empty($filters['size'])) {
            $conditions[] = "size LIKE ?";
            $params[] = "%" . $filters['size'] . "%";
            $types .= "s";
        }
        
        // ✅ FIXED: Add supplier filter
        if (!empty($filters['supplier'])) {
            $conditions[] = "supplier = ?";
            $params[] = $filters['supplier'];
            $types .= "s";
        }
        
        // ✅ FIXED: Add category filter
        if (!empty($filters['category'])) {
            $conditions[] = "category = ?";
            $params[] = $filters['category'];
            $types .= "s";
        }
        
        if ($filters['grafted'] !== '') {
            $conditions[] = "grafted = ?";
            $params[] = $filters['grafted'];
            $types .= "s";
        }
        
        if (!empty($filters['club'])) {
            $conditions[] = "club = ?";
            $params[] = $filters['club'];
            $types .= "s";
        }
        
        if (!empty($filters['lowstock'])) {
            $conditions[] = "quantity < $threshold";
        }

        $sql = "SELECT COUNT(*) as count FROM items";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)$row['count'];
    }



    /**
     * Retrieves unique manufacturers from the items table.
     *
     * @return array An array of associative arrays, each containing 'manufacturer'.
     */
    public function getUniqueManufacturers(): array {
        $sql = "SELECT DISTINCT manufacturer FROM items WHERE manufacturer IS NOT NULL AND manufacturer != '' ORDER BY manufacturer";
        $result = $this->conn->query($sql);

        if (!$result) {
            error_log("ERROR: MySQLi query failed in getUniqueManufacturers: " . $this->conn->error);
            return [];
        }

        $manufacturers = [];
        while ($row = $result->fetch_assoc()) {
            $manufacturers[] = $row;
        }
        $result->free();
        return $manufacturers;
    }

    /**
     * Retrieves unique clubs from the items table.
     *
     * @return array An array of associative arrays, each containing 'club'.
     */
    public function getUniqueClubs(): array {
        $sql = "SELECT DISTINCT club FROM items WHERE club IS NOT NULL AND club != '' ORDER BY club";
        $result = $this->conn->query($sql);

        if (!$result) {
            error_log("ERROR: MySQLi query failed in getUniqueClubs: " . $this->conn->error);
            return [];
        }

        $clubs = [];
        while ($row = $result->fetch_assoc()) {
            $clubs[] = $row;
        }
        $result->free();
        return $clubs;
    }


    

    /**
     * Finds an item by its ID.
     *
     * @param int $id The ID of the item to find.
     * @return array|null An associative array of item data if found, otherwise null.
     */
    public function find(int $id): ?array {
        $sql = "SELECT * FROM items WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("ERROR: MySQLi prepare failed in find: " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("i", $id); // 'i' for integer
        if (!$stmt->execute()) {
            error_log("ERROR: MySQLi execute failed in find: " . $stmt->error);
            $stmt->close();
            return null;
        }
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        $result->free();
        return $item ?: null;
    }

    /**
     * Creates a new item in the database.
     *
     * @param Item $item The Item object containing the data to insert.
     * @return bool True on success, false on failure.
     */
    

    // src/models/ItemRepository.php

    public function create(Item $item): bool {
        $sql = "INSERT INTO items 
        (productname, category, article_no, manufacturer, description, size, color, 
        color_number, unit_price, total_price, supplier, quantity, grafted, club, 
        expiration_year, expiry_date, img, mime_type, last_edited_by, last_change) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("ERROR: MySQLi prepare failed in create: " . $this->conn->error);
            return false;
        }

        $grafted = (int)$item->grafted;
        if ($item->grafted === 1) {
            $item->club = !empty(trim($_POST['club'] ?? '')) ? trim($_POST['club']) : null;
        } else {
            $item->club = null;
        }

        error_log("DEBUG store(): grafted = " . $item->grafted . ", club = " . ($item->club ?? 'NULL'));
        
        // Define the types string for the 19 parameters 
        $types = "ssssssssddsiisssbsss"; // 19 parameters (no last_change, no id)
        
        // FIX: Use an empty string '' as a placeholder for the BLOB data
        $image_placeholder = ($item->img !== null && $item->img !== '') ? '' : null;
        
        // Ensure all variables for bind_param are defined (using null coalescing for nullable columns)
        $category_value = $item->category ?? null;
        $article_no_value = $item->article_no ?? null;
        $color_number_value = $item->color_number ?? null;
        $unit_price_value = $item->unit_price ?? null;
        $total_price_value = $item->total_price ?? null;
        $supplier_value = $item->supplier ?? null;
        $expiry_date_value = $item->expiry_date ?? null;
        $mime_type_value = $item->mime_type ?? 'image/png';
        $last_change_value = $item->last_change ?? date('Y-m-d H:i:s'); 

        if (!$stmt->bind_param(
            $types, 
            $item->productname, $category_value, $article_no_value, $item->manufacturer, 
            $item->description, $item->size, $item->color, $color_number_value, 
            $unit_price_value, $total_price_value, $supplier_value, 
            $item->quantity, $grafted, $item->club, 
            $item->expiration_year, $expiry_date_value, 
            $image_placeholder, // <-- Placeholder for 'img'
            $mime_type_value, $item->last_edited_by, $last_change_value
        )) {
            error_log("ERROR: MySQLi bind_param failed in create: " . $stmt->error);
            $stmt->close();
            return false;
        }

        // The send_long_data call - EXACTLY like update method
        if ($item->img !== null && $item->img !== '') { 
            error_log("DEBUG: Creating with new image. Size: " . strlen($item->img) . " bytes");
            $stmt->send_long_data(16, $item->img); // Parameter index 16 (0-based)
        } else {
            error_log("DEBUG: No image data for create"); 
        }
        
        if ($stmt->execute()) {
            $item->id = $this->conn->insert_id;
            error_log("DEBUG: Create executed. New ID: " . $item->id);
            $stmt->close();
            return true;
        } else {
            error_log("ERROR: MySQLi execute failed in create: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    // src/models/ItemRepository.php

    public function update(Item $item): bool {
        $sql = "UPDATE items SET productname=?, category=?, article_no=?, manufacturer=?, description=?, size=?, color=?, color_number=?, unit_price=?, total_price=?, supplier=?, quantity=?, grafted=?, club=?, expiration_year=?, expiry_date=?, img=?, mime_type=?, last_change=?, last_edited_by=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("ERROR: MySQLi prepare failed in update: " . $this->conn->error);
            return false;
        }

        $grafted = (int)$item->grafted;
        $club_value = ($item->grafted === 1 && !empty($item->club)) ? $item->club : null;
        
        // Define the types string for the 21 parameters 
        $types = "ssssssssddsiisssbsssi"; // Example type string (20 columns + 1 integer for 'id')
        
        // FIX: Use an empty string '' as a placeholder for the BLOB data
        $image_placeholder = ($item->img !== null && $item->img !== '') ? '' : null;
        
        // Ensure all variables for bind_param are defined (using null coalescing for nullable columns)
        $category_value = $item->category ?? null;
        $article_no_value = $item->article_no ?? null;
        $color_number_value = $item->color_number ?? null;
        $unit_price_value = $item->unit_price ?? null;
        $total_price_value = $item->total_price ?? null;
        $supplier_value = $item->supplier ?? null;
        $expiry_date_value = $item->expiry_date ?? null;
        $mime_type_value = $item->mime_type ?? 'image/png';

        if (!$stmt->bind_param(
            $types, 
            $item->productname, $category_value, $article_no_value, $item->manufacturer, 
            $item->description, $item->size, $item->color, $color_number_value, 
            $unit_price_value, $total_price_value, $supplier_value, 
            $item->quantity, $grafted, $club_value, 
            $item->expiration_year, $expiry_date_value, 
            $image_placeholder, // <-- CORRECTED: Placeholder for 'img'
            $mime_type_value, $item->last_change, $item->last_edited_by,
            $item->id // WHERE clause parameter
        )) {
            error_log("ERROR: MySQLi bind_param failed in update: " . $stmt->error);
            $stmt->close();
            return false;
        }

        // The send_long_data call (already in your code)
        if ($item->img !== null && $item->img !== '') { 
            error_log("DEBUG: Updating with new image. Size: " . strlen($item->img) . " bytes");
            $stmt->send_long_data(16, $item->img); // Parameter index 16 (0-based)
        } else {
            error_log("DEBUG: No new image data for update (keeping existing image)"); 
        }
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            error_log("DEBUG: Update executed. Affected rows: " . $affected_rows);
            $stmt->close();
            return $affected_rows >= 0;
        } else {
            error_log("ERROR: MySQLi execute failed in update: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    
    

    /**
     * Deletes an item from the database by its ID.
     *
     * @param int $id The ID of the item to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool {
        $sql = "DELETE FROM items WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("ERROR: MySQLi prepare failed in delete: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            return $affected_rows > 0; // Return true if at least one row was affected
        } else {
            error_log("ERROR: MySQLi execute failed in delete: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    // Add these methods to the ItemRepository class

    /**
     * Save a filter for a user
     */
    public function saveFilter(int $userId, string $name, array $filters): bool
    {
        $query = "INSERT INTO saved_filters (user_id, name, filters) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            error_log("DEBUG: Failed to prepare saveFilter statement: " . $this->conn->error);
            return false;
        }
        
        $jsonFilters = json_encode($filters);
        $stmt->bind_param("iss", $userId, $name, $jsonFilters);
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("DEBUG: Failed to execute saveFilter: " . $stmt->error);
        }
        
        $stmt->close();
        return $result;
    }

    /**
     * Get saved filters for a user
     */
    public function getSavedFilters(int $userId): array
    {
        $query = "SELECT id, name, filters, created_at, updated_at 
                FROM saved_filters 
                WHERE user_id = ? 
                ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            error_log("DEBUG: Failed to prepare getSavedFilters statement: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $filters = [];
        while ($row = $result->fetch_assoc()) {
            $row['filters'] = json_decode($row['filters'], true);
            $filters[] = $row;
        }
        
        $stmt->close();
        return $filters;
    }

    /**
     * Get a specific saved filter
     */
    public function getSavedFilter(int $filterId, int $userId): ?array
    {
        $query = "SELECT id, name, filters 
                FROM saved_filters 
                WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            error_log("DEBUG: Failed to prepare getSavedFilter statement: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("ii", $filterId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row) {
            $row['filters'] = json_decode($row['filters'], true);
        }
        
        $stmt->close();
        return $row;
    }

    /**
     * Delete a saved filter
     */
    public function deleteSavedFilter(int $filterId, int $userId): bool
    {
        $query = "DELETE FROM saved_filters WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            error_log("DEBUG: Failed to prepare deleteSavedFilter statement: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("ii", $filterId, $userId);
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("DEBUG: Failed to execute deleteSavedFilter: " . $stmt->error);
        }
        
        $stmt->close();
        return $result;
    }

    // Add these methods to your ItemRepository class (src/models/ItemRepository.php)

    /**
     * Find items by article number
     *
     * @param string $articleNo The article number to search for
     * @return array An array of items matching the article number
     */
    public function findByArticleNumber(string $articleNo): array {
        $sql = "SELECT id, productname, article_no, manufacturer, description, size, color, 
                color_number, category, quantity, unit_price, total_price, supplier, 
                grafted, club, expiration_year, expiry_date, last_change, last_edited_by 
                FROM items 
                WHERE article_no = ? 
                ORDER BY productname ASC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("ERROR: MySQLi prepare failed in findByArticleNumber: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("s", $articleNo);
        
        if (!$stmt->execute()) {
            error_log("ERROR: MySQLi execute failed in findByArticleNumber: " . $stmt->error);
            $stmt->close();
            return [];
        }
        
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $result->free();
        
        return $items;
    }

    /**
     * Update only the quantity of an item
     *
     * @param int $itemId The ID of the item
     * @param int $quantity The new quantity
     * @param string $editedBy The username of the person making the change
     * @return bool True on success, false on failure
     */
    public function updateQuantity(int $itemId, int $quantity, string $editedBy): bool {
        $sql = "UPDATE items 
                SET quantity = ?, 
                    last_change = NOW(), 
                    last_edited_by = ? 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("ERROR: MySQLi prepare failed in updateQuantity: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("isi", $quantity, $editedBy, $itemId);
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            error_log("DEBUG: updateQuantity executed. Affected rows: " . $affected_rows);
            $stmt->close();
            return $affected_rows > 0;
        } else {
            error_log("ERROR: MySQLi execute failed in updateQuantity: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }




    // private function loadSavedFilters(): array
    // {
    //     $userId = $_SESSION['user_id'] ?? null;
    //     if (!$userId) {
    //         return [];
    //     }
        
    //     return $this->itemRepository->getSavedFilters($userId);
    // }

}
?>