/**
 * Main JavaScript File for Application System
 * Contains all JavaScript functionality organized by page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Common functionality for all pages
    initializeTooltips();
    handleSidebarToggle();
    
    // Page-specific initialization
    const currentPage = window.location.pathname.split('/').pop();
    
    switch(currentPage) {
        case 'dashboard.php':
            initDashboard();
            break;
        case 'applications.php':
            initApplicationsPage();
            break;
        case 'enquiry_contact.php':
        case 'enquiry_service.php':
            initEnquiryPages();
            break;
        case 'profile.php':
            initProfilePage();
            break;
    }
});

/**
 * ===========================================
 * COMMON FUNCTIONS (Used across multiple pages)
 * ===========================================
 */

// Initialize Bootstrap tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Handle sidebar toggle for mobile view
function handleSidebarToggle() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
}

// Show toast notifications
function showToast(type, message) {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// Handle form submissions with AJAX
function handleFormSubmit(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message || 'Operation completed successfully!');
                if (typeof successCallback === 'function') {
                    successCallback(data);
                }
            } else {
                showToast('danger', data.message || 'An error occurred. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('danger', 'An error occurred. Please try again.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
}

/**
 * ===========================================
 * DASHBOARD PAGE (dashboard.php)
 * ===========================================
 */
function initDashboard() {
    // Update dashboard stats every 30 seconds
    const statsUpdateInterval = setInterval(updateDashboardStats, 30000);
    
    // Clean up interval when leaving the page
    window.addEventListener('beforeunload', function() {
        clearInterval(statsUpdateInterval);
    });
    
    // Initialize any dashboard-specific functionality
    initializeDashboardCharts();
}

function updateDashboardStats() {
    fetch('/application-system/ajax/get_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the stats cards
                document.querySelectorAll('[data-stat]').forEach(element => {
                    const statName = element.getAttribute('data-stat');
                    if (data[statName] !== undefined) {
                        element.textContent = data[statName];
                    }
                });
            }
        })
        .catch(error => console.error('Error updating dashboard stats:', error));
}

function initializeDashboardCharts() {
    // Initialize any charts on the dashboard
    const ctx = document.getElementById('dashboardChart');
    if (!ctx) return;
    
    // Example chart initialization
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Applications',
                data: [12, 19, 3, 5, 2, 3],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Monthly Applications'
                }
            }
        }
    });
}

/**
 * ===========================================
 * APPLICATIONS PAGE (applications.php)
 * ===========================================
 */
function initApplicationsPage() {
    // Initialize DataTable for applications
    const applicationsTable = $('#applicationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/application-system/ajax/get_applications.php',
            type: 'POST'
        },
        columns: [
            { data: 'id' },
            { data: 'applicant_name' },
            { data: 'service_type' },
            { 
                data: 'status',
                render: function(data, type, row) {
                    return `<span class="badge bg-${getStatusBadgeClass(data)}">${data}</span>`;
                }
            },
            { data: 'created_at' },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <a href="view_application.php?id=${row.id}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit_application.php?id=${row.id}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    `;
                }
            }
        ]
    });
    
    // Handle application status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            applicationsTable.column(3).search(this.value).draw();
        });
    }
}

/**
 * ===========================================
 * ENQUIRY PAGES (enquiry_contact.php, enquiry_service.php)
 * ===========================================
 */
function initEnquiryPages() {
    // Initialize DataTable for enquiries
    const enquiryTable = $('#enquiryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.location.href.includes('contact') ? 
                 '/application-system/ajax/get_contact_enquiries.php' : 
                 '/application-system/ajax/get_service_enquiries.php',
            type: 'POST'
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'email' },
            { data: 'phone' },
            { data: 'message', width: '30%' },
            { data: 'created_at' },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-outline-primary view-enquiry" data-id="${row.id}" data-bs-toggle="tooltip" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-enquiry" data-id="${row.id}" data-bs-toggle="tooltip" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }
            }
        ]
    });
    
    // Handle view enquiry modal
    $(document).on('click', '.view-enquiry', function() {
        const enquiryId = $(this).data('id');
        // Fetch enquiry details and show in modal
        fetch(`/application-system/ajax/get_enquiry_details.php?id=${enquiryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate modal with enquiry data
                    Object.keys(data.enquiry).forEach(key => {
                        const element = document.getElementById(`enquiry-${key}`);
                        if (element) {
                            element.textContent = data.enquiry[key] || 'N/A';
                        }
                    });
                    
                    // Show the modal
                    const viewModal = new bootstrap.Modal(document.getElementById('viewEnquiryModal'));
                    viewModal.show();
                } else {
                    showToast('danger', data.message || 'Failed to load enquiry details.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('danger', 'An error occurred while loading enquiry details.');
            });
    });
    
    // Handle delete enquiry
    $(document).on('click', '.delete-enquiry', function() {
        if (!confirm('Are you sure you want to delete this enquiry? This action cannot be undone.')) {
            return;
        }
        
        const enquiryId = $(this).data('id');
        const row = $(this).closest('tr');
        
        fetch('/application-system/ajax/delete_enquiry.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: enquiryId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Enquiry deleted successfully!');
                enquiryTable.row(row).remove().draw(false);
            } else {
                showToast('danger', data.message || 'Failed to delete enquiry.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('danger', 'An error occurred while deleting the enquiry.');
        });
    });
}

/**
 * ===========================================
 * PROFILE PAGE (profile.php)
 * ===========================================
 */
function initProfilePage() {
    // Handle profile picture upload
    const profilePicInput = document.getElementById('profilePicInput');
    const profilePicPreview = document.getElementById('profilePicPreview');
    
    if (profilePicInput && profilePicPreview) {
        profilePicInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePicPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                // Auto-submit the form when a file is selected
                document.getElementById('profilePicForm').submit();
            }
        });
    }
    
    // Handle profile form submission
    handleFormSubmit('profileForm', function(data) {
        // Update the displayed name if it was changed
        if (data.user) {
            const nameElements = document.querySelectorAll('.profile-name');
            nameElements.forEach(el => {
                el.textContent = data.user.name;
            });
        }
    });
    
    // Handle password change form submission
    handleFormSubmit('changePasswordForm');
}

/**
 * ===========================================
 * UTILITY FUNCTIONS
 * ===========================================
 */

// Get appropriate badge class based on status
function getStatusBadgeClass(status) {
    const statusMap = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'danger',
        'in review': 'info',
        'completed': 'primary',
        'cancelled': 'secondary'
    };
    
    return statusMap[status.toLowerCase()] || 'secondary';
}

// Format date to a more readable format
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Debounce function to limit how often a function can be called
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

// Throttle function to limit the rate at which a function can fire
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}
