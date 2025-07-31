// Casino Platform JavaScript

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltip initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-dismiss alerts
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Form validation enhancement
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Number input formatting
    var numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(function(input) {
        input.addEventListener('input', function(e) {
            if (this.value < 0) {
                this.value = 0;
            }
        });
    });
    
    // File upload preview
    var fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    // Create preview if it's an image
                    if (file.type.startsWith('image/')) {
                        var preview = document.createElement('img');
                        preview.src = e.target.result;
                        preview.style.maxWidth = '200px';
                        preview.style.maxHeight = '200px';
                        preview.style.marginTop = '10px';
                        preview.className = 'img-thumbnail';
                        
                        // Remove existing preview
                        var existingPreview = input.parentNode.querySelector('.file-preview');
                        if (existingPreview) {
                            existingPreview.remove();
                        }
                        
                        // Add new preview
                        var previewContainer = document.createElement('div');
                        previewContainer.className = 'file-preview';
                        previewContainer.appendChild(preview);
                        input.parentNode.appendChild(previewContainer);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Copy to clipboard functionality
    window.copyToClipboard = function(text, button) {
        navigator.clipboard.writeText(text).then(function() {
            // Visual feedback
            var originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copied!';
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-casino');
            
            setTimeout(function() {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-casino');
            }, 2000);
        });
    };
    
    // Real-time balance validation for withdrawal
    var withdrawalAmountInput = document.getElementById('withdrawal-amount');
    var maxBalance = parseFloat(document.querySelector('[data-max-balance]')?.dataset.maxBalance || 0);
    
    if (withdrawalAmountInput && maxBalance > 0) {
        withdrawalAmountInput.addEventListener('input', function() {
            var amount = parseFloat(this.value);
            var feedback = document.getElementById('withdrawal-feedback');
            
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.id = 'withdrawal-feedback';
                feedback.className = 'form-text';
                this.parentNode.appendChild(feedback);
            }
            
            if (amount > maxBalance) {
                this.classList.add('is-invalid');
                feedback.className = 'form-text text-danger';
                feedback.textContent = 'Amount exceeds available balance';
            } else if (amount > 0) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                feedback.className = 'form-text text-success';
                feedback.textContent = 'Valid amount';
            } else {
                this.classList.remove('is-invalid', 'is-valid');
                feedback.textContent = '';
            }
        });
    }
    
    // Dynamic payment method selection
    var paymentMethodSelect = document.getElementById('payment_method');
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            var selectedMethod = this.value;
            var methodCards = document.querySelectorAll('.payment-method');
            
            methodCards.forEach(function(card) {
                if (selectedMethod && card.querySelector('h6').textContent.includes(selectedMethod)) {
                    card.style.display = 'block';
                    card.classList.add('highlight');
                } else {
                    card.classList.remove('highlight');
                }
            });
        });
    }
    
    // Auto-refresh for admin dashboard
    if (window.location.pathname.includes('/admin/dashboard')) {
        setInterval(function() {
            // Refresh only the stats without full page reload
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    
                    // Update stat cards
                    var statCards = document.querySelectorAll('.card .h5');
                    var newStatCards = doc.querySelectorAll('.card .h5');
                    
                    statCards.forEach(function(card, index) {
                        if (newStatCards[index]) {
                            card.textContent = newStatCards[index].textContent;
                        }
                    });
                })
                .catch(error => console.log('Auto-refresh failed:', error));
        }, 30000); // Refresh every 30 seconds
    }
    
    // Confirm dialogs for destructive actions
    var dangerButtons = document.querySelectorAll('.btn-danger, .btn-outline-danger');
    dangerButtons.forEach(function(button) {
        if (button.textContent.toLowerCase().includes('delete') || 
            button.textContent.toLowerCase().includes('reject') ||
            button.textContent.toLowerCase().includes('ban')) {
            
            button.addEventListener('click', function(e) {
                var action = this.textContent.trim();
                if (!confirm('Are you sure you want to ' + action.toLowerCase() + '? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        }
    });
    
    // Enhanced table sorting
    var sortableHeaders = document.querySelectorAll('th[data-sort]');
    sortableHeaders.forEach(function(header) {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            var table = this.closest('table');
            var tbody = table.querySelector('tbody');
            var rows = Array.from(tbody.querySelectorAll('tr'));
            var columnIndex = Array.from(this.parentNode.children).indexOf(this);
            var sortDirection = this.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
            
            rows.sort(function(a, b) {
                var aVal = a.children[columnIndex].textContent.trim();
                var bVal = b.children[columnIndex].textContent.trim();
                
                // Try to parse as numbers
                var aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
                var bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
                } else {
                    return sortDirection === 'asc' ? 
                        aVal.localeCompare(bVal) : 
                        bVal.localeCompare(aVal);
                }
            });
            
            // Clear tbody and re-append sorted rows
            tbody.innerHTML = '';
            rows.forEach(function(row) {
                tbody.appendChild(row);
            });
            
            // Update sort direction
            this.dataset.sortDirection = sortDirection;
            
            // Update visual indicators
            sortableHeaders.forEach(function(h) {
                h.classList.remove('sort-asc', 'sort-desc');
            });
            this.classList.add('sort-' + sortDirection);
        });
    });
});

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function showNotification(message, type = 'info') {
    var notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(function() {
        notification.remove();
    }, 5000);
}

// Loading state management
function showLoading(element) {
    if (element) {
        element.disabled = true;
        var originalText = element.innerHTML;
        element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        element.dataset.originalText = originalText;
    }
}

function hideLoading(element) {
    if (element && element.dataset.originalText) {
        element.disabled = false;
        element.innerHTML = element.dataset.originalText;
        delete element.dataset.originalText;
    }
}

// Form submission with loading state
document.addEventListener('submit', function(e) {
    var submitButton = e.target.querySelector('button[type="submit"]');
    if (submitButton) {
        showLoading(submitButton);
    }
});
