// Simple working version of your_pet.js

// Global variables
let currentPetId = null;

// Delete pet function
function deletePet(petId) {
    if (confirm('Are you sure you want to delete this pet? This action cannot be undone.')) {
        fetch('../php/delete_pet.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'pet_id=' + petId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Pet deleted successfully', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error: ' + error.message, 'error');
        });
    }
}

// Medical Documents Functions
function viewMedicalDocuments(petId, petName) {
    document.getElementById('modalPetName').textContent = petName;
    document.getElementById('medicalDocumentsModal').style.display = 'block';
    
    // Show loading
    document.getElementById('documentsContainer').innerHTML = `
        <div class="loading">
            <div class="loading-spinner"></div>
            <p>Loading medical documents...</p>
        </div>
    `;
    
    // Load both general and event-specific documents
    fetch(`../php/get_pet_record.php?pet_id=${petId}&event_id=0`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAllMedicalDocuments(data.records, petName);
            } else {
                throw new Error(data.message || 'Failed to load documents');
            }
        })
        .catch(error => {
            document.getElementById('documentsContainer').innerHTML = `
                <div class="error">
                    <p>Error loading documents: ${error.message}</p>
                </div>
            `;
        });
}

function displayAllMedicalDocuments(records, petName) {
    const container = document.getElementById('documentsContainer');
    
    if (!records || records.length === 0) {
        container.innerHTML = `
            <div class="no-documents">
                <div class="no-documents-icon">📄</div>
                <h4>No Medical Documents</h4>
                <p>No medical documents found for ${escapeHtml(petName)}.</p>
                <p><small>Documents uploaded by event hosts will appear here.</small></p>
            </div>
        `;
        return;
    }
    
    // Separate records by type
    const hostUploads = records.filter(r => r.is_host_upload == 1 || (r.event_id && r.event_id > 0));
    const personalUploads = records.filter(r => !r.is_host_upload && (!r.event_id || r.event_id == 0));
    
    let html = `
        <div class="documents-header">
            <h4>Medical Documents for ${escapeHtml(petName)}</h4>
            <p class="documents-count">Total: ${records.length} document(s) 
                ${hostUploads.length > 0 ? `(${hostUploads.length} from events)` : ''}
            </p>
        </div>
    `;
    
    // Display host uploads first if any
    if (hostUploads.length > 0) {
        html += `
            <div class="documents-section">
                <h5 class="section-title">📋 Event Medical Records (${hostUploads.length})</h5>
                <p class="section-description">Records uploaded by event hosts after your pet participated in events</p>
                <div class="documents-list">
        `;
        
        hostUploads.forEach(record => {
            html += generateDocumentHTML(record, true);
        });
        
        html += `</div></div>`;
    }
    
    // Display personal uploads if any
    if (personalUploads.length > 0) {
        html += `
            <div class="documents-section">
                <h5 class="section-title">📁 Personal Medical Documents (${personalUploads.length})</h5>
                <p class="section-description">Documents you uploaded for your pet</p>
                <div class="documents-list">
        `;
        
        personalUploads.forEach(record => {
            html += generateDocumentHTML(record, false);
        });
        
        html += `</div></div>`;
    }
    
    container.innerHTML = html;
}

function generateDocumentHTML(record, isHostUpload) {
    const uploadDate = new Date(record.uploaded_at);
    const formattedDate = uploadDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const recordTypeDisplay = {
        'vaccination': '💉 Vaccination',
        'medication': '💊 Medication',
        'treatment': '🏥 Treatment',
        'checkup': '🩺 Checkup',
        'other': '📄 Medical Record'
    };
    
    const displayType = recordTypeDisplay[record.record_type] || recordTypeDisplay['other'];
    
    return `
        <div class="document-item ${isHostUpload ? 'host-upload' : 'personal-upload'}">
            <div class="document-info">
                <div class="document-type-badge">
                    <span class="record-type">${displayType}</span>
                    ${isHostUpload ? '<span class="host-badge">👑 Host Upload</span>' : ''}
                    ${record.event_id ? `<span class="event-badge">🎯 ${escapeHtml(record.event_title || 'Event #' + record.event_id)}</span>` : ''}
                </div>
                <div class="document-description">${escapeHtml(record.description || 'No description provided')}</div>
                <div class="document-meta">
                    <span class="document-date">📅 ${formattedDate}</span>
                    <span class="document-size">📦 ${formatFileSize(record.file_size || 0)}</span>
                    <span class="document-uploader">👤 Uploaded by: ${escapeHtml(record.uploaded_by || 'Unknown')}</span>
                </div>
            </div>
            <div class="document-actions">
                <a href="../php/download_record.php?record_id=${record.id}" class="download-btn" target="_blank">
                    📥 Download
                </a>
                ${!isHostUpload ? `<button class="delete-btn" onclick="deleteMedicalDocument(${record.id})">🗑️ Delete</button>` : ''}
            </div>
        </div>
    `;
}

function openUploadModal(petId, petName) {
    currentPetId = petId;
    document.getElementById('uploadPetName').textContent = petName;
    document.getElementById('uploadPetId').value = petId;
    document.getElementById('documentFileName').innerHTML = '';
    document.getElementById('uploadDocumentModal').style.display = 'block';
}

function closeMedicalDocumentsModal() {
    document.getElementById('medicalDocumentsModal').style.display = 'none';
}

function closeUploadDocumentModal() {
    document.getElementById('uploadDocumentModal').style.display = 'none';
}

function deleteMedicalDocument(documentId) {
    if (confirm('Delete this document?')) {
        fetch('../php/delete_medical_document.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'document_id=' + documentId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Document deleted', 'success');
                // Refresh the documents view
                const petName = document.getElementById('modalPetName').textContent;
                viewMedicalDocuments(currentPetId, petName);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        });
    }
}

// Upload document function with better error handling
function uploadDocument() {
    const fileInput = document.getElementById('medicalDocument');
    const description = document.getElementById('documentDescription').value;
    const petId = document.getElementById('uploadPetId').value;

    if (!fileInput.files.length) {
        showNotification('Please select a PDF file', 'error');
        return;
    }

    const file = fileInput.files[0];
    if (file.type !== 'application/pdf') {
        showNotification('Please select a PDF file', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('pet_id', petId);
    formData.append('description', description);
    formData.append('medical_document', file);

    // Show loading
    const button = document.querySelector('#uploadDocumentModal .submit-btn');
    const originalText = button.textContent;
    button.textContent = 'Uploading...';
    button.disabled = true;

    fetch('../php/upload_medical_document.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // First get the response as text to see what we're dealing with
        return response.text().then(text => {
            try {
                // Try to parse as JSON
                return JSON.parse(text);
            } catch (e) {
                // If not JSON, throw error with the actual response
                throw new Error('Server returned non-JSON: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        if (data.success) {
            showNotification('Document uploaded successfully!', 'success');
            closeUploadDocumentModal();
            // Reset form
            fileInput.value = '';
            document.getElementById('documentDescription').value = '';
            document.getElementById('documentFileName').innerHTML = '';
        } else {
            throw new Error(data.message || 'Upload failed');
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        showNotification('Upload failed: ' + error.message, 'error');
    })
    .finally(() => {
        button.textContent = originalText;
        button.disabled = false;
    });
}

// Initialize file upload area
document.addEventListener('DOMContentLoaded', function() {
    // Initialize document upload area
    const documentUploadArea = document.getElementById('documentUploadArea');
    const medicalDocumentInput = document.getElementById('medicalDocument');
    const documentFileName = document.getElementById('documentFileName');

    if (documentUploadArea && medicalDocumentInput) {
        documentUploadArea.addEventListener('click', function() {
            medicalDocumentInput.click();
        });

        medicalDocumentInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                if (file.type !== 'application/pdf') {
                    showNotification('Please select a PDF file', 'error');
                    this.value = '';
                    documentFileName.innerHTML = '';
                    return;
                }

                if (file.size > 10 * 1024 * 1024) { // 10MB limit
                    showNotification('File size must be less than 10MB', 'error');
                    this.value = '';
                    documentFileName.innerHTML = '';
                    return;
                }

                documentFileName.innerHTML = `
                    <div class="file-selected">
                        <span class="file-icon">📄</span>
                        <span class="file-name">${escapeHtml(file.name)}</span>
                        <span class="file-size">(${formatFileSize(file.size)})</span>
                    </div>
                `;
            }
        });
    }

    const uploadForm = document.getElementById('uploadRecordsForm');
    
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const eventId = document.getElementById('uploadEventId').value;
            const petId = document.getElementById('uploadPetId').value;
            const participantId = document.getElementById('uploadParticipantId').value;
            
            console.log('Form submission data:', { eventId, petId, participantId });
            
            // Enhanced validation
            if ((!petId || petId <= 0) && (!participantId || participantId <= 0)) {
                showNotification('Error: Cannot determine which pet to upload records for. Please ensure the participant has a registered pet.', 'error');
                return;
            }
            
            const formData = new FormData(this);
            
            // Show loading state on the submit button
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading-text">⏳ Uploading...</span>';
            submitBtn.disabled = true;
            
            // Also disable the close button and show loading in modal
            const closeBtn = document.querySelector('#uploadRecordsModal .close-modal');
            if (closeBtn) closeBtn.style.pointerEvents = 'none';
            
            console.log('Starting upload...');
            
            fetch('../php/upload_pet_record.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response received:', response);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Upload response:', data);
                if (data.success) {
                    showNotification(data.message || 'Record uploaded successfully!', 'success');
                    
                    // Reset form but keep modal open
                    this.reset();
                    document.getElementById('fileNameDisplay').innerHTML = '';
                    
                    // Reload records list to show the new record
                    setTimeout(() => {
                        loadExistingRecords(eventId, petId);
                    }, 500);
                    
                } else {
                    throw new Error(data.message || 'Upload failed');
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                showNotification('Error uploading record: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                if (closeBtn) closeBtn.style.pointerEvents = 'auto';
            });
        });
    }
    
    // File upload area handling
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('record_file');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    
    if (fileUploadArea && fileInput) {
        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                if (file.type !== 'application/pdf') {
                    showNotification('Please upload a PDF file only', 'error');
                    this.value = '';
                    fileNameDisplay.innerHTML = '';
                    return;
                }
                
                if (file.size > 10 * 1024 * 1024) { // 10MB limit
                    showNotification('File size must be less than 10MB', 'error');
                    this.value = '';
                    fileNameDisplay.innerHTML = '';
                    return;
                }
                
                fileNameDisplay.innerHTML = `
                    <div class="file-selected">
                        <span class="file-icon">📄</span>
                        <span class="file-name">${escapeHtml(file.name)}</span>
                        <span class="file-size">(${formatFileSize(file.size)})</span>
                    </div>
                `;
            }
        });
    }
});

function closeUploadModal() {
    // Check if upload is in progress
    const submitBtn = document.querySelector('#uploadRecordsForm .submit-btn');
    if (submitBtn && submitBtn.disabled) {
        if (!confirm('Upload in progress. Are you sure you want to cancel?')) {
            return;
        }
    }
    
    document.getElementById('uploadRecordsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close participants modal
function closeParticipantsModal() {
    document.getElementById('participantsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Setup modal close functionality - FIXED VERSION
function setupModalClose() {
    window.addEventListener('click', function(event) {
        const participantsModal = document.getElementById('participantsModal');
        const uploadModal = document.getElementById('uploadRecordsModal');
        
        if (event.target === participantsModal) {
            closeParticipantsModal();
        }
        if (event.target === uploadModal) {
            closeUploadModal();
        }
    });
    
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            // Check if upload is in progress
            const submitBtn = document.querySelector('#uploadRecordsForm .submit-btn');
            if (submitBtn && submitBtn.disabled) {
                if (!confirm('Upload in progress. Are you sure you want to cancel?')) {
                    return;
                }
            }
            closeParticipantsModal();
            closeUploadModal();
        }
    });
}

// Utility functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function showNotification(message, type) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.action-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `action-notification ${type}`;
    notification.innerHTML = `
        <span class="notification-icon">${type === 'success' ? '✅' : '❌'}</span>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}
