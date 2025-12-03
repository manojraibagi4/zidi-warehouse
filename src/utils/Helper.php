<?php
namespace Utils;

class Helper {
    /**
     * Render a sortable table header link.
     *
     * @param string $field Column name for sorting
     * @param string $label Display label
     * @param array $filters Current filters containing 'sort' and 'order'
     * @return string HTML link for table header
     */
    public static function renderSortHeader(string $field, string $label, array $filters): string {
        $currentSort = $filters['sort'] ?? '';
        $currentOrder = $filters['order'] ?? 'asc';

        // Toggle sort order
        $newOrder = ($currentSort === $field && $currentOrder === 'asc') ? 'desc' : 'asc';

        // Merge query parameters
        $query = array_merge($_GET, [
            'sort' => $field,
            'order' => $newOrder
        ]);

        // Add arrow icon if currently sorted
        $icon = '';
        if ($currentSort === $field) {
            $icon = ' <i class="bi bi-arrow-' . ($currentOrder === 'asc' ? 'up' : 'down') . ' ms-1"></i>';
        }

        $url = '?' . http_build_query($query);

        return '<a href="' . htmlspecialchars($url) . '" class="text-white text-decoration-none">'
             . htmlspecialchars($label) . $icon
             . '</a>';
    }
}
