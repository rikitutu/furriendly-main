// ===== EVENT DETAILS FUNCTIONALITY =====

let selectedEventId = null;

// Open pet selection modal for joining event
function openPetSelection(eventId) {
    selectedEventId = eventId;
    
    // Load user's pets
    fetch('../php/get_user_pets.php')
        .then(response => response.json())
        .then(data => {
            const petsContainer = document.getElementById('petsContainer');
            const noPetsMessage = document.getElementById('noPetsMessage');
            const joinConfirmBtn = document.getElementById('joinConfirmBtn');
            
            if (data.success && data.pets.length > 0) {
                petsContainer.innerHTML = '';
                data.pets.forEach(pet => {
                    const petOption = document.createElement('div');
                    petOption.className = 'pet-option';
                    petOption.innerHTML = `
                        <input type="radio" name="selectedPet" value="${pet.id}" class="pet-radio" onchange="enableJoinButton()">
                        <div class="pet-info">
                            <h4>${pet.pet_name}</h4>
                            <p>${pet.pet_species} • ${pet.pet_age} years old</p>
                        </div>
                    `;
                    petsContainer.appendChild(petOption);
                });
                noPetsMessage.style.display = 'none';
            } else {
                petsContainer.innerHTML = '';
                noPetsMessage.style.display = 'block';
            }
            
            // Show modal
            document.getElementById('petSelectionModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading pets');
        });
}

// Close pet selection modal
function closePetSelection() {
    document.getElementById('petSelectionModal').style.display = 'none';
    selectedEventId = null;
}

// Enable join button when pet is selected
function enableJoinButton() {
    document.getElementById('joinConfirmBtn').disabled = false;
}

// Join event with selected pet
function joinEvent() {
    const selectedPet = document.querySelector('input[name="selectedPet"]:checked');
    
    if (!selectedPet) {
        alert('Please select a pet');
        return;
    }

    const formData = new FormData();
    formData.append('event_id', selectedEventId);
    formData.append('pet_id', selectedPet.value);

    fetch('../php/join_event.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Successfully joined the event!');
            closePetSelection();
            // Reload notifications after joining
            if (window.notificationManager) {
                window.notificationManager.loadNotifications();
            }
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while joining the event');
    });
}

// Redirect to add pet page
function redirectToAddPet() {
    window.location.href = 'your_pet.php';
}

// Close modal when clicking outside
function setupModalClose() {
    window.onclick = function(event) {
        const modal = document.getElementById('petSelectionModal');
        if (event.target === modal) {
            closePetSelection();
        }
    }
}

// Initialize event details functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Event details page loaded');
    setupModalClose();
    
    // Add any additional initialization for event details page
});