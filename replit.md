# Support Issue Logger - Web Application

## Overview

This is a secure, fully offline CRUD web application designed for logging technical support issues related to WordPress and WooCommerce plugins. The application runs on MAMP (Apache + PHP + MariaDB) and provides comprehensive issue tracking capabilities with multiple viewing modes including table, Kanban, and grouped list views.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
- **Framework**: Vanilla JavaScript with Material Design Components (MDC)
- **CSS Framework**: Material Design 3 custom implementation
- **UI Library**: Google's Material Design Components Web
- **Charts**: Chart.js for dashboard visualizations
- **Architecture Pattern**: Component-based modular JavaScript with separation of concerns

### Backend Architecture
- **Server**: Apache (via MAMP)
- **Language**: PHP for server-side processing
- **Database**: MariaDB for data persistence
- **API Design**: RESTful API endpoints for CRUD operations
- **Session Management**: PHP-based session handling for user state

### Data Storage
- **Primary Database**: MariaDB with structured schema for support logs
- **Local Storage**: Browser localStorage for UI preferences and temporary data
- **File Structure**: Organized into logical directories (assets, api, views)

## Key Components

### 1. Dashboard Module (`dashboard.js`)
- **Purpose**: Provides analytical overview of support issues
- **Features**: 
  - Statistical summaries (total logs, open/resolved counts)
  - Multiple chart visualizations (pie, bar, line, donut)
  - Real-time data updates via AJAX
- **Charts**: Categories distribution, plugin usage, time series trends, recurring issue analysis

### 2. Logs Management Module (`logs.js`)
- **Purpose**: Core CRUD functionality for support issue entries
- **Features**:
  - Real-time search with debouncing (300ms delay)
  - Multi-criteria filtering (status, plugin, category)
  - Multiple view modes (table, Kanban, grouped list)
  - Keyboard shortcuts for power users
  - Table sorting capabilities

### 3. Material Design System (`styles.css`)
- **Design Language**: Material Design 3 specification
- **Color System**: CSS custom properties for consistent theming
- **Components**: Top app bar, text fields, selects, checkboxes, buttons
- **Responsive Design**: Mobile-first approach with flexible layouts

### 4. Core Application (`app.js`)
- **State Management**: Global application state for logs, views, and filters
- **Component Initialization**: Material Design Components setup
- **Event Coordination**: Central event handling and routing
- **Data Loading**: Initial application bootstrap and data fetching

## Data Flow

### 1. Application Initialization
1. Material Design Components are initialized
2. Event listeners are attached to UI elements
3. Dashboard data is fetched and displayed
4. Initial logs are loaded based on default view

### 2. CRUD Operations
1. **Create**: Modal forms with validation → PHP API → Database insertion
2. **Read**: Database queries → PHP API → JSON response → UI rendering
3. **Update**: Edit forms → PHP API → Database updates → UI refresh
4. **Delete**: Confirmation dialogs → PHP API → Database deletion → UI update

### 3. Real-time Features
- **Search**: Debounced input → filter application → re-render results
- **Filtering**: Multi-criteria filters → combined query → filtered results
- **View Switching**: State change → layout transformation → data re-organization

## External Dependencies

### Frontend Libraries
- **Material Design Components**: UI component library
- **Chart.js**: Data visualization and charts
- **Roboto Font**: Google Fonts for typography

### Backend Dependencies
- **MAMP Stack**: Apache, PHP, MariaDB
- **PHP Extensions**: PDO for database connectivity, JSON for API responses

### Development Tools
- **Local Development**: MAMP for offline development environment
- **Database Management**: phpMyAdmin (included with MAMP)

## Deployment Strategy

### Local Development (MAMP)
1. **Setup**: Install MAMP with Apache, PHP, and MariaDB
2. **Database**: Create MariaDB database with support logs schema
3. **File Placement**: Place application files in MAMP's htdocs directory
4. **Configuration**: Configure PHP database connection parameters
5. **Access**: Local access via http://localhost/[app-directory]

### Production Considerations
- **Security**: Input validation, SQL injection prevention, XSS protection
- **Performance**: Database indexing, query optimization, asset minification
- **Backup**: Regular database backups, file system backups
- **Monitoring**: Error logging, performance monitoring

### Key Technical Decisions

1. **Offline-First Architecture**: Chosen for security and reliability in isolated environments
2. **Material Design 3**: Selected for modern, accessible UI with consistent design patterns
3. **Vanilla JavaScript**: Preferred over frameworks for simplicity and reduced dependencies
4. **Modular Structure**: Component separation for maintainability and scalability
5. **RESTful API**: Standard approach for clean separation between frontend and backend
6. **MariaDB**: Chosen for compatibility with MAMP and robust SQL capabilities

The application prioritizes security, offline functionality, and user experience while maintaining a clean, maintainable codebase suitable for technical support workflow management.