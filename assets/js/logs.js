// Logs-specific functionality
let logModal;
let currentEditingLog = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeLogsPage();
});

function initializeLogsPage() {
    // Initialize logs-specific event listeners
    setupLogsEventListeners();
}

function setupLogsEventListeners() {
    // Real-time search with debouncing
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleLogsSearch, 300));
    }
    
    // Filter change handlers
    const filterElements = [
        'filter-status',
        'filter-plugin', 
        'filter-category'
    ];
    
    filterElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', handleLogsFilterChange);
        }
    });
    
    // View switcher handlers
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', handleViewSwitch);
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', handleLogsKeyboardShortcuts);
    
    // Table sorting
    setupTableSorting();
}

function handleLogsSearch(event) {
    const searchTerm = event.target.value.toLowerCase().trim();
    currentFilters.search = searchTerm;
    
    // Show loading indicator
    showLogsLoading();
    
    // Reload logs with new search term
    loadLogs();
}

function handleLogsFilterChange(event) {
    const filterType = event.target.id.replace('filter-', '');
    currentFilters[filterType] = event.target.value;
    
    // Show loading indicator
    showLogsLoading();
    
    // Reload logs with new filter
    loadLogs();
}

function handleViewSwitch(event) {
    const newView = event.currentTarget.dataset.view;
    
    // Update active button
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    // Switch view
    switchView(newView);
}

function handleLogsKeyboardShortcuts(event) {
    // Ctrl/Cmd + N for new log
    if ((event.ctrlKey || event.metaKey) && event.key === 'n') {
        event.preventDefault();
        openAddLogModal();
    }
    
    // Escape to close modal
    if (event.key === 'Escape' && window.logModal && window.logModal.isOpen) {
        window.logModal.close();
    }
    
    // Ctrl/Cmd + F for search focus
    if ((event.ctrlKey || event.metaKey) && event.key === 'f') {
        event.preventDefault();
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
}

function setupTableSorting() {
    const table = document.querySelector('.logs-table');
    if (!table) return;
    
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        // Skip action column
        if (index === headers.length - 1) return;
        
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => sortTable(index));
        
        // Add sort indicator
        const sortIcon = document.createElement('span');
        sortIcon.className = 'material-icons sort-icon';
        sortIcon.textContent = 'sort';
        sortIcon.style.fontSize = '16px';
        sortIcon.style.marginLeft = '4px';
        sortIcon.style.opacity = '0.5';
        header.appendChild(sortIcon);
    });
}

let currentSort = { column: -1, direction: 'asc' };

function sortTable(columnIndex) {
    const table = document.querySelector('.logs-table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Determine sort direction
    if (currentSort.column === columnIndex) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.direction = 'asc';
    }
    currentSort.column = columnIndex;
    
    // Update sort icons
    table.querySelectorAll('.sort-icon').forEach((icon, index) => {
        if (index === columnIndex) {
            icon.textContent = currentSort.direction === 'asc' ? 'keyboard_arrow_up' : 'keyboard_arrow_down';
            icon.style.opacity = '1';
        } else {
            icon.textContent = 'sort';
            icon.style.opacity = '0.5';
        }
    });
    
    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        let comparison = 0;
        
        // Handle different data types
        if (columnIndex === 0) { // Date column
            comparison = new Date(aValue) - new Date(bValue);
        } else if (columnIndex === 6) { // Time spent column
            const aNum = parseInt(aValue);
            const bNum = parseInt(bValue);
            comparison = aNum - bNum;
        } else {
            comparison = aValue.localeCompare(bValue);
        }
        
        return currentSort.direction === 'asc' ? comparison : -comparison;
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

function showLogsLoading() {
    const tableBody = document.getElementById('logs-table-body');
    const kanbanContainers = ['kanban-open', 'kanban-resolved', 'kanban-escalated'];
    const groupedContent = document.getElementById('grouped-content');
    
    if (tableBody) {
        tableBody.innerHTML = '<tr><td colspan="8" class="loading-state">Loading...</td></tr>';
    }
    
    kanbanContainers.forEach(id => {
        const container = document.getElementById(id);
        if (container) {
            container.innerHTML = '<div class="loading-state">Loading...</div>';
        }
    });
    
    if (groupedContent) {
        groupedContent.innerHTML = '<div class="loading-state">Loading...</div>';
    }
}

function validateLogForm() {
    const form = document.getElementById('log-form');
    const errors = [];
    
    // Required fields validation
    const requiredFields = [
        { id: 'plugin-select', name: 'Plugin Name' },
        { id: 'issue-type-select', name: 'Issue Type' },
        { id: 'query-title', name: 'Query Title' },
        { id: 'status-select', name: 'Status' }
    ];
    
    requiredFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (!element.value.trim()) {
            errors.push(`${field.name} is required`);
        }
    });
    
    // Time spent validation
    const timeSpent = document.getElementById('time-spent').value;
    if (timeSpent && (isNaN(timeSpent) || parseInt(timeSpent) < 0)) {
        errors.push('Time spent must be a positive number');
    }
    
    // Query title validation
    const queryTitle = document.getElementById('query-title').value.trim();
    if (queryTitle && queryTitle.length < 3) {
        errors.push('Query Title must be at least 3 characters');
    }
    
    return errors;
}

function highlightFormErrors(errors) {
    // Clear previous error states
    document.querySelectorAll('.mdc-text-field--invalid').forEach(field => {
        field.classList.remove('mdc-text-field--invalid');
    });
    
    // Show error messages (simple implementation)
    if (errors.length > 0) {
        alert('Please fix the following errors:\n\n' + errors.join('\n'));
        return false;
    }
    
    return true;
}

function enhancedLogFormSubmit(event) {
    event.preventDefault();
    
    // Validate form
    const errors = validateLogForm();
    if (!highlightFormErrors(errors)) {
        return;
    }
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    // Call original form submit handler
    handleLogFormSubmit(event).finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Enhanced log rendering with animations
function renderLogsWithAnimation() {
    switch (currentView) {
        case 'table':
            renderTableViewAnimated();
            break;
        case 'kanban':
            renderKanbanViewAnimated();
            break;
        case 'grouped':
            renderGroupedViewAnimated();
            break;
    }
}

function renderTableViewAnimated() {
    const tbody = document.getElementById('logs-table-body');
    
    // Fade out current content
    tbody.style.opacity = '0.5';
    
    setTimeout(() => {
        renderTableView();
        
        // Fade in new content
        tbody.style.opacity = '1';
        tbody.style.transition = 'opacity 0.3s ease';
        
        // Add fade-in animation to rows
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(10px)';
            
            setTimeout(() => {
                row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 50);
        });
    }, 150);
}

function renderKanbanViewAnimated() {
    const containers = ['kanban-open', 'kanban-resolved', 'kanban-escalated'];
    
    // Fade out current content
    containers.forEach(id => {
        const container = document.getElementById(id);
        if (container) {
            container.style.opacity = '0.5';
        }
    });
    
    setTimeout(() => {
        renderKanbanView();
        
        // Fade in new content
        containers.forEach(id => {
            const container = document.getElementById(id);
            if (container) {
                container.style.opacity = '1';
                container.style.transition = 'opacity 0.3s ease';
                
                // Animate cards
                const cards = container.querySelectorAll('.kanban-card');
                cards.forEach((card, index) => {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'scale(1)';
                    }, index * 100);
                });
            }
        });
    }, 150);
}

function renderGroupedViewAnimated() {
    const container = document.getElementById('grouped-content');
    
    // Fade out current content
    container.style.opacity = '0.5';
    
    setTimeout(() => {
        renderGroupedView();
        
        // Fade in new content
        container.style.opacity = '1';
        container.style.transition = 'opacity 0.3s ease';
        
        // Animate sections
        const sections = container.querySelectorAll('.grouped-section');
        sections.forEach((section, index) => {
            section.style.opacity = '0';
            section.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                section.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                section.style.opacity = '1';
                section.style.transform = 'translateX(0)';
            }, index * 100);
        });
    }, 150);
}

// Advanced filtering capabilities
function setupAdvancedFilters() {
    // Date range filter
    const dateRangeContainer = document.createElement('div');
    dateRangeContainer.className = 'date-range-filter';
    dateRangeContainer.innerHTML = `
        <div class="mdc-text-field mdc-text-field--outlined">
            <input type="date" id="date-from" class="mdc-text-field__input">
            <div class="mdc-notched-outline">
                <div class="mdc-notched-outline__leading"></div>
                <div class="mdc-notched-outline__notch">
                    <label for="date-from" class="mdc-floating-label">From Date</label>
                </div>
                <div class="mdc-notched-outline__trailing"></div>
            </div>
        </div>
        <div class="mdc-text-field mdc-text-field--outlined">
            <input type="date" id="date-to" class="mdc-text-field__input">
            <div class="mdc-notched-outline">
                <div class="mdc-notched-outline__leading"></div>
                <div class="mdc-notched-outline__notch">
                    <label for="date-to" class="mdc-floating-label">To Date</label>
                </div>
                <div class="mdc-notched-outline__trailing"></div>
            </div>
        </div>
    `;
    
    // Add to filters row (would need to be integrated into the existing filter UI)
}

// Bulk operations
function setupBulkOperations() {
    // Add bulk action toolbar
    const bulkToolbar = document.createElement('div');
    bulkToolbar.className = 'bulk-toolbar';
    bulkToolbar.style.display = 'none';
    bulkToolbar.innerHTML = `
        <div class="bulk-actions">
            <span class="bulk-count">0 selected</span>
            <button class="mdc-button" onclick="bulkUpdateStatus()">
                <span class="mdc-button__label">Update Status</span>
            </button>
            <button class="mdc-button" onclick="bulkDelete()">
                <span class="mdc-button__label">Delete</span>
            </button>
            <button class="mdc-button" onclick="clearBulkSelection()">
                <span class="mdc-button__label">Clear</span>
            </button>
        </div>
    `;
    
    // Insert before table (would need DOM integration)
}

// Quick actions and shortcuts
function setupQuickActions() {
    // Add quick action buttons for common operations
    const quickActionsContainer = document.createElement('div');
    quickActionsContainer.className = 'quick-actions';
    quickActionsContainer.innerHTML = `
        <button class="mdc-fab mdc-fab--mini quick-action-fab" onclick="openAddLogModal()" title="Add New Log (Ctrl+N)">
            <span class="mdc-fab__icon material-icons">add</span>
        </button>
    `;
    
    // Position fixed in bottom right (would need CSS integration)
    quickActionsContainer.style.position = 'fixed';
    quickActionsContainer.style.bottom = '24px';
    quickActionsContainer.style.right = '24px';
    quickActionsContainer.style.zIndex = '1000';
    
    document.body.appendChild(quickActionsContainer);
}

// Auto-save draft functionality
let autoSaveDraft = null;

function setupAutoSave() {
    const form = document.getElementById('log-form');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('input', debounce(saveDraft, 2000));
    });
}

function saveDraft() {
    const formData = new FormData(document.getElementById('log-form'));
    const draftData = {};
    
    for (let [key, value] of formData.entries()) {
        draftData[key] = value;
    }
    
    localStorage.setItem('log_draft', JSON.stringify(draftData));
}

function loadDraft() {
    const draft = localStorage.getItem('log_draft');
    if (!draft) return;
    
    try {
        const draftData = JSON.parse(draft);
        const form = document.getElementById('log-form');
        
        Object.entries(draftData).forEach(([key, value]) => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = value === '1';
                } else {
                    input.value = value;
                }
            }
        });
    } catch (e) {
        console.error('Failed to load draft:', e);
    }
}

function clearDraft() {
    localStorage.removeItem('log_draft');
}

// Export current filtered logs
function exportFilteredLogs(format) {
    // Build query string from current filters
    const params = new URLSearchParams();
    Object.entries(currentFilters).forEach(([key, value]) => {
        if (value) {
            params.append(key, value);
        }
    });
    params.append('format', format);
    
    window.open(`api/export.php?${params.toString()}`, '_blank');
}

// Initialize logs page enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on the logs page
    if (document.getElementById('logs-tab')) {
        setupQuickActions();
        setupAutoSave();
    }
});
