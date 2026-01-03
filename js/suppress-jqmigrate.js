/**
 * Suppress jQuery Migrate non-critical warnings
 * This script must load BEFORE jQuery Migrate to be effective
 */
(function() {
    // Check if jQuery.migrateVersion exists (jQuery Migrate is loaded)
    if (window.jQuery && window.jQuery.migrateVersion) {
        // Disable jQuery Migrate logging
        window.jQuery.migrateMute = true;
    }
})();
