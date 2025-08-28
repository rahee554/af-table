/**
 * AF Table Package - JavaScript Enhancements
 * Provides interactive functionality and UX improvements
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize AF Table functionality
    initAFTable();
});

function initAFTable() {
    // Keep column visibility dropdown open after Livewire updates
    setupColumnVisibilityDropdown();
    
    // Add loading states for better UX
    setupLoadingStates();
    
    // Enhance table interactions
    setupTableInteractions();
    
    // Add keyboard shortcuts
    setupKeyboardShortcuts();
    
    // Setup responsive table features
    setupResponsiveFeatures();
}

/**
 * Keep column visibility dropdown open after toggling columns
 */
function setupColumnVisibilityDropdown() {
    // Listen for Livewire updates
    document.addEventListener('livewire:updated', function() {
        const dropdown = document.getElementById('columnVisibilityDropdown');
        const dropdownMenu = dropdown?.nextElementSibling;
        
        if (window._keepColumnDropdownOpen && dropdown && dropdownMenu) {
            // Bootstrap 5: manually show dropdown
            dropdownMenu.classList.add('show');
            dropdown.setAttribute('aria-expanded', 'true');
            window._keepColumnDropdownOpen = false;
        }
    });

    // Intercept column visibility checkbox clicks
    const dropdownWrapper = document.getElementById('columnVisibilityDropdownWrapper');
    if (dropdownWrapper) {
        dropdownWrapper.addEventListener('click', function(e) {
            if (e.target && e.target.matches('.form-check-input')) {
                window._keepColumnDropdownOpen = true;
                
                // Add visual feedback
                e.target.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    e.target.style.transform = '';
                }, 150);
            }
        });
    }
}

/**
 * Add loading states for better user experience
 */
function setupLoadingStates() {
    // Show loading indicator during Livewire requests
    document.addEventListener('livewire:loading.start', function() {
        showTableLoading();
    });

    document.addEventListener('livewire:loading.stop', function() {
        hideTableLoading();
        animateTableUpdate();
    });
}

function showTableLoading() {
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer && !tableContainer.querySelector('.loading-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner"></div>
            <span class="sr-only">Loading...</span>
        `;
        tableContainer.style.position = 'relative';
        tableContainer.appendChild(overlay);
    }
}

function hideTableLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function animateTableUpdate() {
    const tbody = document.querySelector('#myTable tbody');
    if (tbody) {
        tbody.classList.remove('table-fade');
        setTimeout(() => {
            tbody.classList.add('table-fade');
        }, 10);
    }
}

/**
 * Enhance table interactions
 */
function setupTableInteractions() {
    // Add hover effects for sortable columns
    const sortableHeaders = document.querySelectorAll('#myTable th[style*="cursor:pointer"]');
    sortableHeaders.forEach(header => {
        header.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#e9ecef';
        });
        
        header.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Smooth scroll to top after page changes
    document.addEventListener('livewire:updated', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('page')) {
            const tableTop = document.querySelector('#myTable')?.offsetTop;
            if (tableTop) {
                window.scrollTo({
                    top: tableTop - 100,
                    behavior: 'smooth'
                });
            }
        }
    });

    // Auto-resize search input based on content
    const searchInput = document.querySelector('input[wire\\:model\\.lazy="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const length = this.value.length;
            const minWidth = 200;
            const maxWidth = 400;
            const newWidth = Math.min(maxWidth, Math.max(minWidth, length * 8 + 100));
            this.style.width = newWidth + 'px';
        });
    }
}

/**
 * Keyboard shortcuts for power users
 */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K: Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[wire\\:model\\.lazy="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        // Escape: Clear search
        if (e.key === 'Escape') {
            const searchInput = document.querySelector('input[wire\\:model\\.lazy="search"]');
            if (searchInput && document.activeElement === searchInput) {
                if (searchInput.value) {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input'));
                } else {
                    searchInput.blur();
                }
            }
        }

        // Ctrl/Cmd + P: Print
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            const printButton = document.querySelector('button[onclick="window.print()"]');
            if (printButton) {
                e.preventDefault();
                window.print();
            }
        }
    });

    // Add keyboard navigation for dropdowns
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('keydown', function(e) {
            const items = this.querySelectorAll('.form-check-input, .dropdown-item');
            const currentIndex = Array.from(items).indexOf(document.activeElement);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = (currentIndex + 1) % items.length;
                items[nextIndex]?.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                items[prevIndex]?.focus();
            }
        });
    });
}

/**
 * Setup responsive table features
 */
function setupResponsiveFeatures() {
    // Handle mobile table scrolling
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        let isScrolling = false;

        tableContainer.addEventListener('scroll', function() {
            if (!isScrolling) {
                isScrolling = true;
                this.style.boxShadow = 'inset 0 0 10px rgba(0,0,0,0.1)';
                
                setTimeout(() => {
                    isScrolling = false;
                    this.style.boxShadow = '';
                }, 150);
            }
        });
    }

    // Responsive column hiding on small screens
    function handleResponsiveColumns() {
        const table = document.querySelector('#myTable');
        if (!table) return;

        const screenWidth = window.innerWidth;
        const columns = table.querySelectorAll('th, td');

        // Show/hide columns based on screen size
        if (screenWidth < 768) {
            columns.forEach((col, index) => {
                // Hide columns beyond the first 3 on mobile
                if (index > 2) {
                    col.style.display = 'none';
                }
            });
        } else {
            columns.forEach(col => {
                col.style.display = '';
            });
        }
    }

    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleResponsiveColumns, 250);
    });

    // Initial call
    handleResponsiveColumns();
}

/**
 * Utility functions
 */

// Show toast notifications
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.minWidth = '300px';
    
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

// Copy table data to clipboard
function copyTableData() {
    const table = document.querySelector('#myTable');
    if (!table) return;

    let tableData = '';
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => cell.textContent.trim()).join('\t');
        tableData += rowData + '\n';
    });

    navigator.clipboard.writeText(tableData).then(() => {
        showToast('Table data copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Failed to copy table data.', 'error');
    });
}

// Export functionality enhancement
function enhanceExportButtons() {
    const exportButtons = document.querySelectorAll('[wire\\:click^="export"]');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.textContent;
            this.textContent = 'Exporting...';
            this.disabled = true;
            
            setTimeout(() => {
                this.textContent = originalText;
                this.disabled = false;
            }, 3000);
        });
    });
}

// Initialize export enhancements when DOM is ready
document.addEventListener('livewire:updated', function() {
    enhanceExportButtons();
});

// Global utility object
window.AFTable = {
    showToast,
    copyTableData,
    showTableLoading,
    hideTableLoading,
    animateTableUpdate
};

// Add CSS for better print styling
function addPrintStyles() {
    const style = document.createElement('style');
    style.textContent = `
        @media print {
            @page {
                margin: 0.5in;
                size: landscape;
            }
            
            body * {
                visibility: hidden;
            }
            
            .table-responsive,
            .table-responsive * {
                visibility: visible;
            }
            
            .table-responsive {
                position: absolute;
                left: 0;
                top: 0;
                width: 100% !important;
                overflow: visible !important;
            }
            
            #myTable {
                font-size: 10px !important;
                page-break-inside: auto;
            }
            
            #myTable tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            #myTable thead {
                display: table-header-group;
            }
            
            #myTable tbody {
                display: table-row-group;
            }
        }
    `;
    document.head.appendChild(style);
}

// Initialize print styles
addPrintStyles();