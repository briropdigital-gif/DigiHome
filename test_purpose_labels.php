<?php
require 'includes/db.config.php';

$purposes = ['sale', 'rent', 'lease', 'hire_purchase', 'airbnb', 'hotel_booking', 'auction'];
foreach ($purposes as $p) {
    $label = listing_purpose_label(['purpose' => $p]);
    echo $p . ' => ' . $label . PHP_EOL;
}
echo 'scope unit => ' . listing_scope_label(['listing_scope' => 'unit']) . PHP_EOL;
echo 'scope entire => ' . listing_scope_label(['listing_scope' => 'entire_property']) . PHP_EOL;
echo 'scope empty => ' . listing_scope_label(['listing_scope' => '']) . PHP_EOL;

// Test get_favorite_count
echo 'favorite_count_test: ' . get_favorite_count(1) . PHP_EOL;
