// Admin Panel JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize admin panel functionality
    initializeAdminPanel();
});

function initializeAdminPanel() {
    // Add loading states to forms
    setupFormLoading();
    
    // Setup file upload previews
    setupFileUploads();
    
    // Setup search and filtering
    setupSearchAndFilter();
    
    // Setup responsive navigation
    setupResponsiveNav();
    
    // Setup auto-refresh for dashboard
    setupAutoRefresh();
    
    // Setup confirmation dialogs
    setupConfirmations();
}

// Form Loading States
function setupFormLoading() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
                
                // Re-enable button after 5 seconds (fallback)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    });
}

// File Upload Previews
function setupFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                showFilePreview(file, this);
            }
        });
    });
}

function showFilePreview(file, input) {
    // Remove existing preview
    const existingPreview = input.parentNode.querySelector('.file-preview');
    if (existingPreview) {
        existingPreview.remove();
    }
    
    // Create preview element
    const preview = document.createElement('div');
    preview.className = 'file-preview';
    preview.style.cssText = `
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    `;
    
    const fileInfo = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-file" style="color: #007bff;"></i>
            <div>
                <strong>${file.name}</strong><br>
                <small>Size: ${formatFileSize(file.size)} | Type: ${file.type || 'Unknown'}</small>
            </div>
        </div>
    `;
    
    preview.innerHTML = fileInfo;
    input.parentNode.appendChild(preview);
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + sizes[i];
}

// Search and Filter Functionality
function setupSearchAndFilter() {
    const searchInputs = document.querySelectorAll('input[placeholder*="Search"]');
    const categoryFilters = document.querySelectorAll('select[id*="category"]');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(function() {
            performSearch(this.value, this.dataset.target);
        }, 300));
    });
    
    categoryFilters.forEach(filter => {
        filter.addEventListener('change', function() {
            performCategoryFilter(this.value, this.dataset.target);
        });
    });
}

function performSearch(query, target) {
    const searchableElements = document.querySelectorAll(target || '.searchable');
    
    searchableElements.forEach(element => {
        const text = element.textContent.toLowerCase();
        const matches = text.includes(query.toLowerCase());
        element.style.display = matches ? '' : 'none';
    });
}

function performCategoryFilter(category, target) {
    const filterableElements = document.querySelectorAll(target || '[data-category]');
    
    filterableElements.forEach(element => {
        if (category === 'all' || element.dataset.category === category) {
            element.style.display = '';
        } else {
            element.style.display = 'none';
        }
    });
}

// Debounce function for search
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

// Responsive Navigation
function setupResponsiveNav() {
    const navToggle = document.getElementById('mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('open');
            navToggle.classList.toggle('open');
        });
    
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (navMenu.classList.contains('open') && !event.target.closest('.admin-nav')) {
                navMenu.classList.remove('open');
                navToggle.classList.remove('open');
            }
        });
    }
}

// Auto-refresh Dashboard
function setupAutoRefresh() {
    // Only auto-refresh on dashboard page
    if (window.location.pathname.includes('dashboard.php')) {
        setInterval(refreshDashboardStats, 30000); // Refresh every 30 seconds
    }
}

function refreshDashboardStats() {
    // Refresh statistics without full page reload
    fetch('dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            updateDashboardStats(data);
        })
        .catch(error => {
            console.log('Dashboard refresh failed:', error);
        });
}

function updateDashboardStats(data) {
    // Update stat cards
    const statCards = document.querySelectorAll('.stat-card h3');
    if (data.stats && statCards.length >= 4) {
        statCards[0].textContent = data.stats.apk_files || '0';
        statCards[1].textContent = data.stats.total_videos || '0';
        statCards[2].textContent = data.stats.total_downloads || '0';
        statCards[3].textContent = data.stats.latest_version || 'Unknown';
    }
    
    // Update recent activity
    if (data.recent_activity) {
        updateRecentActivity(data.recent_activity);
    }
}

function updateRecentActivity(activities) {
    const activityList = document.querySelector('.activity-list');
    if (activityList && activities.length > 0) {
        const newActivities = activities.map(activity => `
            <div class="activity-item">
                <i class="fas fa-info-circle"></i>
                <span>${activity}</span>
            </div>
        `).join('');
        
        activityList.innerHTML = newActivities;
    }
}

// Confirmation Dialogs
function setupConfirmations() {
    const deleteButtons = document.querySelectorAll('button[class*="btn-danger"], input[type="submit"][value*="delete"]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            if (!confirm('Are you sure you want to perform this action? This cannot be undone.')) {
                event.preventDefault();
                return false;
            }
        });
    });
}

// Enhanced File Upload with Progress
function setupEnhancedUploads() {
    const uploadForms = document.querySelectorAll('form[enctype="multipart/form-data"]');
    
    uploadForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            const fileInput = form.querySelector('input[type="file"]');
            if (fileInput && fileInput.files.length > 0) {
                showUploadProgress();
            }
        });
    });
}

function showUploadProgress() {
    // Create progress overlay
    const progressOverlay = document.createElement('div');
    progressOverlay.className = 'upload-progress-overlay';
    progressOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;
    
    progressOverlay.innerHTML = `
        <div class="upload-progress-content" style="
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        ">
            <i class="fas fa-upload" style="font-size: 3rem; color: #007bff; margin-bottom: 20px;"></i>
            <h3>Uploading Files...</h3>
            <div class="progress-bar" style="
                width: 100%;
                height: 20px;
                background: #e9ecef;
                border-radius: 10px;
                overflow: hidden;
                margin: 20px 0;
            ">
                <div class="progress-fill" style="
                    height: 100%;
                    background: #007bff;
                    width: 0%;
                    transition: width 0.3s ease;
                "></div>
            </div>
            <p class="progress-text">Preparing upload...</p>
        </div>
    `;
    
    document.body.appendChild(progressOverlay);
    
    // Simulate progress (in real implementation, use XMLHttpRequest or Fetch with progress)
    let progress = 0;
    const progressFill = progressOverlay.querySelector('.progress-fill');
    const progressText = progressOverlay.querySelector('.progress-text');
    
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);
            setTimeout(() => {
                document.body.removeChild(progressOverlay);
            }, 1000);
        }
        
        progressFill.style.width = progress + '%';
        progressText.textContent = `Uploading... ${Math.round(progress)}%`;
    }, 200);
}

// Data Table Enhancements
function setupDataTables() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        // Add sorting functionality
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(table, Array.from(table.querySelectorAll('th')).indexOf(this));
            });
        });
        
        // Add pagination if needed
        if (table.rows.length > 10) {
            addPagination(table);
        }
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Try to sort as numbers first
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return aNum - bNum;
        }
        
        // Fall back to string comparison
        return aValue.localeCompare(bValue);
    });
    
    // Clear and re-add sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

function addPagination(table) {
    const rowsPerPage = 10;
    const totalRows = table.querySelectorAll('tbody tr').length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    
    if (totalPages <= 1) return;
    
    // Create pagination controls
    const pagination = document.createElement('div');
    pagination.className = 'table-pagination';
    pagination.style.cssText = `
        margin-top: 20px;
        text-align: center;
    `;
    
    let paginationHTML = '<div class="pagination-controls">';
    for (let i = 1; i <= totalPages; i++) {
        paginationHTML += `<button class="page-btn" data-page="${i}">${i}</button>`;
    }
    paginationHTML += '</div>';
    
    pagination.innerHTML = paginationHTML;
    table.parentNode.appendChild(pagination);
    
    // Show first page by default
    showPage(table, 1);
    
    // Add click handlers
    pagination.addEventListener('click', function(event) {
        if (event.target.classList.contains('page-btn')) {
            const page = parseInt(event.target.dataset.page);
            showPage(table, page);
            
            // Update active page button
            pagination.querySelectorAll('.page-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }
    });
}

function showPage(table, page) {
    const rows = table.querySelectorAll('tbody tr');
    const rowsPerPage = 10;
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    
    rows.forEach((row, index) => {
        if (index >= start && index < end) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Utility Functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
        color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
        border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (document.body.contains(notification)) {
            document.body.removeChild(notification);
        }
    }, 5000);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

// Export functionality
function exportTableData(tableId, format = 'csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = Array.from(table.querySelectorAll('tr'));
    let data = [];
    
    // Get headers
    const headers = Array.from(rows[0].querySelectorAll('th')).map(th => th.textContent.trim());
    data.push(headers);
    
    // Get data rows
    for (let i = 1; i < rows.length; i++) {
        const row = Array.from(rows[i].querySelectorAll('td')).map(td => td.textContent.trim());
        data.push(row);
    }
    
    if (format === 'csv') {
        exportCSV(data);
    } else if (format === 'json') {
        exportJSON(data, headers);
    }
}

function exportCSV(data) {
    const csvContent = data.map(row => 
        row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',')
    ).join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'export.csv';
    link.click();
}

function exportJSON(data, headers) {
    const jsonData = data.slice(1).map(row => {
        const obj = {};
        headers.forEach((header, index) => {
            obj[header] = row[index];
        });
        return obj;
    });
    
    const blob = new Blob([JSON.stringify(jsonData, null, 2)], { type: 'application/json' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'export.json';
    link.click();
}

// Initialize all functionality when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeAdminPanel();
    setupEnhancedUploads();
    setupDataTables();
    
    // Add keyboard shortcuts
    setupKeyboardShortcuts();
});

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(event) {
        // Ctrl/Cmd + S to save forms
        if ((event.ctrlKey || event.metaKey) && event.key === 's') {
            event.preventDefault();
            const activeForm = document.querySelector('form:focus-within');
            if (activeForm) {
                activeForm.submit();
            }
        }
        
        // Ctrl/Cmd + K to focus search
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            event.preventDefault();
            const searchInput = document.querySelector('input[placeholder*="Search"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape to close modals
        if (event.key === 'Escape') {
            const modal = document.querySelector('.modal');
            if (modal && modal.style.display !== 'none') {
                modal.style.display = 'none';
            }
        }
    });
}
