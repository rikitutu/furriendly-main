let currentEventId = null;
let selectedPetId = null;

// Open pet selection modal
function openPetSelection(eventId) {
    console.log('Opening pet selection for event:', eventId);
    currentEventId = eventId;
    selectedPetId = null;
    
    // Reset UI
    document.getElementById('joinConfirmBtn').disabled = true;
    document.getElementById('joinConfirmBtn').textContent = 'Join Event';
    document.getElementById('petsContainer').innerHTML = '<div class="loading">Loading pets</div>';
    document.getElementById('noPetsMessage').style.display = 'none';
    
    // Show modal
    document.getElementById('petSelectionModal').style.display = 'flex';
    
    // Load user's pets
    loadUserPets();
}

// Close pet selection modal
function closePetSelection() {
    console.log('Closing pet selection');
    document.getElementById('petSelectionModal').style.display = 'none';
    currentEventId = null;
    selectedPetId = null;
}

// Load user's pets from server
function loadUserPets() {
    console.log('Loading user pets...');
    fetch('../php/get_user_pets.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Pets data received:', data);
            const petsContainer = document.getElementById('petsContainer');
            const noPetsMessage = document.getElementById('noPetsMessage');
            
            if (data.success && data.pets && data.pets.length > 0) {
                // First, get which pets are already attending this event
                fetch(`../php/get_user_attending_pets.php?event_id=${currentEventId}`)
                    .then(response => response.json())
                    .then(attendingData => {
                        const attendingPetIds = attendingData.success ? attendingData.attending_pets.map(pet => pet.id) : [];
                        const attendingPetNames = attendingData.success ? attendingData.attending_pets.map(pet => pet.pet_name) : [];
                        
                        // Display pets
                        petsContainer.innerHTML = '';
                        data.pets.forEach(pet => {
                            const isAlreadyAttending = attendingPetIds.includes(pet.id);
                            const petOption = createPetOption(pet, isAlreadyAttending);
                            petsContainer.appendChild(petOption);
                        });
                        noPetsMessage.style.display = 'none';
                        
                        // If all pets are already attending, show a message
                        if (attendingPetIds.length > 0 && attendingPetIds.length === data.pets.length) {
                            petsContainer.innerHTML += `
                                <div class="all-pets-attending">
                                    <p>🎉 All your pets are already attending this event!</p>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading attending pets:', error);
                        // Fallback: display all pets without attending info
                        displayAllPets(data.pets);
                    });
            } else {
                // No pets found
                petsContainer.innerHTML = '';
                noPetsMessage.style.display = 'block';
                console.log('No pets found');
            }
        })
        .catch(error => {
            console.error('Error loading pets:', error);
            document.getElementById('petsContainer').innerHTML = 
                '<div class="no-pets-message">Error loading pets. Please try again.</div>';
        });
}

function createPetOption(pet, isAlreadyAttending = false) {
    const petDiv = document.createElement('div');
    petDiv.className = `pet-option ${isAlreadyAttending ? 'already-attending' : ''}`;
    
    if (!isAlreadyAttending) {
        petDiv.onclick = () => selectPet(pet.id, petDiv);
    }
    
    const ageDisplay = pet.pet_age && pet.pet_age !== '0 years' ? pet.pet_age : 'Age not specified';
    
    petDiv.innerHTML = `
        <input type="radio" name="petSelect" class="pet-radio" id="pet-${pet.id}" ${isAlreadyAttending ? 'disabled' : ''}>
        <div class="pet-info">
            <h4>${escapeHtml(pet.pet_name)} ${isAlreadyAttending ? ' (Already Attending)' : ''}</h4>
            <p>${escapeHtml(pet.pet_species)} • ${ageDisplay}</p>
            ${isAlreadyAttending ? '<p class="already-attending-note">This pet is already attending this event</p>' : ''}
        </div>
    `;
    
    return petDiv;
}

function displayAllPets(pets) {
    const petsContainer = document.getElementById('petsContainer');
    const noPetsMessage = document.getElementById('noPetsMessage');
    
    petsContainer.innerHTML = '';
    pets.forEach(pet => {
        const petOption = createPetOption(pet, false);
        petsContainer.appendChild(petOption);
    });
    noPetsMessage.style.display = 'none';
}

// Select a pet
function selectPet(petId, element) {
    console.log('Pet selected:', petId);
    
    // Remove selected class from all pets
    document.querySelectorAll('.pet-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // Add selected class to clicked pet
    element.classList.add('selected');
    
    // Update radio button
    const radio = element.querySelector('.pet-radio');
    radio.checked = true;
    
    // Enable join button
    selectedPetId = petId;
    document.getElementById('joinConfirmBtn').disabled = false;
    console.log('Join button enabled');
}

// Join event with selected pet
function joinEvent() {
    if (!currentEventId || !selectedPetId) {
        alert('Please select a pet to join the event.');
        return;
    }
    
    console.log('Joining event:', currentEventId, 'with pet:', selectedPetId);
    
    // Disable button during submission
    const joinBtn = document.getElementById('joinConfirmBtn');
    joinBtn.disabled = true;
    joinBtn.textContent = 'Joining...';
    
    // Send join request
    fetch('../php/join_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `event_id=${currentEventId}&pet_id=${selectedPetId}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Join response:', data);
        if (data.success) {
            // Show success modal
            closePetSelection();
            showSuccessModal();
        } else {
            alert('Error joining event: ' + data.message);
            joinBtn.disabled = false;
            joinBtn.textContent = 'Join Event';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error joining event. Please try again.');
        joinBtn.disabled = false;
        joinBtn.textContent = 'Join Event';
    });
}

// Show success modal
function showSuccessModal() {
    console.log('Showing success modal');
    document.getElementById('successModal').style.display = 'flex';
}

// Close success modal
function closeSuccessModal() {
    console.log('Closing success modal');
    document.getElementById('successModal').style.display = 'none';
    // Optionally refresh the page or update UI
    location.reload();
}

// Redirect to add pet page
function redirectToAddPet() {
    console.log('Redirecting to add pet page');
    window.location.href = 'your_pet.php';
}

// Utility function to escape HTML
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Close modals when clicking outside
window.onclick = function(event) {
    const petModal = document.getElementById('petSelectionModal');
    const successModal = document.getElementById('successModal');
    
    if (event.target === petModal) {
        closePetSelection();
    }
    if (event.target === successModal) {
        closeSuccessModal();
    }
}

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePetSelection();
        closeSuccessModal();
    }
});

// ===== UPCOMING EVENTS ENHANCEMENTS =====

// Initialize events page with enhanced features
function initializeEventsPage() {
    addEventCardAnimations();
    addSearchFunctionality();
    updateEventCountdowns();
    enhanceServiceTags();
}

// Add staggered animations to event cards
function addEventCardAnimations() {
    const eventCards = document.querySelectorAll('.event-card');
    eventCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
}

// Add real-time search functionality
function addSearchFunctionality() {
    const searchInput = document.querySelector('.search-input-right');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                const title = card.querySelector('.event-title').textContent.toLowerCase();
                const description = card.querySelector('.event-description').textContent.toLowerCase();
                const location = card.querySelector('.event-location').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm) || location.includes(searchTerm)) {
                    card.style.display = 'block';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });
        });
    }
}

// Update event countdown timers
function updateEventCountdowns() {
    const countdownElements = document.querySelectorAll('.event-countdown');
    
    countdownElements.forEach(element => {
        const eventDate = new Date(element.dataset.eventDate);
        updateCountdown(element, eventDate);
    });
    
    // Update countdowns every minute
    setInterval(() => {
        countdownElements.forEach(element => {
            const eventDate = new Date(element.dataset.eventDate);
            updateCountdown(element, eventDate);
        });
    }, 60000);
}

function updateCountdown(element, eventDate) {
    const now = new Date();
    const timeDiff = eventDate - now;
    
    if (timeDiff <= 0) {
        element.textContent = 'Event in progress!';
        element.style.background = 'linear-gradient(135deg, #ff8c42, #ff6b35)';
        return;
    }
    
    const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    
    if (days > 0) {
        element.textContent = `${days}d ${hours}h until event`;
    } else if (hours > 0) {
        element.textContent = `${hours}h until event`;
    } else {
        const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
        element.textContent = `${minutes}m until event`;
        element.style.background = 'linear-gradient(135deg, #dc3545, #ff6b6b)';
    }
}

// Add hover effects to service tags
function enhanceServiceTags() {
    const serviceTags = document.querySelectorAll('.service-tag');
    serviceTags.forEach(tag => {
        tag.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });
        
        tag.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Events page initialized');
    initializeEventsPage();
});

// Add smooth scrolling for better UX
function smoothScrollToEvents() {
    const eventsSection = document.querySelector('.events-grid');
    if (eventsSection) {
        eventsSection.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Create pet option element
function createPetOption(pet) {
    const petDiv = document.createElement('div');
    petDiv.className = 'pet-option';
    petDiv.onclick = () => selectPet(pet.id, petDiv);
    
    // Handle age display - use pet_age directly since it's now calculated in PHP
    const ageDisplay = pet.pet_age && pet.pet_age !== '0 years' ? pet.pet_age : 'Age not specified';
    
    petDiv.innerHTML = `
        <input type="radio" name="petSelect" class="pet-radio" id="pet-${pet.id}">
        <div class="pet-info">
            <h4>${escapeHtml(pet.pet_name)}</h4>
            <p>${escapeHtml(pet.pet_species)} • ${ageDisplay}</p>
        </div>
    `;
    
    return petDiv;
}