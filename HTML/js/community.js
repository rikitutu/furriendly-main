// Community Board JavaScript
document.addEventListener('DOMContentLoaded', function() {
    loadPosts();
    loadEventsForPosts();

    // Create post form submission
    const createPostForm = document.getElementById('createPostForm');
    if (createPostForm) {
        createPostForm.addEventListener('submit', function(e) {
            e.preventDefault();
            createPost();
        });
    }

    // Image preview for create post
    const postImageInput = document.getElementById('postImage');
    if (postImageInput) {
        postImageInput.addEventListener('change', function(e) {
            previewImage(e.target, 'imagePreview');
        });
    }

    // Edit modal close
    const editModal = document.getElementById('editPostModal');
    const closeEditModal = document.querySelector('.close-edit-modal');
    if (closeEditModal) {
        closeEditModal.addEventListener('click', function() {
            editModal.style.display = 'none';
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === editModal) {
            editModal.style.display = 'none';
        }
    });
});

function loadPosts() {
    fetch('../php/get_posts.php')
        .then(response => response.json())
        .then(posts => {
            displayPosts(posts);
        })
        .catch(error => {
            console.error('Error loading posts:', error);
            document.getElementById('postsFeed').innerHTML = '<p class="loading">Error loading posts. Please try again.</p>';
        });
}

function displayPosts(posts) {
    const postsFeed = document.getElementById('postsFeed');

    if (posts.length === 0) {
        postsFeed.innerHTML = `
            <div class="no-posts">
                <i class="fas fa-paw"></i>
                <p>No posts yet. Be the first to share something!</p>
            </div>
        `;
        return;
    }

    postsFeed.innerHTML = posts.map(post => `
        <div class="post-card" data-post-id="${post.id}">
            <div class="post-header">
                <img src="${post.profile_pic || '../images/default-avatar.png'}" alt="Avatar" class="post-avatar">
                <div class="post-user-info">
                    <h4>${post.name || post.username}</h4>
                    <span>${formatDate(post.created_at)}</span>
                </div>
            </div>
            <div class="post-content">${escapeHtml(post.content)}</div>
            ${post.image_path ? `<img src="${post.image_path}" alt="Post image" class="post-image">` : ''}
            ${post.event_id ? `
                <a href="event_details.php?id=${post.event_id}" class="event-link">
                    <strong>📅 ${post.event_title}</strong><br>
                    ${formatDate(post.event_date)} • ${post.location}
                </a>
            ` : ''}
            <div class="post-actions">
                <div class="post-time">${formatDate(post.created_at)}</div>
                ${window.currentUser === post.username ? `
                    <div class="post-buttons">
                        <button class="edit-btn" onclick="editPost(${post.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="delete-btn" onclick="deletePost(${post.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                ` : ''}
            </div>
        </div>
    `).join('');
}

function createPost() {
    const form = document.getElementById('createPostForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('.submit-btn');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Posting...';

    fetch('../php/create_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            form.reset();
            document.getElementById('imagePreview').style.display = 'none';
            loadPosts();
            showMessage('Post created successfully!', 'success');
        } else {
            throw new Error('Failed to create post');
        }
    })
    .catch(error => {
        console.error('Error creating post:', error);
        showMessage('Failed to create post. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Post';
    });
}

function editPost(postId) {
    // Get post data
    fetch(`../php/get_posts.php`)
        .then(response => response.json())
        .then(posts => {
            const post = posts.find(p => p.id == postId);
            if (post) {
                openEditModal(post);
            }
        })
        .catch(error => {
            console.error('Error loading post for edit:', error);
        });
}

function openEditModal(post) {
    const modal = document.getElementById('editPostModal');
    const form = document.getElementById('editPostForm');

    form.querySelector('[name="post_id"]').value = post.id;
    form.querySelector('[name="content"]').value = post.content;
    form.querySelector('[name="event_id"]').value = post.event_id || '';

    // Handle image preview
    const imagePreview = document.getElementById('editImagePreview');
    if (post.image_path) {
        imagePreview.src = post.image_path;
        imagePreview.style.display = 'block';
    } else {
        imagePreview.style.display = 'none';
    }

    modal.style.display = 'block';

    // Handle form submission
    form.onsubmit = function(e) {
        e.preventDefault();
        updatePost();
    };

    // Handle image change
    const imageInput = document.getElementById('editPostImage');
    imageInput.onchange = function(e) {
        previewImage(e.target, 'editImagePreview');
    };
}

function updatePost() {
    const form = document.getElementById('editPostForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('.submit-btn');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Updating...';

    fetch('../php/edit_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            document.getElementById('editPostModal').style.display = 'none';
            loadPosts();
            showMessage('Post updated successfully!', 'success');
        } else {
            throw new Error('Failed to update post');
        }
    })
    .catch(error => {
        console.error('Error updating post:', error);
        showMessage('Failed to update post. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Update Post';
    });
}

function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post?')) {
        return;
    }

    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('../php/delete_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            loadPosts();
            showMessage('Post deleted successfully!', 'success');
        } else {
            throw new Error('Failed to delete post');
        }
    })
    .catch(error => {
        console.error('Error deleting post:', error);
        showMessage('Failed to delete post. Please try again.', 'error');
    });
}

function loadEventsForPosts() {
    fetch('../php/get_events_for_posts.php')
        .then(response => response.json())
        .then(events => {
            const eventSelects = document.querySelectorAll('select[name="event_id"]');
            eventSelects.forEach(select => {
                select.innerHTML = '<option value="">Select an event (optional)</option>';
                events.forEach(event => {
                    const option = document.createElement('option');
                    option.value = event.id;
                    option.textContent = `${event.event_title} - ${formatDate(event.event_date)}`;
                    select.appendChild(option);
                });
            });
        })
        .catch(error => {
            console.error('Error loading events:', error);
        });
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    if (days === 0) {
        return 'Today';
    } else if (days === 1) {
        return 'Yesterday';
    } else if (days < 7) {
        return `${days} days ago`;
    } else {
        return date.toLocaleDateString();
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showMessage(message, type) {
    // Simple message display - you can enhance this
    alert(message);
}
