<?php
// Debug script to test category terms

// Laden Sie WordPress
require_once( '/home/dern3rd/Local Sites/ps-dev/app/public/wp-load.php' );

// Check if taxonomies exist
$taxonomies = get_taxonomies(array('object_type' => 'clienti'));
echo "Taxonomies for 'clienti' post type:\n";
print_r($taxonomies);
echo "\n";

// Get all terms from each taxonomy
$taxonomies_to_check = array('WPsCRM_customersCat', 'WPsCRM_customersInt', 'WPsCRM_customersProv');
foreach ($taxonomies_to_check as $tax) {
    $terms = get_terms(array(
        'taxonomy' => $tax,
        'hide_empty' => false,
    ));
    
    echo "\n=== Terms in $tax ===\n";
    if (is_wp_error($terms)) {
        echo "ERROR: " . $terms->get_error_message() . "\n";
    } else {
        echo "Count: " . count($terms) . "\n";
        foreach ($terms as $term) {
            echo "  ID: {$term->term_id}, Name: {$term->name}, Slug: {$term->slug}\n";
        }
    }
}

// Now check the customer table
global $wpdb;
$table = $wpdb->prefix . 'smartcrm_kunde';
echo "\n\n=== Customers in database ===\n";
$customers = $wpdb->get_results("SELECT ID_kunde, name, nachname, categoria, provenienza, interessi FROM $table LIMIT 5");
echo "Count: " . count($customers) . "\n";
foreach ($customers as $customer) {
    echo "\nCustomer ID: {$customer->ID_kunde}, Name: {$customer->name} {$customer->nachname}\n";
    echo "  categoria: {$customer->categoria}\n";
    echo "  provenienza: {$customer->provenienza}\n";
    echo "  interessi: {$customer->interessi}\n";
}

// Test: Get terms by ID
echo "\n\n=== Testing get_term_by ID ===\n";
$term_test = get_term_by('id', 12, 'WPsCRM_customersCat');
if ($term_test) {
    echo "Term 12 found: {$term_test->name}\n";
} else {
    echo "Term 12 NOT found!\n";
}
?>
