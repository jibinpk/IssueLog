// Global app state
let currentLogs = [];
let currentView = 'table';
let currentFilters = {
    search: '',
    status: '',
    plugin: '',
    category: '',
    recurring: '',
    escalated: ''
};

// Material Components initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Material Components
    initializeMaterialComponents();
    
    // Initialize app
    initializeApp();
    
    // Set up event listeners
    setupEventListeners();
    
    // Load initial data
    loadDashboardData();
    loadLogs();
});

function initializeMaterialComponents() {
    // Initialize top app bar
    const topAppBar = new mdc.topAppBar.MDCTopAppBar(document.querySelector('.mdc-top-app-bar'));
    
    // Initialize tab bar
    const tabBar = new mdc.tabBar.MDCTabBar(document.querySelector('.mdc-tab-bar'));
    tabBar.listen('MDCTabBar:activated', (event) => {
        switchTab(event.detail.index);
    });
    
    // Initialize text fields
    document.querySelectorAll('.mdc-text-field').forEach(element => {
        new mdc.textField.MDCTextField(element);
    });
    
    // Initialize selects
    document.querySelectorAll('.mdc-select').forEach(element => {
        new mdc.select.MDCSelect(element);
    });
    
    // Initialize checkboxes
    document.querySelectorAll('.mdc-checkbox').forEach(element => {
        new mdc.checkbox.MDCCheckbox(element);
    });
    
    // Initialize buttons
    document.querySelectorAll('.mdc-button').forEach(element => {
        new mdc.ripple.MDCRipple(element);
    });
    
    // Initialize icon buttons
    document.querySelectorAll('.mdc-icon-button').forEach(element => {
        new mdc.ripple.MDCRipple(element);
        element.unbounded = true;
    });
    
    // Initialize dialog
    window.logModal = new mdc.dialog.MDCDialog(document.querySelector('#log-modal'));
}

function initializeApp() {
    // Set default tab
    switchTab(0);
    
    // Populate filter dropdowns
    populateFilterDropdowns();
    
    // Set up import file handler
    document.getElementById('import-file').addEventListener('change', handleFileSelect);
}

function setupEventListeners() {
    // Search input
    const searchInput = document.getElementById('search-input');
    searchInput.addEventListener('input', debounce(handleSearch, 300));
    
    // Filter selects
    document.getElementById('filter-status').addEventListener('change', handleFilterChange);
    document.getElementById('filter-plugin').addEventListener('change', handleFilterChange);
    document.getElementById('filter-category').addEventListener('change', handleFilterChange);
    
    // View switcher
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const view = e.currentTarget.dataset.view;
            switchView(view);
        });
    });
    
    // Group by selector
    document.getElementById('group-by').addEventListener('change', handleGroupByChange);
    
    // Log form submission
    document.getElementById('log-form').addEventListener('submit', handleLogFormSubmit);
    
    // Modal events
    window.logModal.listen('MDCDialog:closed', (event) => {
        if (event.detail.action === 'save') {
            // Form submission is handled by the submit event
        } else {
            resetLogForm();
        }
    });
}

function switchTab(index) {
    // Hide all tab panels
    document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    
    // Show selected tab panel
    const panels = ['dashboard-tab', 'logs-tab', 'import-export-tab'];
    if (panels[index]) {
        document.getElementById(panels[index]).classList.add('active');
        
        // Load data for specific tabs
        if (index === 0) { // Dashboard
            loadDashboardData();
        } else if (index === 1) { // Logs
            loadLogs();
        }
    }
}

function switchView(view) {
    // Update view buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-view="${view}"]`).classList.add('active');
    
    // Update view content
    document.querySelectorAll('.view-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`${view}-view`).classList.add('active');
    
    currentView = view;
    
    // Render logs in the new view
    renderLogs();
}

function populateFilterDropdowns() {
    // This would typically be populated from the backend
    const plugins = ['PDF Invoice', 'Product Feed', 'Gift Cards', 'WooCommerce Bookings', 'Custom Product Designer'];
    const categories = ['Activation', 'Template Issue', 'Export/Import', 'Configuration', 'Performance', 'Compatibility', 'Bug Report', 'Feature Request'];
    
    const pluginSelect = document.getElementById('filter-plugin');
    const categorySelect = document.getElementById('filter-category');
    
    plugins.forEach(plugin => {
        const option = document.createElement('option');
        option.value = plugin;
        option.textContent = plugin;
        pluginSelect.appendChild(option);
    });
    
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category;
        option.textContent = category;
        categorySelect.appendChild(option);
    });
}

function handleSearch(event) {
    currentFilters.search = event.target.value;
    loadLogs();
}

function handleFilterChange(event) {
    const filterType = event.target.id.replace('filter-', '');
    currentFilters[filterType] = event.target.value;
    loadLogs();
}

function handleGroupByChange() {
    if (currentView === 'grouped') {
        renderLogs();
    }
}

function clearFilters() {
    // Reset all filters
    currentFilters = {
        search: '',
        status: '',
        plugin: '',
        category: '',
        recurring: '',
        escalated: ''
    };
    
    // Reset form elements
    document.getElementById('search-input').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-plugin').value = '';
    document.getElementById('filter-category').value = '';
    
    // Reload logs
    loadLogs();
}

function loadLogs() {
    // Build query string from filters
    const params = new URLSearchParams();
    Object.entries(currentFilters).forEach(([key, value]) => {
        if (value) {
            params.append(key, value);
        }
    });
    
    fetch(`api/logs.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentLogs = data.data;
                renderLogs();
            } else {
                showError('Failed to load logs: ' + data.error);
            }
        })
        .catch(error => {
            showError('Network error: ' + error.message);
        });
}

function renderLogs() {
    switch (currentView) {
        case 'table':
            renderTableView();
            break;
        case 'kanban':
            renderKanbanView();
            break;
        case 'grouped':
            renderGroupedView();
            break;
    }
}

function renderTableView() {
    const tbody = document.getElementById('logs-table-body');
    tbody.innerHTML = '';
    
    if (currentLogs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-state">
                    <div class="empty-state-text">No logs found</div>
                    <div class="empty-state-subtext">Try adjusting your search or filters</div>
                </td>
            </tr>
        `;
        return;
    }
    
    currentLogs.forEach(log => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(log.date_created)}</td>
            <td>${escapeHtml(log.client_ref)}</td>
            <td>${escapeHtml(log.plugin_name)}</td>
            <td>${escapeHtml(log.issue_category)}</td>
            <td>${escapeHtml(log.issue_summary)}</td>
            <td><span class="status-badge ${getStatusClass(log.status)}">${log.status}</span></td>
            <td>${log.time_spent} min</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn edit" onclick="editLog(${log.id})" title="Edit">
                        <span class="material-icons">edit</span>
                    </button>
                    <button class="action-btn delete" onclick="deleteLog(${log.id})" title="Delete">
                        <span class="material-icons">delete</span>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function renderKanbanView() {
    const openCards = document.getElementById('kanban-open');
    const resolvedCards = document.getElementById('kanban-resolved');
    const escalatedCards = document.getElementById('kanban-escalated');
    
    // Clear existing cards
    [openCards, resolvedCards, escalatedCards].forEach(container => {
        container.innerHTML = '';
    });
    
    // Group logs by status
    const logsByStatus = {
        'Open': [],
        'Resolved': [],
        'Escalated': []
    };
    
    currentLogs.forEach(log => {
        if (logsByStatus[log.status]) {
            logsByStatus[log.status].push(log);
        }
    });
    
    // Render cards for each status
    Object.entries(logsByStatus).forEach(([status, logs]) => {
        const container = document.getElementById(`kanban-${status.toLowerCase()}`);
        
        logs.forEach(log => {
            const card = document.createElement('div');
            card.className = 'kanban-card';
            card.onclick = () => editLog(log.id);
            card.innerHTML = `
                <div class="kanban-card-title">${escapeHtml(log.issue_summary)}</div>
                <div class="kanban-card-meta">Client: ${escapeHtml(log.client_ref)}</div>
                <div class="kanban-card-meta">Plugin: ${escapeHtml(log.plugin_name)}</div>
                <div class="kanban-card-meta">Category: ${escapeHtml(log.issue_category)}</div>
                <div class="kanban-card-meta">Time: ${log.time_spent} min</div>
            `;
            container.appendChild(card);
        });
        
        if (logs.length === 0) {
            container.innerHTML = '<div class="empty-state-text">No logs</div>';
        }
    });
}

function renderGroupedView() {
    const groupBy = document.getElementById('group-by').value;
    const container = document.getElementById('grouped-content');
    container.innerHTML = '';
    
    // Group logs
    const groups = {};
    currentLogs.forEach(log => {
        const groupKey = log[groupBy] || 'Unknown';
        if (!groups[groupKey]) {
            groups[groupKey] = [];
        }
        groups[groupKey].push(log);
    });
    
    // Render groups
    Object.entries(groups).forEach(([groupName, logs]) => {
        const section = document.createElement('div');
        section.className = 'grouped-section';
        
        const header = document.createElement('div');
        header.className = 'grouped-header';
        header.textContent = `${groupName} (${logs.length})`;
        section.appendChild(header);
        
        const items = document.createElement('div');
        items.className = 'grouped-items';
        
        logs.forEach(log => {
            const item = document.createElement('div');
            item.className = 'grouped-item';
            item.innerHTML = `
                <div>
                    <div>${escapeHtml(log.issue_summary)}</div>
                    <div style="font-size: 0.875rem; color: var(--md-sys-color-on-surface-variant);">
                        ${escapeHtml(log.client_ref)} • ${formatDate(log.date_created)}
                    </div>
                </div>
                <div>
                    <span class="status-badge ${getStatusClass(log.status)}">${log.status}</span>
                </div>
            `;
            item.onclick = () => editLog(log.id);
            item.style.cursor = 'pointer';
            items.appendChild(item);
        });
        
        section.appendChild(items);
        container.appendChild(section);
    });
    
    if (Object.keys(groups).length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-text">No logs found</div>
                <div class="empty-state-subtext">Try adjusting your search or filters</div>
            </div>
        `;
    }
}

function openAddLogModal() {
    resetLogForm();
    document.getElementById('modal-title').textContent = 'Add Log Entry';
    window.logModal.open();
}

function editLog(id) {
    const log = currentLogs.find(l => l.id == id);
    if (!log) {
        showError('Log not found');
        return;
    }
    
    // Populate form with log data
    document.getElementById('log-id').value = log.id;
    document.getElementById('client-ref').value = log.client_ref;
    document.getElementById('plugin-select').value = log.plugin_name;
    document.getElementById('plugin-version').value = log.plugin_version || '';
    document.getElementById('wp-version').value = log.wp_version || '';
    document.getElementById('wc-version').value = log.wc_version || '';
    document.getElementById('category-select').value = log.issue_category;
    document.getElementById('issue-summary').value = log.issue_summary;
    document.getElementById('detailed-description').value = log.detailed_description || '';
    document.getElementById('steps-reproduce').value = log.steps_reproduce || '';
    document.getElementById('errors-logs').value = log.errors_logs || '';
    document.getElementById('troubleshooting-steps').value = log.troubleshooting_steps || '';
    document.getElementById('resolution').value = log.resolution || '';
    document.getElementById('time-spent').value = log.time_spent || '';
    document.getElementById('escalated').checked = log.escalated == 1;
    document.getElementById('status-select').value = log.status;
    document.getElementById('recurring').checked = log.recurring == 1;
    
    document.getElementById('modal-title').textContent = 'Edit Log Entry';
    window.logModal.open();
}

function deleteLog(id) {
    if (!confirm('Are you sure you want to delete this log entry?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    fetch('api/logs.php', {
        method: 'DELETE',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Log entry deleted successfully');
            loadLogs();
            loadDashboardData(); // Refresh dashboard stats
        } else {
            showError('Failed to delete log: ' + data.error);
        }
    })
    .catch(error => {
        showError('Network error: ' + error.message);
    });
}

function handleLogFormSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const isEdit = formData.get('id');
    
    const url = 'api/logs.php';
    const method = isEdit ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(isEdit ? 'Log entry updated successfully' : 'Log entry created successfully');
            window.logModal.close();
            resetLogForm();
            loadLogs();
            loadDashboardData(); // Refresh dashboard stats
        } else {
            showError('Failed to save log: ' + data.error);
        }
    })
    .catch(error => {
        showError('Network error: ' + error.message);
    });
}

function resetLogForm() {
    document.getElementById('log-form').reset();
    document.getElementById('log-id').value = '';
}

// Export functions
function exportLogs(format) {
    window.open(`api/export.php?format=${format}`, '_blank');
}

// Import functions
function handleFileSelect(event) {
    const file = event.target.files[0];
    const fileNameSpan = document.getElementById('file-name');
    const importBtn = document.getElementById('import-btn');
    
    if (file) {
        fileNameSpan.textContent = file.name;
        importBtn.disabled = false;
    } else {
        fileNameSpan.textContent = '';
        importBtn.disabled = true;
    }
}

function importLogs() {
    const fileInput = document.getElementById('import-file');
    const file = fileInput.files[0];
    
    if (!file) {
        showError('Please select a file to import');
        return;
    }
    
    const formData = new FormData();
    formData.append('import_file', file);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    // Show loading state
    const importBtn = document.getElementById('import-btn');
    const originalText = importBtn.textContent;
    importBtn.textContent = 'Importing...';
    importBtn.disabled = true;
    
    fetch('api/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const result = data.data;
            let message = `Import completed: ${result.imported} imported, ${result.skipped} skipped`;
            
            if (result.errors.length > 0) {
                message += `\n\nErrors:\n${result.errors.join('\n')}`;
            }
            
            const statusDiv = document.getElementById('import-status');
            statusDiv.className = 'success';
            statusDiv.textContent = message;
            statusDiv.style.display = 'block';
            
            // Reset form
            fileInput.value = '';
            document.getElementById('file-name').textContent = '';
            
            // Refresh data
            loadLogs();
            loadDashboardData();
        } else {
            showError('Import failed: ' + data.error);
        }
    })
    .catch(error => {
        showError('Network error: ' + error.message);
    })
    .finally(() => {
        importBtn.textContent = originalText;
        importBtn.disabled = false;
    });
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

function getStatusClass(status) {
    switch (status.toLowerCase()) {
        case 'open':
            return 'status-open';
        case 'resolved':
            return 'status-resolved';
        case 'escalated':
            return 'status-escalated';
        default:
            return '';
    }
}

function showError(message) {
    // Create or update error snackbar
    console.error(message);
    alert('Error: ' + message); // Simple alert for now
}

function showSuccess(message) {
    // Create or update success snackbar
    console.log(message);
    // Simple implementation - in a real app you'd use MDC Snackbar
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}
