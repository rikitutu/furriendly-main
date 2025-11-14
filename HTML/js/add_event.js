// ===== ADD EVENT FUNCTIONALITY =====

// Service management
let selectedServices = [];

// Initialize service buttons
document.addEventListener('DOMContentLoaded', function() {
    initializeServiceButtons();
    updateServicesList();
    updateHiddenServicesField();
});

// Initialize service button event listeners
function initializeServiceButtons() {
    const serviceButtons = document.querySelectorAll('.service-btn');
    serviceButtons.forEach(button => {
        button.addEventListener('click', function() {
            toggleService(this.dataset.service, this);
        });
    });
}

// Toggle service selection
function toggleService(service, button) {
    const index = selectedServices.indexOf(service);
    
    if (index === -1) {
        // Add service
        selectedServices.push(service);
        button.classList.add('active');
    } else {
        // Remove service
        selectedServices.splice(index, 1);
        button.classList.remove('active');
    }
    
    updateServicesList();
    updateHiddenServicesField();
}

// Add custom service
function addCustomService() {
    const customServiceInput = document.getElementById('custom_service');
    const serviceName = customServiceInput.value.trim();
    
    if (serviceName && !selectedServices.includes(serviceName)) {
        selectedServices.push(serviceName);
        customServiceInput.value = '';
        updateServicesList();
        updateHiddenServicesField();
        
        // Show success feedback
        showNotification('Service added successfully!', 'success');
    } else if (selectedServices.includes(serviceName)) {
        showNotification('Service already added!', 'error');
    }
}

// Remove service
function removeService(service) {
    const index = selectedServices.indexOf(service);
    if (index !== -1) {
        selectedServices.splice(index, 1);
        
        // Also remove active class from corresponding button
        const serviceButtons = document.querySelectorAll('.service-btn');
        serviceButtons.forEach(button => {
            if (button.dataset.service === service) {
                button.classList.remove('active');
            }
        });
        
        updateServicesList();
        updateHiddenServicesField();
    }
}

// Update services list display
function updateServicesList() {
    const servicesList = document.getElementById('services-list');
    
    if (selectedServices.length === 0) {
        servicesList.innerHTML = '<div class="no-services">No services selected yet</div>';
        return;
    }
    
    servicesList.innerHTML = selectedServices.map(service => `
        <div class="service-tag">
            ${service}
            <button type="button" class="remove-service" onclick="removeService('${service}')">×</button>
        </div>
    `).join('');
}

// Update hidden services field
function updateHiddenServicesField() {
    const servicesData = document.getElementById('services_data');
    servicesData.value = JSON.stringify(selectedServices);
    
    // Validate services
    validateServices();
}

// Validate that at least one service is selected
function validateServices() {
    const servicesList = document.getElementById('services-list');
    if (selectedServices.length === 0) {
        servicesList.style.borderColor = '#dc3545';
    } else {
        servicesList.style.borderColor = '#a7e7ff';
    }
}

// Phone number formatting
function formatPhoneNumber() {
    const countryCode = document.getElementById('country_code').value;
    const phoneInput = document.getElementById('contact_number');
    const phoneFormat = document.getElementById('phone-format');
    let phoneNumber = phoneInput.value.replace(/\D/g, ''); // Remove non-digits
    
    // Update format display based on country code
    const formatExamples = {
        '+1': 'Format: (XXX) XXX-XXXX',
        '+44': 'Format: XXXX XXX XXXX',
        '+63': 'Format: XXX XXX XXXX',
        '+81': 'Format: XX XXXX XXXX',
        '+82': 'Format: XX XXXX XXXX',
        '+65': 'Format: XXXX XXXX',
        '+60': 'Format: XX XXXX XXXX',
        '+66': 'Format: XX XXX XXXX',
        '+84': 'Format: XX XXXX XXXX',
        '+62': 'Format: XX XXXX XXXX',
        '+91': 'Format: XXXX XXXXXX',
        '+971': 'Format: XX XXX XXXX',
        '+966': 'Format: XX XXX XXXX',
        '+61': 'Format: X XXX XXX XXX'
    };
    
    phoneFormat.textContent = formatExamples[countryCode] || 'Format: Enter phone number';
    
    // Apply formatting based on country code
    let formattedNumber = phoneNumber;
    
    switch (countryCode) {
        case '+1': // US/Canada
            if (phoneNumber.length > 3 && phoneNumber.length <= 6) {
                formattedNumber = `(${phoneNumber.slice(0, 3)}) ${phoneNumber.slice(3)}`;
            } else if (phoneNumber.length > 6) {
                formattedNumber = `(${phoneNumber.slice(0, 3)}) ${phoneNumber.slice(3, 6)}-${phoneNumber.slice(6, 10)}`;
            }
            break;
            
        case '+44': // UK
            if (phoneNumber.length > 4 && phoneNumber.length <= 7) {
                formattedNumber = `${phoneNumber.slice(0, 4)} ${phoneNumber.slice(4)}`;
            } else if (phoneNumber.length > 7) {
                formattedNumber = `${phoneNumber.slice(0, 4)} ${phoneNumber.slice(4, 7)} ${phoneNumber.slice(7, 11)}`;
            }
            break;
            
        case '+63': // Philippines
            if (phoneNumber.length > 3 && phoneNumber.length <= 6) {
                formattedNumber = `${phoneNumber.slice(0, 3)} ${phoneNumber.slice(3)}`;
            } else if (phoneNumber.length > 6) {
                formattedNumber = `${phoneNumber.slice(0, 3)} ${phoneNumber.slice(3, 6)} ${phoneNumber.slice(6, 10)}`;
            }
            break;
            
        case '+65': // Singapore
            if (phoneNumber.length > 4) {
                formattedNumber = `${phoneNumber.slice(0, 4)} ${phoneNumber.slice(4, 8)}`;
            }
            break;
            
        default:
            // Generic formatting for other countries
            if (phoneNumber.length > 4) {
                formattedNumber = phoneNumber.replace(/(\d{2})(?=\d)/g, '$1 ');
            }
    }
    
    phoneInput.value = formattedNumber;
}

// Toggle ID upload section based on position
function toggleIdUpload() {
    const position = document.getElementById('position').value;
    const idUploadSection = document.getElementById('id-upload-section');
    
    if (position === 'Government Official') {
        idUploadSection.style.display = 'block';
    } else {
        idUploadSection.style.display = 'none';
        // Clear the file input if hidden
        document.getElementById('id_upload').value = '';
        document.getElementById('id_upload_name').textContent = 'No file chosen';
    }
}

// Update file name display
function updateFileName(inputId, spanId) {
    const fileInput = document.getElementById(inputId);
    const fileNameSpan = document.getElementById(spanId);
    
    if (fileInput.files.length > 0) {
        fileNameSpan.textContent = fileInput.files[0].name;
        fileNameSpan.style.color = '#28a745';
        fileNameSpan.style.fontWeight = '600';
    } else {
        fileNameSpan.textContent = 'No file chosen';
        fileNameSpan.style.color = '#666';
        fileNameSpan.style.fontWeight = 'normal';
    }
}

// Form validation
document.getElementById('eventForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Validate services
    if (selectedServices.length === 0) {
        showNotification('Please select at least one service', 'error');
        isValid = false;
    }
    
    // Validate phone number
    const phoneNumber = document.getElementById('contact_number').value.replace(/\D/g, '');
    const countryCode = document.getElementById('country_code').value;
    
    if (!isValidPhoneNumber(phoneNumber, countryCode)) {
        showNotification('Please enter a valid phone number', 'error');
        isValid = false;
    }
    
    // Validate date (must be in future)
    const eventDate = new Date(document.getElementById('event_date').value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (eventDate <= today) {
        showNotification('Event date must be in the future', 'error');
        isValid = false;
    }
    
    // Validate time (end time must be after start time)
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (startTime >= endTime) {
        showNotification('End time must be after start time', 'error');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
    }
});

// Phone number validation based on country
function isValidPhoneNumber(phoneNumber, countryCode) {
    const minLengths = {
        '+1': 10, // US/Canada
        '+44': 10, // UK
        '+63': 10, // Philippines
        '+81': 10, // Japan
        '+82': 10, // South Korea
        '+65': 8,  // Singapore
        '+60': 9,  // Malaysia
        '+66': 9,  // Thailand
        '+84': 9,  // Vietnam
        '+62': 9,  // Indonesia
        '+91': 10, // India
        '+971': 9, // UAE
        '+966': 9, // Saudi Arabia
        '+61': 9   // Australia
    };
    
    const minLength = minLengths[countryCode] || 8;
    return phoneNumber.length >= minLength;
}

// Notification system
function showNotification(message, type) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.form-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create new notification
    const notification = document.createElement('div');
    notification.className = `form-notification ${type}`;
    notification.innerHTML = `
        <span class="notification-icon">${type === 'success' ? '✅' : '❌'}</span>
        <span class="notification-message">${message}</span>
    `;
    
    // Add styles if not already added
    if (!document.querySelector('#notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .form-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 10px;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease-out;
            }
            .form-notification.success {
                background: #27ae60;
                border-left: 4px solid #2ecc71;
            }
            .form-notification.error {
                background: #e74c3c;
                border-left: 4px solid #c0392b;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Allow Enter key to add custom service
document.getElementById('custom_service').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addCustomService();
    }
});

// Initialize the form
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('event_date').min = today;
    
    // Initialize phone format
    formatPhoneNumber();
});