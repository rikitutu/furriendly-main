// ===== MY HOSTING FUNCTIONALITY =====

// Filter events by status
function filterEvents(status) {
    const eventCards = document.querySelectorAll('.event-card');
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    // Update active tab
    tabBtns.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter events
    eventCards.forEach(card => {
        card.style.display = (status === 'all' || card.getAttribute('data-status') === status) ? 'block' : 'none';
    });
}

// Mark event as completed
function markCompleted(eventId, buttonElement) {
    if (confirm('Are you sure you want to mark this event as completed?\n\nThis will:' +
                '\n• Mark the event as completed' +
                '\n• Update all participant statuses to completed' +
                '\n• Move participants from "Joined" to "Completed" events' +
                '\n\nThis action cannot be undone.')) {
        
        // Show loading state
        const completeBtn = buttonElement;
        const originalHTML = completeBtn.innerHTML;
        completeBtn.innerHTML = '<span class="loading-text">⏳ Marking...</span>';
        completeBtn.disabled = true;
        
        fetch('../php/mark_event_completed.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'event_id=' + eventId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Event marked as completed!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            showNotification('Error: ' + error.message, 'error');
            completeBtn.innerHTML = originalHTML;
            completeBtn.disabled = false;
        });
    }
}

// View participants for completed events
function viewCompletedParticipants(eventId) {
    // Show loading state
    document.getElementById('participantsContainer').innerHTML = `
        <div class="loading">
            <div class="loading-spinner"></div>
            <p>Loading participants...</p>
        </div>
    `;
    document.getElementById('participantsModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    fetch(`../php/get_participants.php?event_id=${eventId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayParticipants(data.participants, eventId);
            } else {
                throw new Error(data.message || 'Failed to load participants');
            }
        })
        .catch(error => {
            document.getElementById('participantsContainer').innerHTML = 
                `<div class="error">
                    <p>Error loading participants: ${error.message}</p>
                </div>`;
        });
}

// Display participants without medical records
function displayParticipants(participants, eventId) {
    const container = document.getElementById('participantsContainer');
    
    if (participants.length === 0) {
        container.innerHTML = `
            <div class="no-participants">
                <div style="font-size: 48px; margin-bottom: 15px;">🐾</div>
                <h3>No Participants</h3>
                <p>No participants joined this completed event.</p>
            </div>
        `;
        return;
    }
    
    // Calculate totals
    const totalPets = participants.reduce((sum, participant) => sum + (participant.pets ? participant.pets.length : 0), 0);
    const participantsWithPets = participants.filter(p => p.pets && p.pets.length > 0).length;
    
    let html = `
        <div class="completed-event-header">
            <div class="event-stats">
                <div class="stat-item">
                    <span class="stat-number">${participants.length}</span>
                    <span class="stat-label">Total Participants</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">${totalPets}</span>
                    <span class="stat-label">Total Pets</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">${participantsWithPets}</span>
                    <span class="stat-label">With Pets</span>
                </div>
            </div>
        </div>
        
        <div class="participants-list completed-participants">
    `;
    
    participants.forEach((participant, index) => {
        const joinedDate = new Date(participant.joined_at);
        const formattedDate = joinedDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        
        const hasPets = participant.pets && participant.pets.length > 0;
        
        html += `
            <div class="participant-card ${hasPets ? 'has-pets' : 'no-pets'}">
                <div class="participant-header">
                    <div class="participant-identity">
                        <img src="${participant.profile_pic || '../images/default-avatar.png'}" 
                             alt="${participant.name}" 
                             class="participant-avatar-large"
                             onerror="this.src='../images/default-avatar.png'">
                        <div class="participant-info">
                            <h4 class="participant-name">${escapeHtml(participant.name)}</h4>
                            <div class="participant-contact">
                                <span class="contact-email">📧 ${escapeHtml(participant.email || 'No email provided')}</span>
                                <span class="join-date">📅 Joined: ${formattedDate}</span>
                                <span class="participant-username">👤 ${escapeHtml(participant.username)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="participant-status">
                        <span class="status-badge ${hasPets ? 'with-pets' : 'no-pets'}">
                            ${hasPets ? '🐾 Has Pets' : '❌ No Pets'}
                        </span>
                    </div>
                </div>
        `;
        
        // Show pet information
        if (hasPets) {
            html += `<div class="pets-section">`;
            html += `<h4 class="pets-title">Registered Pet${participant.pets.length > 1 ? 's' : ''} (${participant.pets.length}):</h4>`;
            html += `<div class="pets-grid">`;
            
            participant.pets.forEach((pet, petIndex) => {
                // Use the correct pet ID field - pet.id is the primary key
                const petId = pet.id || pet.pet_id;
                console.log('Pet data:', pet); // Debug log
                
                html += `
                    <div class="pet-card detailed">
                        <div class="pet-header">
                            <img src="${pet.pet_profile_pic || '../images/default-pet.png'}" 
                                 alt="${pet.pet_name}" 
                                 class="pet-avatar-large"
                                 onerror="this.src='../images/default-pet.png'">
                            <div class="pet-info">
                                <h5 class="pet-name">${escapeHtml(pet.pet_name)}</h5>
                                <div class="pet-details">
                                    <div class="pet-detail">
                                        <span class="detail-label">Species:</span>
                                        <span class="detail-value">${escapeHtml(pet.pet_species || 'Unknown')}</span>
                                    </div>
                                    ${pet.pet_breed ? `
                                    <div class="pet-detail">
                                        <span class="detail-label">Breed:</span>
                                        <span class="detail-value">${escapeHtml(pet.pet_breed)}</span>
                                    </div>
                                    ` : ''}
                                    <div class="pet-detail">
                                        <span class="detail-label">Age:</span>
                                        <span class="detail-value">${pet.age ? `${escapeHtml(pet.age)} years` : 'Not specified'}</span>
                                    </div>
                                    ${pet.pet_gender ? `
                                    <div class="pet-detail">
                                        <span class="detail-label">Gender:</span>
                                        <span class="detail-value">${escapeHtml(pet.pet_gender)}</span>
                                    </div>
                                    ` : ''}
                                    <div class="pet-detail">
                                        <span class="detail-label">Pet ID:</span>
                                        <span class="detail-value">${petId || 'Unknown'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Records Management Section -->
                        <div class="records-management-section">
                            <button class="records-management-btn" onclick="openUploadModal(${eventId}, ${petId}, ${participant.id}, '${escapeHtml(pet.pet_name)}')">
                                📁 Manage Medical Records
                            </button>
                        </div>
                    </div>
                `;
            });
            
            html += `</div></div>`;
        } else {
            html += `
                <div class="no-pets-message">
                    <div class="no-pets-icon">🚫</div>
                    <p>This participant did not register any pets for this event.</p>
                </div>
            `;
        }
        
        html += `</div>`; // Close participant-card
        
        // Add separator between participants (except last one)
        if (index < participants.length - 1) {
            html += `<div class="participant-separator"></div>`;
        }
    });
    
    html += '</div>'; // Close participants-list
    
    container.innerHTML = html;
}

// Open upload modal for pet records - FIXED VERSION
function openUploadModal(eventId, petId, participantId, petName) {
    console.log('Opening upload modal with:', { 
        eventId: eventId, 
        petId: petId, 
        participantId: participantId, 
        petName: petName 
    });
    
    // Enhanced validation with better error messages
    if ((!petId || petId <= 0) && (!participantId || participantId <= 0)) {
        showNotification('Error: Cannot determine which pet to upload records for. Please ensure the participant has a registered pet and try again.', 'error');
        return;
    }
    
    if (!petId || petId <= 0) {
        showNotification('Warning: No valid pet ID found. The system will try to find the pet using participant information.', 'error');
    }
    
    document.getElementById('petNameHeader').textContent = petName || 'Unknown Pet';
    document.getElementById('uploadEventId').value = eventId;
    document.getElementById('uploadPetId').value = petId || 0;
    document.getElementById('uploadParticipantId').value = participantId || 0;
    
    // Reset form
    document.getElementById('uploadRecordsForm').reset();
    document.getElementById('fileNameDisplay').innerHTML = '';
    
    // Load existing records
    loadExistingRecords(eventId, petId);
    
    // Show modal
    document.getElementById('uploadRecordsModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Close upload modal
function closeUploadModal() {
    document.getElementById('uploadRecordsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Load existing records for a pet - FIXED VERSION
function loadExistingRecords(eventId, petId) {
    const recordsList = document.getElementById('recordsList');
    recordsList.innerHTML = `
        <div class="loading">
            <div class="loading-spinner"></div>
            <p>Loading records...</p>
        </div>
    `;
    
    // Use the correct endpoint that returns proper JSON
    fetch(`../php/get_pet_record.php?event_id=${eventId}&pet_id=${petId}`)
        .then(response => {
            // First check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayExistingRecords(data.records || []);
            } else {
                // If no records but successful, show empty state
                if (data.count === 0) {
                    displayExistingRecords([]);
                } else {
                    throw new Error(data.message || 'Failed to load records');
                }
            }
        })
        .catch(error => {
            console.error('Error loading records:', error);
            // Show empty state instead of error for no records
            if (error.message.includes('No records') || error.message.includes('empty')) {
                displayExistingRecords([]);
            } else {
                recordsList.innerHTML = `
                    <div class="no-records">
                        <div class="no-records-icon">📄</div>
                        <p>No records found or error loading records</p>
                        <small>${error.message}</small>
                    </div>
                `;
            }
        });
}

// Display existing records - FIXED VERSION
function displayExistingRecords(records) {
    const recordsList = document.getElementById('recordsList');
    
    if (!records || records.length === 0) {
        recordsList.innerHTML = `
            <div class="no-records">
                <div class="no-records-icon">📄</div>
                <h4>No Records Yet</h4>
                <p>No medical records have been uploaded for this pet yet.</p>
                <p><small>Upload a record using the form above.</small></p>
            </div>
        `;
        return;
    }
    
    let html = '';
    records.forEach(record => {
        const uploadDate = new Date(record.uploaded_at);
        const formattedDate = uploadDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        html += `
            <div class="record-item">
                <div class="record-info">
                    <div class="record-type">${escapeHtml(record.record_type || 'Medical Record')}</div>
                    <div class="record-description">${escapeHtml(record.description || 'No description provided')}</div>
                    <div class="record-meta">
                        <span class="record-date">📅 ${formattedDate}</span>
                        <span class="record-size">📦 ${formatFileSize(record.file_size)}</span>
                        <span class="record-uploader">👤 ${escapeHtml(record.uploaded_by || 'Unknown')}</span>
                    </div>
                </div>
                <div class="record-actions">
                    <a href="../php/download_record.php?record_id=${record.id}" class="download-btn" target="_blank">Download</a>
                    <button class="delete-btn" onclick="deleteRecord(${record.id})">Delete</button>
                </div>
            </div>
        `;
    });
    
    recordsList.innerHTML = html;
}

// Format file size
function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Delete record
function deleteRecord(recordId) {
    if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
        fetch('../php/delete_record.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'record_id=' + recordId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Record deleted successfully', 'success');
                // Reload records list
                const eventId = document.getElementById('uploadEventId').value;
                const petId = document.getElementById('uploadPetId').value;
                loadExistingRecords(eventId, petId);
            } else {
                showNotification('Error deleting record: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error deleting record: ' + error.message, 'error');
        });
    }
}

// Handle file upload area click
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Handle record upload form submission
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
});

// Close participants modal
function closeParticipantsModal() {
    document.getElementById('participantsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper function to show notifications
function showNotification(message, type) {
    const existingNotifications = document.querySelectorAll('.action-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `action-notification ${type}`;
    notification.innerHTML = `
        <span class="notification-icon">${type === 'success' ? '✅' : '❌'}</span>
        <span class="notification-message">${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Setup modal close functionality
function setupModalClose() {
    window.addEventListener('click', function(event) {
        const participantsModal = document.getElementById('participantsModal');
        const uploadModal = document.getElementById('uploadRecordsModal');
        const appealModal = document.getElementById('appealModal');

        if (event.target === participantsModal) {
            closeParticipantsModal();
        }
        if (event.target === uploadModal) {
            closeUploadModal();
        }
        if (event.target === appealModal) {
            closeAppealModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeParticipantsModal();
            closeUploadModal();
            closeAppealModal();
        }
    });
}

// Appeal functionality
function openAppealModal(eventId, rejectionReason) {
    document.getElementById('appealEventId').value = eventId;
    document.getElementById('rejectionReasonText').textContent = rejectionReason;
    document.getElementById('appealMessage').value = '';
    document.getElementById('appealModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeAppealModal() {
    document.getElementById('appealModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function editEventForAppeal(eventId) {
    window.location.href = `edit_event.php?event_id=${eventId}`;
}

// Handle appeal form submission
document.addEventListener('DOMContentLoaded', function() {
    const appealForm = document.getElementById('appealForm');
    if (appealForm) {
        appealForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<span class="loading-text">⏳ Submitting...</span>';
            submitBtn.disabled = true;

            fetch('../ADMIN/appeal_event.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    throw new Error(data.message || 'Appeal submission failed');
                }
            })
            .catch(error => {
                showNotification('Error: ' + error.message, 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    setupModalClose();
});
