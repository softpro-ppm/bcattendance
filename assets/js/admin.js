// BC Attendance Admin Dashboard JavaScript

console.log('Admin.js loaded successfully');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing components...');
    
    // Initialize all components
    initializeDropdowns();
    initializeConfirmations();
    initializeFormValidation();
    initializeDataTables();
    initializeTooltips();
    initializeSidebar();
    initializeTreeview();
    initializeMobileFeatures();
    
    console.log('All components initialized');
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (alert.classList.contains('alert-success')) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }
        });
    }, 5000);
});

// Dropdown functionality
function initializeDropdowns() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const menu = this.nextElementSibling;
            const isOpen = menu.classList.contains('show');
            
            // Close all dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(function(m) {
                m.classList.remove('show');
            });
            
            // Toggle current dropdown
            if (!isOpen) {
                menu.classList.add('show');
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
            menu.classList.remove('show');
        });
    });
}

// Confirmation dialogs
function initializeConfirmations() {
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    // Clear previous errors
    form.querySelectorAll('.error-message').forEach(function(error) {
        error.remove();
    });
    
    form.querySelectorAll('.form-control').forEach(function(field) {
        field.classList.remove('is-invalid');
    });
    
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            isValid = false;
            showFieldError(field, 'This field is required');
        } else {
            // Additional validations
            if (field.type === 'email' && !isValidEmail(field.value)) {
                isValid = false;
                showFieldError(field, 'Please enter a valid email address');
            }
            
            if (field.type === 'tel' && !isValidPhone(field.value)) {
                isValid = false;
                showFieldError(field, 'Please enter a valid phone number');
            }
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message text-danger mt-1';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[0-9]{10}$/;
    return phoneRegex.test(phone.replace(/\D/g, ''));
}

// Data tables functionality
function initializeDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(function(table) {
        // Add search functionality
        addTableSearch(table);
        
        // Add sorting functionality
        addTableSorting(table);
    });
}

function addTableSearch(table) {
    const searchInput = table.parentNode.querySelector('.table-search');
    if (!searchInput) return;
    
    searchInput.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

function addTableSorting(table) {
    const headers = table.querySelectorAll('th[data-sort]');
    
    headers.forEach(function(header, index) {
        header.style.cursor = 'pointer';
        header.innerHTML += ' <span class="sort-indicator">↕</span>';
        
        header.addEventListener('click', function() {
            sortTable(table, index, this);
        });
    });
}

function sortTable(table, columnIndex, header) {
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const isAscending = header.classList.contains('sort-asc');
    
    // Clear all sort classes
    table.querySelectorAll('th').forEach(function(h) {
        h.classList.remove('sort-asc', 'sort-desc');
        const indicator = h.querySelector('.sort-indicator');
        if (indicator) indicator.textContent = '↕';
    });
    
    // Sort rows
    rows.sort(function(a, b) {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        
        let comparison = 0;
        if (aText > bText) comparison = 1;
        if (aText < bText) comparison = -1;
        
        return isAscending ? -comparison : comparison;
    });
    
    // Update header class and indicator
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
    const indicator = header.querySelector('.sort-indicator');
    if (indicator) indicator.textContent = isAscending ? '↓' : '↑';
    
    // Reorder rows
    const tbody = table.querySelector('tbody');
    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
}

// Tooltips
function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(function(element) {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const text = this.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip-custom';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        background: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 1000;
        pointer-events: none;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = this.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
    
    this.tooltipElement = tooltip;
}

function hideTooltip() {
    if (this.tooltipElement) {
        document.body.removeChild(this.tooltipElement);
        this.tooltipElement = null;
    }
}

// Enhanced sidebar functionality for mobile
function initializeSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const body = document.body;
    const sidebar = document.querySelector('.main-sidebar');
    
    console.log('Initializing sidebar...', { 
        sidebarToggle: sidebarToggle, 
        body: body, 
        sidebar: sidebar,
        windowWidth: window.innerWidth,
        isMobile: window.innerWidth <= 768
    });
    
    if (sidebarToggle) {
        console.log('Sidebar toggle found, adding event listener');
        console.log('Toggle button styles:', window.getComputedStyle(sidebarToggle));
        
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Sidebar toggle clicked');
            body.classList.toggle('sidebar-open');
            
            // Add ARIA attributes for accessibility
            const isOpen = body.classList.contains('sidebar-open');
            sidebarToggle.setAttribute('aria-expanded', isOpen);
            if (sidebar) {
                sidebar.setAttribute('aria-hidden', !isOpen);
            }
            console.log('Sidebar state:', isOpen ? 'open' : 'closed');
        });
        
        // Ensure the toggle button is visible
        sidebarToggle.style.display = 'flex';
        sidebarToggle.style.visibility = 'visible';
        sidebarToggle.style.opacity = '1';
        
    } else {
        console.error('Sidebar toggle button not found!');
        console.log('Available elements with sidebar-toggle class:', document.querySelectorAll('.sidebar-toggle'));
        console.log('Available elements with sidebar-toggle in main-header:', document.querySelectorAll('.main-header .sidebar-toggle'));
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!e.target.closest('.main-sidebar') && !e.target.closest('.sidebar-toggle')) {
                body.classList.remove('sidebar-open');
                if (sidebarToggle) {
                    sidebarToggle.setAttribute('aria-expanded', 'false');
                }
                if (sidebar) {
                    sidebar.setAttribute('aria-hidden', 'true');
                }
            }
        }
    });
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && body.classList.contains('sidebar-open')) {
            body.classList.remove('sidebar-open');
            if (sidebarToggle) {
                sidebarToggle.setAttribute('aria-expanded', 'false');
                sidebarToggle.focus(); // Return focus to toggle button
            }
            if (sidebar) {
                sidebar.setAttribute('aria-hidden', 'true');
            }
        }
    });
    
    // Handle screen resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            body.classList.remove('sidebar-open');
            if (sidebarToggle) {
                sidebarToggle.setAttribute('aria-expanded', 'false');
            }
            if (sidebar) {
                sidebar.setAttribute('aria-hidden', 'false');
            }
        }
    });
    
    // Log mobile detection
    if (window.innerWidth <= 768) {
        console.log('Mobile device detected, sidebar should be hidden by default');
        if (sidebar) {
            sidebar.setAttribute('aria-hidden', 'true');
        }
        // Force show the toggle button on mobile
        if (sidebarToggle) {
            sidebarToggle.style.display = 'flex !important';
            sidebarToggle.style.visibility = 'visible !important';
            sidebarToggle.style.opacity = '1 !important';
        }
    }
    
    // Additional check for toggle button visibility
    setTimeout(() => {
        if (sidebarToggle) {
            const computedStyle = window.getComputedStyle(sidebarToggle);
            console.log('Toggle button computed styles:', {
                display: computedStyle.display,
                visibility: computedStyle.visibility,
                opacity: computedStyle.opacity,
                position: computedStyle.position,
                zIndex: computedStyle.zIndex
            });
        }
    }, 100);
}

// AJAX functions
function sendAjaxRequest(url, data, method = 'POST') {
    return new Promise(function(resolve, reject) {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (e) {
                        resolve(xhr.responseText);
                    }
                } else {
                    reject(new Error('Request failed'));
                }
            }
        };
        
        if (method === 'POST') {
            const formData = new URLSearchParams(data).toString();
            xhr.send(formData);
        } else {
            xhr.send();
        }
    });
}

// Loading overlay
function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'loading';
    loading.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.querySelector('.loading');
    if (loading) {
        document.body.removeChild(loading);
    }
}

// Alert functions
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    const contentHeader = document.querySelector('.content-header');
    if (contentHeader) {
        contentHeader.appendChild(alertDiv);
    } else {
        document.querySelector('.content').insertBefore(alertDiv, document.querySelector('.content').firstChild);
    }
    
    // Auto remove after 5 seconds
    setTimeout(function() {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Export functions
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(function(row) {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => {
            return '"' + cell.textContent.replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(content, filename) {
    const blob = new Blob([content], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Dynamic form functions
function addFormRow(containerId, templateId) {
    const container = document.getElementById(containerId);
    const template = document.getElementById(templateId);
    
    if (container && template) {
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
    }
}

function removeFormRow(button) {
    const row = button.closest('.form-row');
    if (row) {
        row.remove();
    }
}

// Treeview functionality
function initializeTreeview() {
    const treeviewToggles = document.querySelectorAll('.nav-item.has-treeview > .nav-link');
    
    treeviewToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const parentItem = this.parentElement;
            const isOpen = parentItem.classList.contains('menu-open');
            
            // Close all other treeview menus
            document.querySelectorAll('.nav-item.has-treeview').forEach(function(item) {
                if (item !== parentItem) {
                    item.classList.remove('menu-open');
                }
            });
            
            // Toggle current menu
            if (isOpen) {
                parentItem.classList.remove('menu-open');
            } else {
                parentItem.classList.add('menu-open');
            }
        });
    });
}

// Mobile-specific features
function initializeMobileFeatures() {
    // Handle mobile table scrolling
    const tables = document.querySelectorAll('.table-responsive');
    tables.forEach(function(tableContainer) {
        const table = tableContainer.querySelector('table');
        if (table) {
            // Add touch scrolling indicator
            if (window.innerWidth <= 768) {
                addScrollIndicator(tableContainer);
            }
        }
    });
    
    // Handle mobile form improvements
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        // Prevent zoom on iOS when focusing input
        const inputs = form.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="number"], select, textarea');
        inputs.forEach(function(input) {
            if (input.style.fontSize === '' || parseFloat(input.style.fontSize) < 16) {
                input.style.fontSize = '16px';
            }
        });
    });
    
    // Add pull-to-refresh functionality (basic)
    if (window.innerWidth <= 768) {
        addPullToRefresh();
    }
    
    // Handle orientation change
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            // Force recalculation of viewport height
            document.body.style.height = window.innerHeight + 'px';
            setTimeout(function() {
                document.body.style.height = '';
            }, 500);
        }, 100);
    });
    
    // Handle modal improvements on mobile
    const modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        modal.addEventListener('show.bs.modal', function() {
            if (window.innerWidth <= 768) {
                document.body.style.overflow = 'hidden';
            }
        });
        
        modal.addEventListener('hide.bs.modal', function() {
            document.body.style.overflow = '';
        });
    });
}

function addScrollIndicator(tableContainer) {
    const table = tableContainer.querySelector('table');
    if (!table) return;
    
    const indicator = document.createElement('div');
    indicator.className = 'scroll-indicator';
    indicator.innerHTML = '<i class="fas fa-arrow-right"></i> Scroll to see more';
    indicator.style.cssText = `
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        z-index: 10;
        pointer-events: none;
        transition: opacity 0.3s;
    `;
    
    tableContainer.style.position = 'relative';
    tableContainer.appendChild(indicator);
    
    // Hide indicator after scrolling starts
    tableContainer.addEventListener('scroll', function() {
        if (this.scrollLeft > 20) {
            indicator.style.opacity = '0';
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.parentNode.removeChild(indicator);
                }
            }, 300);
        }
    });
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        if (indicator.parentNode) {
            indicator.style.opacity = '0';
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.parentNode.removeChild(indicator);
                }
            }, 300);
        }
    }, 3000);
}

function addPullToRefresh() {
    let startY = 0;
    let currentY = 0;
    let pullDistance = 0;
    let isPulling = false;
    
    const refreshThreshold = 80;
    const content = document.querySelector('.content-wrapper');
    
    if (!content) return;
    
    content.addEventListener('touchstart', function(e) {
        if (window.pageYOffset === 0) {
            startY = e.touches[0].pageY;
            isPulling = true;
        }
    });
    
    content.addEventListener('touchmove', function(e) {
        if (!isPulling) return;
        
        currentY = e.touches[0].pageY;
        pullDistance = currentY - startY;
        
        if (pullDistance > 0 && window.pageYOffset === 0) {
            e.preventDefault();
            
            if (pullDistance > refreshThreshold) {
                // Show refresh indicator
                if (!document.querySelector('.pull-refresh-indicator')) {
                    const indicator = document.createElement('div');
                    indicator.className = 'pull-refresh-indicator';
                    indicator.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Release to refresh';
                    indicator.style.cssText = `
                        position: fixed;
                        top: 10px;
                        left: 50%;
                        transform: translateX(-50%);
                        background: #28a745;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 20px;
                        font-size: 14px;
                        z-index: 9999;
                        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                    `;
                    document.body.appendChild(indicator);
                }
            }
        }
    });
    
    content.addEventListener('touchend', function() {
        if (isPulling && pullDistance > refreshThreshold) {
            // Trigger refresh
            window.location.reload();
        }
        
        isPulling = false;
        pullDistance = 0;
        
        // Remove indicator
        const indicator = document.querySelector('.pull-refresh-indicator');
        if (indicator) {
            indicator.parentNode.removeChild(indicator);
        }
    });
}
