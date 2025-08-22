/**
 * Reusable Search and Pagination JavaScript
 * for v2bc_attendance admin pages
 */

// Global search timeout variable
let searchTimeout;

/**
 * Initialize live search functionality
 * @param {string} searchInputId - ID of the search input field (default: 'liveSearch')
 * @param {number} debounceDelay - Delay in milliseconds before search executes (default: 500)
 */
function initializeLiveSearch(searchInputId = 'liveSearch', debounceDelay = 500) {
    const searchInput = document.getElementById(searchInputId);
    
    if (searchInput) {
        // Live search with debouncing
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performSearch();
            }, debounceDelay);
        });
        
        // Handle Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    }
}

/**
 * Perform search by updating URL parameters
 * @param {string} searchInputId - ID of the search input field (default: 'liveSearch')
 */
function performSearch(searchInputId = 'liveSearch') {
    const searchInput = document.getElementById(searchInputId);
    if (!searchInput) return;
    
    const searchTerm = searchInput.value.trim();
    const currentUrl = new URL(window.location);
    
    if (searchTerm) {
        currentUrl.searchParams.set('search', searchTerm);
    } else {
        currentUrl.searchParams.delete('search');
    }
    currentUrl.searchParams.delete('page'); // Reset to first page when searching
    
    window.location.href = currentUrl.toString();
}

/**
 * Clear search and reset to original view
 * @param {string} searchInputId - ID of the search input field (default: 'liveSearch')
 */
function clearSearch(searchInputId = 'liveSearch') {
    const searchInput = document.getElementById(searchInputId);
    if (searchInput) {
        searchInput.value = '';
    }
    
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.delete('search');
    currentUrl.searchParams.delete('page');
    window.location.href = currentUrl.toString();
}

/**
 * Auto-hide alert messages
 * @param {number} delay - Delay in milliseconds before hiding (default: 5000)
 */
function autoHideAlerts(delay = 5000) {
    $(document).ready(function() {
        $('.alert').delay(delay).fadeOut('slow');
    });
}

/**
 * Reset modal forms when closed
 * @param {string[]} modalIds - Array of modal IDs to reset
 */
function initializeModalReset(modalIds = []) {
    modalIds.forEach(function(modalId) {
        $('#' + modalId).on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
        });
    });
}

/**
 * Initialize all common functionality
 * @param {Object} options - Configuration options
 */
function initializeCommonFeatures(options = {}) {
    const config = {
        searchInputId: options.searchInputId || 'liveSearch',
        debounceDelay: options.debounceDelay || 500,
        alertHideDelay: options.alertHideDelay || 5000,
        modalIds: options.modalIds || []
    };
    
    // Initialize live search
    initializeLiveSearch(config.searchInputId, config.debounceDelay);
    
    // Auto-hide alerts
    autoHideAlerts(config.alertHideDelay);
    
    // Initialize modal resets
    if (config.modalIds.length > 0) {
        initializeModalReset(config.modalIds);
    }
}

// Make functions globally available
window.initializeLiveSearch = initializeLiveSearch;
window.performSearch = performSearch;
window.clearSearch = clearSearch;
window.autoHideAlerts = autoHideAlerts;
window.initializeModalReset = initializeModalReset;
window.initializeCommonFeatures = initializeCommonFeatures;
