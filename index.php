<?php
// Check if application is installed
if (!file_exists('config/installed.lock')) {
    header('Location: install.php');
    exit;
}

// Initialize session configuration first
require_once 'config/session.php';

// Start session after configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth.php';
require_once 'includes/csrf.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Log Manager</title>
    
    <!-- Material Design 3 -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="app-container">
        <!-- Top App Bar -->
        <header class="mdc-top-app-bar mdc-top-app-bar--fixed">
            <div class="mdc-top-app-bar__row">
                <section class="mdc-top-app-bar__section mdc-top-app-bar__section--align-start">
                    <span class="mdc-top-app-bar__title">Support Log Manager</span>
                </section>
                <section class="mdc-top-app-bar__section mdc-top-app-bar__section--align-end">
                    <button class="mdc-icon-button material-icons" onclick="logout()">logout</button>
                </section>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mdc-top-app-bar--fixed-adjust">
            <!-- Navigation Tabs -->
            <div class="mdc-tab-bar" role="tablist">
                <div class="mdc-tab-scroller">
                    <div class="mdc-tab-scroller__scroll-area">
                        <div class="mdc-tab-scroller__scroll-content">
                            <button class="mdc-tab mdc-tab--active" role="tab" aria-selected="true" tabindex="0" data-tab="dashboard">
                                <span class="mdc-tab__content">
                                    <span class="mdc-tab__icon material-icons">dashboard</span>
                                    <span class="mdc-tab__text-label">Dashboard</span>
                                </span>
                                <span class="mdc-tab-indicator mdc-tab-indicator--active">
                                    <span class="mdc-tab-indicator__content mdc-tab-indicator__content--underline"></span>
                                </span>
                            </button>
                            <button class="mdc-tab" role="tab" aria-selected="false" tabindex="-1" data-tab="logs">
                                <span class="mdc-tab__content">
                                    <span class="mdc-tab__icon material-icons">list</span>
                                    <span class="mdc-tab__text-label">Logs</span>
                                </span>
                                <span class="mdc-tab-indicator">
                                    <span class="mdc-tab-indicator__content mdc-tab-indicator__content--underline"></span>
                                </span>
                            </button>
                            <button class="mdc-tab" role="tab" aria-selected="false" tabindex="-1" data-tab="import-export">
                                <span class="mdc-tab__content">
                                    <span class="mdc-tab__icon material-icons">import_export</span>
                                    <span class="mdc-tab__text-label">Import/Export</span>
                                </span>
                                <span class="mdc-tab-indicator">
                                    <span class="mdc-tab-indicator__content mdc-tab-indicator__content--underline"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Dashboard Tab -->
                <div id="dashboard-tab" class="tab-panel active">
                    <div class="dashboard-container">
                        <div class="stats-grid">
                            <div class="mdc-card stat-card">
                                <div class="mdc-card__primary-action">
                                    <div class="stat-number" id="total-logs">0</div>
                                    <div class="stat-label">Total Logs</div>
                                </div>
                            </div>
                            <div class="mdc-card stat-card">
                                <div class="mdc-card__primary-action">
                                    <div class="stat-number" id="open-logs">0</div>
                                    <div class="stat-label">Open Issues</div>
                                </div>
                            </div>
                            <div class="mdc-card stat-card">
                                <div class="mdc-card__primary-action">
                                    <div class="stat-number" id="resolved-logs">0</div>
                                    <div class="stat-label">Resolved</div>
                                </div>
                            </div>
                            <div class="mdc-card stat-card">
                                <div class="mdc-card__primary-action">
                                    <div class="stat-number" id="escalated-logs">0</div>
                                    <div class="stat-label">Escalated</div>
                                </div>
                            </div>
                            <div class="mdc-card stat-card">
                                <div class="mdc-card__primary-action">
                                    <div class="stat-number" id="recurring-logs">0</div>
                                    <div class="stat-label">Recurring Issues</div>
                                </div>
                            </div>
                            <div class="mdc-card stat-card">
                                <div class="mdc-card__primary-action">
                                    <div class="stat-number" id="avg-time">0</div>
                                    <div class="stat-label">Avg Time (min)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="charts-grid">
                            <div class="mdc-card chart-card">
                                <div class="chart-header">Issue Categories</div>
                                <canvas id="categoriesChart"></canvas>
                            </div>
                            <div class="mdc-card chart-card">
                                <div class="chart-header">Logs by Plugin</div>
                                <canvas id="pluginsChart"></canvas>
                            </div>
                            <div class="mdc-card chart-card">
                                <div class="chart-header">Issues Over Time</div>
                                <canvas id="timeChart"></canvas>
                            </div>
                            <div class="mdc-card chart-card">
                                <div class="chart-header">Recurring vs Non-recurring</div>
                                <canvas id="recurringChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logs Tab -->
                <div id="logs-tab" class="tab-panel">
                    <div class="logs-container">
                        <!-- View Controls -->
                        <div class="controls-section">
                            <div class="mdc-card controls-card">
                                <div class="controls-row">
                                    <button class="mdc-button mdc-button--raised" onclick="openAddLogModal()">
                                        <span class="mdc-button__label">Add Log Entry</span>
                                    </button>
                                    
                                    <div class="view-switcher">
                                        <button class="mdc-icon-button view-btn active" data-view="table" title="Table View">
                                            <span class="material-icons">table_view</span>
                                        </button>
                                        <button class="mdc-icon-button view-btn" data-view="kanban" title="Kanban View">
                                            <span class="material-icons">view_kanban</span>
                                        </button>
                                        <button class="mdc-icon-button view-btn" data-view="grouped" title="Grouped View">
                                            <span class="material-icons">view_list</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Search and Filters -->
                                <div class="search-filters">
                                    <div class="mdc-text-field mdc-text-field--outlined search-field">
                                        <input type="text" id="search-input" class="mdc-text-field__input" placeholder="Search logs...">
                                        <div class="mdc-notched-outline">
                                            <div class="mdc-notched-outline__leading"></div>
                                            <div class="mdc-notched-outline__notch">
                                                <label for="search-input" class="mdc-floating-label">Search</label>
                                            </div>
                                            <div class="mdc-notched-outline__trailing"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="filters-row">
                                        <select class="mdc-select__native-control" id="filter-status">
                                            <option value="">All Statuses</option>
                                            <option value="Open">Open</option>
                                            <option value="Resolved">Resolved</option>
                                            <option value="Escalated">Escalated</option>
                                        </select>
                                        
                                        <select class="mdc-select__native-control" id="filter-plugin">
                                            <option value="">All Plugins</option>
                                        </select>
                                        
                                        <select class="mdc-select__native-control" id="filter-category">
                                            <option value="">All Categories</option>
                                        </select>
                                        
                                        <button class="mdc-button" onclick="clearFilters()">
                                            <span class="mdc-button__label">Clear Filters</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Logs Display -->
                        <div id="logs-display">
                            <!-- Table View -->
                            <div id="table-view" class="view-content active">
                                <div class="mdc-card">
                                    <div class="table-container">
                                        <table class="logs-table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Plugin</th>
                                                    <th>Issue Type</th>
                                                    <th>Concern Area</th>
                                                    <th>Query Title</th>
                                                    <th>Status</th>
                                                    <th>Time</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="logs-table-body">
                                                <!-- Logs will be populated here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Kanban View -->
                            <div id="kanban-view" class="view-content">
                                <div class="kanban-board">
                                    <div class="kanban-column">
                                        <h3>Open</h3>
                                        <div id="kanban-open" class="kanban-cards"></div>
                                    </div>
                                    <div class="kanban-column">
                                        <h3>Resolved</h3>
                                        <div id="kanban-resolved" class="kanban-cards"></div>
                                    </div>
                                    <div class="kanban-column">
                                        <h3>Escalated</h3>
                                        <div id="kanban-escalated" class="kanban-cards"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Grouped View -->
                            <div id="grouped-view" class="view-content">
                                <div class="grouped-controls">
                                    <label>Group by:</label>
                                    <select id="group-by" class="mdc-select__native-control">
                                        <option value="plugin_name">Plugin Name</option>
                                        <option value="concern_area">Concern Area</option>
                                        <option value="issue_type">Issue Type</option>
                                    </select>
                                </div>
                                <div id="grouped-content"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import/Export Tab -->
                <div id="import-export-tab" class="tab-panel">
                    <div class="import-export-container">
                        <div class="mdc-card">
                            <h2>Export Logs</h2>
                            <p>Export your support logs to CSV or JSON format</p>
                            <div class="export-buttons">
                                <button class="mdc-button mdc-button--raised" onclick="exportLogs('csv')">
                                    <span class="mdc-button__label">Export as CSV</span>
                                </button>
                                <button class="mdc-button mdc-button--outlined" onclick="exportLogs('json')">
                                    <span class="mdc-button__label">Export as JSON</span>
                                </button>
                            </div>
                        </div>

                        <div class="mdc-card">
                            <h2>Import Logs</h2>
                            <p>Import support logs from CSV or JSON files</p>
                            <div class="import-section">
                                <input type="file" id="import-file" accept=".csv,.json" style="display: none;">
                                <button class="mdc-button mdc-button--outlined" onclick="document.getElementById('import-file').click()">
                                    <span class="mdc-button__label">Choose File</span>
                                </button>
                                <span id="file-name"></span>
                                <button class="mdc-button mdc-button--raised" onclick="importLogs()" disabled id="import-btn">
                                    <span class="mdc-button__label">Import Logs</span>
                                </button>
                            </div>
                            <div id="import-status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Log Modal -->
    <div class="mdc-dialog" id="log-modal">
        <div class="mdc-dialog__container">
            <div class="mdc-dialog__surface">
                <h2 class="mdc-dialog__title" id="modal-title">Add Log Entry</h2>
                <div class="mdc-dialog__content">
                    <form id="log-form">
                        <input type="hidden" id="log-id" name="id">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-row">
                            <div class="mdc-select mdc-select--outlined">
                                <div class="mdc-select__anchor">
                                    <select id="issue-type-select" name="issue_type" class="mdc-select__native-control" required>
                                        <option value="" disabled selected></option>
                                        <option value="Technical">Technical</option>
                                        <option value="Pre-sale">Pre-sale</option>
                                        <option value="Account/Billing">Account/Billing</option>
                                    </select>
                                    <div class="mdc-notched-outline">
                                        <div class="mdc-notched-outline__leading"></div>
                                        <div class="mdc-notched-outline__notch">
                                            <label for="issue-type-select" class="mdc-floating-label">Issue Type</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="mdc-select mdc-select--outlined">
                                <div class="mdc-select__anchor">
                                    <span class="mdc-notched-outline">
                                        <span class="mdc-notched-outline__leading"></span>
                                        <span class="mdc-notched-outline__notch">
                                            <span class="mdc-floating-label">Plugin Name</span>
                                        </span>
                                        <span class="mdc-notched-outline__trailing"></span>
                                    </span>
                                    <span class="mdc-select__selected-text-container">
                                        <span class="mdc-select__selected-text"></span>
                                    </span>
                                    <span class="mdc-select__dropdown-icon">
                                        <svg class="mdc-select__dropdown-icon-graphic" viewBox="7 10 10 5">
                                            <polygon class="mdc-select__dropdown-icon-inactive" stroke="none" fill-rule="evenodd" points="7 10 12 15 17 10"></polygon>
                                            <polygon class="mdc-select__dropdown-icon-active" stroke="none" fill-rule="evenodd" points="7 15 12 10 17 15"></polygon>
                                        </svg>
                                    </span>
                                </div>
                                <div class="mdc-select__menu mdc-menu mdc-menu-surface mdc-menu-surface--fullwidth">
                                    <ul class="mdc-list">
                                        <li class="mdc-list-item" data-value="PDF Invoice">
                                            <span class="mdc-list-item__text">PDF Invoice</span>
                                        </li>
                                        <li class="mdc-list-item" data-value="Product Feed">
                                            <span class="mdc-list-item__text">Product Feed</span>
                                        </li>
                                        <li class="mdc-list-item" data-value="Gift Cards">
                                            <span class="mdc-list-item__text">Gift Cards</span>
                                        </li>
                                        <li class="mdc-list-item" data-value="WooCommerce Bookings">
                                            <span class="mdc-list-item__text">WooCommerce Bookings</span>
                                        </li>
                                        <li class="mdc-list-item" data-value="Custom Product Designer">
                                            <span class="mdc-list-item__text">Custom Product Designer</span>
                                        </li>
                                    </ul>
                                </div>
                                <select name="plugin_name" id="plugin-select" class="mdc-select__native-control" required>
                                    <option value="">Select Plugin</option>
                                    <option value="PDF Invoice">PDF Invoice</option>
                                    <option value="Product Feed">Product Feed</option>
                                    <option value="Gift Cards">Gift Cards</option>
                                    <option value="WooCommerce Bookings">WooCommerce Bookings</option>
                                    <option value="Custom Product Designer">Custom Product Designer</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="mdc-text-field mdc-text-field--outlined">
                                <input type="text" id="plugin-version" name="plugin_version" class="mdc-text-field__input">
                                <div class="mdc-notched-outline">
                                    <div class="mdc-notched-outline__leading"></div>
                                    <div class="mdc-notched-outline__notch">
                                        <label for="plugin-version" class="mdc-floating-label">Plugin Version</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                            <div class="mdc-text-field mdc-text-field--outlined">
                                <input type="text" id="wp-version" name="wp_version" class="mdc-text-field__input">
                                <div class="mdc-notched-outline">
                                    <div class="mdc-notched-outline__leading"></div>
                                    <div class="mdc-notched-outline__notch">
                                        <label for="wp-version" class="mdc-floating-label">WordPress Version</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                            <div class="mdc-text-field mdc-text-field--outlined">
                                <input type="text" id="wc-version" name="wc_version" class="mdc-text-field__input">
                                <div class="mdc-notched-outline">
                                    <div class="mdc-notched-outline__leading"></div>
                                    <div class="mdc-notched-outline__notch">
                                        <label for="wc-version" class="mdc-floating-label">WooCommerce Version</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <select name="concern_area" id="category-select" class="mdc-select__native-control" required>
                                <option value="">Select Concern Area</option>
                                <option value="Plugin activation">Plugin activation</option>
                                <option value="Configuration and compatibility">Configuration and compatibility</option>
                                <option value="Export issues">Export issues</option>
                                <option value="Logs and errors">Logs and errors</option>
                                <option value="Template/Design customization">Template/Design customization</option>
                                <option value="Product/Order export">Product/Order export</option>
                                <option value="Feature request">Feature request</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="mdc-text-field mdc-text-field--outlined">
                                <input type="text" id="query-title" name="query_title" class="mdc-text-field__input" required>
                                <div class="mdc-notched-outline">
                                    <div class="mdc-notched-outline__leading"></div>
                                    <div class="mdc-notched-outline__notch">
                                        <label for="query-title" class="mdc-floating-label">Query Title</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="mdc-text-field mdc-text-field--outlined mdc-text-field--textarea">
                                <textarea id="description" name="description" class="mdc-text-field__input" rows="3"></textarea>
                                <div class="mdc-notched-outline">
                                    <div class="mdc-notched-outline__leading"></div>
                                    <div class="mdc-notched-outline__notch">
                                        <label for="description" class="mdc-floating-label">Description</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="mdc-text-field mdc-text-field--outlined mdc-text-field--textarea">
                                <textarea id="steps-reproduce" name="steps_reproduce" class="mdc-text-field__input" rows="3"></textarea>
                                <div class="mdc-notched-outline">
                                    <div class="mdc-notched-outline__leading"></div>
                                    <div class="mdc-notched-outline__notch">
                                        <label for="steps-reproduce" class="mdc-floating-label">Steps to Reproduce</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="mdc-text-field mdc-text-field--outlined mdc-text-field--textarea">
                                <textarea id="error-logs" name="error_logs" class="mdc-text-field__input" rows="3"></textarea>
                                <div class="mdc-notched-outline">
                                    <div class="mdc-notched-outline__leading"></div>
                                    <div class="mdc-notched-outline__notch">
                                        <label for="error-logs" class="mdc-floating-label">Error Logs (No PII)</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="mdc-text-field mdc-text-field--outlined">
                                <input type="text" id="assigned-agent" name="assigned_agent" class="mdc-text-field__input">
                                <div class="mdc-notched-outline">
                                    <div class="mdc-notched-outline__leading"></div>
                                    <div class="mdc-notched-outline__notch">
                                        <label for="assigned-agent" class="mdc-floating-label">Assigned Agent</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="mdc-text-field mdc-text-field--outlined mdc-text-field--textarea">
                                <textarea id="resolution-notes" name="resolution_notes" class="mdc-text-field__input" rows="3"></textarea>
                                <div class="mdc-notched-outline">
                                    <div class="mdc-notched-outline__leading"></div>
                                    <div class="mdc-notched-outline__notch">
                                        <label for="resolution-notes" class="mdc-floating-label">Resolution Notes</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="mdc-text-field mdc-text-field--outlined">
                                <input type="number" id="time-spent" name="time_spent" class="mdc-text-field__input" min="0">
                                <div class="mdc-notched-outline">
                                    <div class="mdc-notched-outline__leading"></div>
                                    <div class="mdc-notched-outline__notch">
                                        <label for="time-spent" class="mdc-floating-label">Time Spent (minutes)</label>
                                    </div>
                                    <div class="mdc-notched-outline__trailing"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="mdc-select mdc-select--outlined">
                                <div class="mdc-select__anchor">
                                    <select id="escalated-select" name="escalated_to_dev" class="mdc-select__native-control">
                                        <option value="No" selected>No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                    <div class="mdc-notched-outline">
                                        <div class="mdc-notched-outline__leading"></div>
                                        <div class="mdc-notched-outline__notch">
                                            <label for="escalated-select" class="mdc-floating-label">Escalated to Dev</label>
                                        </div>
                                        <div class="mdc-notched-outline__trailing"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mdc-select mdc-select--outlined">
                                <div class="mdc-select__anchor">
                                    <select id="recurring-select" name="recurring_issue" class="mdc-select__native-control">
                                        <option value="No" selected>No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                    <div class="mdc-notched-outline">
                                        <div class="mdc-notched-outline__leading"></div>
                                        <div class="mdc-notched-outline__notch">
                                            <label for="recurring-select" class="mdc-floating-label">Recurring Issue</label>
                                        </div>
                                        <div class="mdc-notched-outline__trailing"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <select name="status" id="status-select" class="mdc-select__native-control" required>
                                <option value="Open">Open</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Escalated">Escalated</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="mdc-dialog__actions">
                    <button type="button" class="mdc-button mdc-dialog__button" data-mdc-dialog-action="cancel">
                        <span class="mdc-button__label">Cancel</span>
                    </button>
                    <button type="submit" form="log-form" class="mdc-button mdc-button--raised mdc-dialog__button" data-mdc-dialog-action="save">
                        <span class="mdc-button__label">Save</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="mdc-dialog__scrim"></div>
    </div>

    <!-- Material Components Web -->
    <script src="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.js"></script>
    
    <!-- Custom Scripts -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/logs.js"></script>
</body>
</html>
