/**
 * MCLS JavaScript Framework
 * Department of Forestry, Fisheries and the Environment
 * Main application JavaScript file
 */

class MCLSApp {
    constructor() {
        this.sessionTimeout = 3600; // From PHP config
        this.sessionWarningTime = 300; // 5 minutes before timeout
        this.sessionCheckInterval = 60000; // Check every minute
        this.csrfToken = '';
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.initializeTooltips();
        this.startSessionMonitoring();
        this.loadCsrfToken();
        this.initializeSidebar();
        this.initializeModals();
        this.setupFormValidation();
    }
    
    setupEventListeners() {
        // Mobile menu toggle
        document.addEventListener('click', (e) => {
            if (e.target.matches('.mobile-menu-toggle')) {
                this.toggleMobileSidebar();
            }
        });
        
        // Sidebar toggle
        document.addEventListener('click', (e) => {
            if (e.target.matches('.sidebar-toggle')) {
                this.toggleSidebar();
            }
        });
        
        // Global AJAX setup
        this.setupAjaxDefaults();
        
        // Form submission handling
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.ajax-form')) {
                e.preventDefault();
                this.handleAjaxForm(e.target);
            }
        });
        
        // Auto-save functionality
        document.addEventListener('input', (e) => {
            if (e.target.matches('.auto-save')) {
                this.debounce(() => {
                    this.autoSave(e.target);
                }, 2000)();
            }
        });
    }
    
    initializeTooltips() {
        // Enhanced tooltip functionality
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target);
            });
            
            element.addEventListener('mouseleave', (e) => {
                this.hideTooltip(e.target);
            });
        });
    }
    
    initializeSidebar() {
        const sidebar = document.querySelector('.app-sidebar');
        const main = document.querySelector('.app-main');
        const footer = document.querySelector('.app-footer');
        
        // Restore sidebar state from localStorage
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isCollapsed) {
            sidebar?.classList.add('collapsed');
            main?.classList.add('sidebar-collapsed');
            footer?.classList.add('sidebar-collapsed');
        }
    }
    
    toggleSidebar() {
        const sidebar = document.querySelector('.app-sidebar');
        const main = document.querySelector('.app-main');
        const footer = document.querySelector('.app-footer');
        
        const isCollapsed = sidebar?.classList.contains('collapsed');
        
        if (isCollapsed) {
            sidebar?.classList.remove('collapsed');
            main?.classList.remove('sidebar-collapsed');
            footer?.classList.remove('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', 'false');
        } else {
            sidebar?.classList.add('collapsed');
            main?.classList.add('sidebar-collapsed');
            footer?.classList.add('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', 'true');
        }
    }
    
    toggleMobileSidebar() {
        const sidebar = document.querySelector('.app-sidebar');
        sidebar?.classList.toggle('mobile-open');
    }
    
    startSessionMonitoring() {
        setInterval(() => {
            this.checkSessionStatus();
        }, this.sessionCheckInterval);
    }
    
    async checkSessionStatus() {
        try {
            const response = await fetch('api/session-check.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (!data.authenticated) {
                this.showSessionExpiredModal();
                return;
            }
            
            if (data.timeRemaining <= this.sessionWarningTime) {
                this.showSessionWarning(data.timeRemaining);
            }
            
        } catch (error) {
            console.error('Session check failed:', error);
        }
    }
    
    async loadCsrfToken() {
        try {
            const response = await fetch('api/csrf-token.php');
            const data = await response.json();
            this.csrfToken = data.token;
        } catch (error) {
            console.error('Failed to load CSRF token:', error);
        }
    }
    
    setupAjaxDefaults() {
        // Add CSRF token to all AJAX requests
        const originalFetch = window.fetch;
        window.fetch = (url, options = {}) => {
            if (typeof url === 'string' && url.startsWith('/') || url.includes(window.location.hostname)) {
                options.headers = options.headers || {};
                if (this.csrfToken) {
                    options.headers['X-CSRF-Token'] = this.csrfToken;
                }
            }
            return originalFetch(url, options);
        };
    }
    
    async handleAjaxForm(form) {
        const formData = new FormData(form);
        const loadingElement = this.showLoading(form);
        
        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('success', result.message || 'Operation completed successfully');
                if (result.redirect) {
                    window.location.href = result.redirect;
                }
                if (result.reload) {
                    window.location.reload();
                }
            } else {
                this.showAlert('danger', result.message || 'An error occurred');
                this.displayFormErrors(form, result.errors || {});
            }
            
        } catch (error) {
            console.error('Form submission error:', error);
            this.showAlert('danger', 'Network error. Please try again.');
        } finally {
            this.hideLoading(loadingElement);
        }
    }
    
    displayFormErrors(form, errors) {
        // Clear previous errors
        form.querySelectorAll('.form-control').forEach(input => {
            input.classList.remove('is-invalid');
            const errorElement = input.parentNode.querySelector('.form-text.error');
            if (errorElement) {
                errorElement.remove();
            }
        });
        
        // Display new errors
        Object.keys(errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const errorElement = document.createElement('div');
                errorElement.className = 'form-text error';
                errorElement.textContent = errors[field];
                input.parentNode.appendChild(errorElement);
            }
        });
    }
    
    setupFormValidation() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
            
            // Real-time validation
            form.querySelectorAll('.form-control').forEach(input => {
                input.addEventListener('blur', () => {
                    this.validateField(input);
                });
            });
        });
    }
    
    validateForm(form) {
        let isValid = true;
        
        form.querySelectorAll('.form-control[required]').forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    validateField(input) {
        const value = input.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        // Required validation
        if (input.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        
        // Email validation
        if (input.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }
        
        // Phone validation
        if (input.type === 'tel' && value) {
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
            if (!phoneRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number';
            }
        }
        
        // Update field state
        input.classList.toggle('is-invalid', !isValid);
        input.classList.toggle('is-valid', isValid && value);
        
        // Show/hide error message
        const existingError = input.parentNode.querySelector('.form-text.error');
        if (existingError) {
            existingError.remove();
        }
        
        if (!isValid && errorMessage) {
            const errorElement = document.createElement('div');
            errorElement.className = 'form-text error';
            errorElement.textContent = errorMessage;
            input.parentNode.appendChild(errorElement);
        }
        
        return isValid;
    }
    
    showLoading(element) {
        const loading = document.createElement('div');
        loading.className = 'loading-overlay';
        loading.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <div class="loading-text">Processing...</div>
            </div>
        `;
        
        element.style.position = 'relative';
        element.appendChild(loading);
        
        return loading;
    }
    
    hideLoading(loadingElement) {
        if (loadingElement && loadingElement.parentNode) {
            loadingElement.parentNode.removeChild(loadingElement);
        }
    }
    
    showAlert(type, message, autoHide = true) {
        const alertContainer = document.querySelector('.alert-container') || this.createAlertContainer();
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible`;
        alert.innerHTML = `
            <span class="alert-message">${message}</span>
            <button type="button" class="btn-close" onclick="this.parentNode.remove()">×</button>
        `;
        
        alertContainer.appendChild(alert);
        
        if (autoHide) {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
    }
    
    createAlertContainer() {
        const container = document.createElement('div');
        container.className = 'alert-container';
        container.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
        return container;
    }
    
    showSessionWarning(timeRemaining) {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        
        this.showModal({
            title: 'Session Warning',
            body: `Your session will expire in ${minutes}:${seconds.toString().padStart(2, '0')}. Would you like to extend your session?`,
            buttons: [
                {
                    text: 'Extend Session',
                    class: 'btn-primary',
                    onclick: () => this.extendSession()
                },
                {
                    text: 'Logout',
                    class: 'btn-secondary',
                    onclick: () => window.location.href = 'logout.php'
                }
            ]
        });
    }
    
    showSessionExpiredModal() {
        this.showModal({
            title: 'Session Expired',
            body: 'Your session has expired. Please log in again.',
            buttons: [
                {
                    text: 'Login',
                    class: 'btn-primary',
                    onclick: () => window.location.href = 'login.php'
                }
            ],
            backdrop: 'static'
        });
    }
    
    async extendSession() {
        try {
            const response = await fetch('api/extend-session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.hideModal();
                this.showAlert('success', 'Session extended successfully');
            } else {
                this.showAlert('danger', 'Failed to extend session');
            }
            
        } catch (error) {
            console.error('Session extension error:', error);
            this.showAlert('danger', 'Network error');
        }
    }
    
    initializeModals() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-modal]')) {
                this.showModalFromElement(e.target);
            }
            
            if (e.target.matches('.modal-backdrop, .modal-close')) {
                this.hideModal();
            }
        });
    }
    
    showModal(options) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-backdrop ${options.backdrop === 'static' ? 'static' : ''}"></div>
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${options.title}</h5>
                        ${options.backdrop !== 'static' ? '<button type="button" class="modal-close">×</button>' : ''}
                    </div>
                    <div class="modal-body">
                        ${options.body}
                    </div>
                    <div class="modal-footer">
                        ${options.buttons ? options.buttons.map(btn => 
                            `<button type="button" class="btn ${btn.class}" onclick="${btn.onclick}">${btn.text}</button>`
                        ).join('') : ''}
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Trigger animation
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
    
    hideModal() {
        const modal = document.querySelector('.modal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.remove();
            }, 300);
        }
    }
    
    autoSave(element) {
        const form = element.closest('form');
        if (!form) return;
        
        const formData = new FormData(form);
        formData.append('auto_save', '1');
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                this.showAutoSaveIndicator(element);
            }
        })
        .catch(error => {
            console.error('Auto-save error:', error);
        });
    }
    
    showAutoSaveIndicator(element) {
        const indicator = document.createElement('span');
        indicator.className = 'auto-save-indicator';
        indicator.textContent = '✓ Saved';
        indicator.style.cssText = `
            color: var(--success-green);
            font-size: 0.8rem;
            margin-left: 8px;
        `;
        
        const existingIndicator = element.parentNode.querySelector('.auto-save-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }
        
        element.parentNode.appendChild(indicator);
        
        setTimeout(() => {
            indicator.remove();
        }, 3000);
    }
    
    debounce(func, delay) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
    
    // Utility methods for data tables
    initDataTable(tableSelector, options = {}) {
        const table = document.querySelector(tableSelector);
        if (!table) return;
        
        // Add search functionality
        if (options.search !== false) {
            this.addTableSearch(table);
        }
        
        // Add sorting functionality
        if (options.sort !== false) {
            this.addTableSort(table);
        }
        
        // Add pagination
        if (options.pagination !== false) {
            this.addTablePagination(table, options.pageSize || 10);
        }
    }
    
    addTableSearch(table) {
        const searchContainer = document.createElement('div');
        searchContainer.className = 'table-search mb-3';
        searchContainer.innerHTML = `
            <input type="text" class="form-control" placeholder="Search..." style="max-width: 300px;">
        `;
        
        table.parentNode.insertBefore(searchContainer, table);
        
        const searchInput = searchContainer.querySelector('input');
        searchInput.addEventListener('input', (e) => {
            this.filterTable(table, e.target.value);
        });
    }
    
    filterTable(table, searchTerm) {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(searchTerm.toLowerCase());
            row.style.display = matches ? '' : 'none';
        });
    }
    
    addTableSort(table) {
        const headers = table.querySelectorAll('th');
        
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(table, index);
            });
        });
    }
    
    sortTable(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        const isAscending = !table.dataset.sortAsc || table.dataset.sortAsc === 'false';
        table.dataset.sortAsc = isAscending;
        
        rows.sort((a, b) => {
            const aText = a.cells[columnIndex].textContent.trim();
            const bText = b.cells[columnIndex].textContent.trim();
            
            // Try to parse as numbers
            const aNum = parseFloat(aText);
            const bNum = parseFloat(bText);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? aNum - bNum : bNum - aNum;
            }
            
            // String comparison
            return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText);
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.mcls = new MCLSApp();
});

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MCLSApp;
}