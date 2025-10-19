function showSection(id) {
    // Hide all tab sections
    document.querySelectorAll('.tab-section').forEach(sec => {
        sec.classList.add("hidden");
        sec.classList.remove("active");
    });

    // Show selected section
    const activeSection = document.getElementById(id);
    activeSection.classList.remove("hidden");
    activeSection.classList.add("active");

    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove("active"));

    // Add active class to clicked button
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => {
        const onclick = btn.getAttribute('onclick') || '';
        if (onclick.includes(id)) {
            btn.classList.add("active");
        }
    });
}


// ====== Search and Filter Functions ======
let allHousingData = [];

function handleSearch() {
    const searchLocation = document.getElementById('searchLocation').value.toLowerCase();
    const rentRange = document.getElementById('rentRange').value;
    const propertyType = document.getElementById('propertyType').value;
    const furnishedStatus = document.getElementById('furnishedStatus').value;
    const bedrooms = document.getElementById('bedrooms').value;

    let filteredData = allHousingData.filter(housing => {
        // Location filter
        if (searchLocation && !housing.location.toLowerCase().includes(searchLocation)) {
            return false;
        }

        // Rent range filter
        if (rentRange) {
            const rent = parseFloat(housing.rent.replace(/,/g, ''));
            if (rentRange === '0-10000' && (rent < 0 || rent > 10000)) return false;
            if (rentRange === '10000-30000' && (rent < 10000 || rent > 30000)) return false;
            if (rentRange === '30000-50000' && (rent < 30000 || rent > 50000)) return false;
            if (rentRange === '50000-100000' && (rent < 50000 || rent > 100000)) return false;
            if (rentRange === '100000+' && rent < 100000) return false;
        }

        // Property type filter
        if (propertyType && housing.property_type !== propertyType) {
            return false;
        }

        // Furnished status filter
        if (furnishedStatus && housing.furnished_status !== furnishedStatus) {
            return false;
        }

        // Bedrooms filter
        if (bedrooms) {
            const housingBedrooms = housing.bedrooms;
            if (bedrooms === '4+' && housingBedrooms < 4) return false;
            if (bedrooms !== '4+' && housingBedrooms != bedrooms) return false;
        }

        return true;
    });

    displayHousingData(filteredData);
}

function clearFilters() {
    document.getElementById('searchLocation').value = '';
    document.getElementById('rentRange').value = '';
    document.getElementById('propertyType').value = '';
    document.getElementById('furnishedStatus').value = '';
    document.getElementById('bedrooms').value = '';
    displayHousingData(allHousingData);
}

function displayHousingData(data) {
    let list = document.getElementById("housingList");
    list.innerHTML = "";

    if (data.length === 0) {
        list.innerHTML = '<div class="no-content">No housing posts match your search criteria.</div>';
        return;
    }

    data.forEach(h => {
        const verificationBadge = h.verification_status === 'verified' && h.khotiyan ?
            `<span class="verification-badge">✅ Verified (${h.khotiyan})</span>` : '';

        const availabilityInfo = h.available_from ?
            `<p class="availability">Available from: ${new Date(h.available_from).toLocaleDateString()}</p>` : '';

        // Note: Removed negotiable text as requested, but kept all other elements
        list.innerHTML += `
            <div class="card housing-card">
                <div class="card-header">
                    <h3>${h.title}</h3>
                    <span class="rent">৳${h.rent}</span>
                </div>
                <div class="card-body">
                    <p class="location"><i class="fas fa-map-marker-alt"></i> ${h.location}</p>
                    <div class="property-details">
                        <span class="property-type">${h.property_type}</span>
                        <span class="bedrooms">${h.bedrooms} bed</span>
                        <span class="bathrooms">${h.bathrooms} bath</span>
                    </div>
                    <p class="description">${h.description}</p>
                    ${availabilityInfo}
                    ${verificationBadge}
                    ${h.coordinates ? `<p class="coordinates"><i class="fas fa-map-pin"></i> ${h.coordinates}</p>` : ''}
                    <div class="card-footer">
                        <div class="card-actions">
                            <button class="details-btn" onclick="viewHousingDetails(${h.id})">
                                <i class="fas fa-eye"></i> Details
                            </button>
                            <button class="apply-btn" onclick="applyForHousing(${h.id})">
                                <i class="fas fa-paper-plane"></i> Apply
                            </button>

                            <!-- 🆕 Delete button for user's own posts -->
                            ${h.is_owner ? `
                            <button class="delete-btn danger" onclick="deleteMyHousing(${h.id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>` : ''}
                        </div>
                    </div>
                </div>
            </div>`;
    });
}

// ====== Fetch Housing Stats ======
function fetchHousingStats() {
    fetch("../../backend/housing_management.php?action=get_dashboard_stats")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data.applicant_stats, data.owner_stats);
            }
        })
        .catch(error => {
            console.error('Error fetching housing stats:', error);
        });
}

function updateDashboardStats(applicantStats, ownerStats) {
    // Calculate values
    const pending = applicantStats.pending || 0;
    const applied = (applicantStats.pending || 0) + (applicantStats.shortlisted || 0) + (applicantStats.accepted || 0);
    const confirmed = applicantStats.accepted || 0;
    const cancelled = (applicantStats.rejected || 0) + (applicantStats.withdrawn || 0);
    
    // Calculate total applications
    const totalApplications = pending + (applicantStats.shortlisted || 0) + confirmed + cancelled;
    
    // Update applicant stats
    document.getElementById('statPending').textContent = pending;
    document.getElementById('statApplied').textContent = applied;
    document.getElementById('statConfirmed').textContent = confirmed;
    document.getElementById('statCancelled').textContent = cancelled;
    
    // Update progress bars with dual colors
    setTimeout(() => {
        updateDualProgressBar('pendingProgress', pending, totalApplications);
        updateDualProgressBar('appliedProgress', applied, totalApplications);
        updateDualProgressBar('confirmedProgress', confirmed, totalApplications);
        updateDualProgressBar('cancelledProgress', cancelled, totalApplications);
    }, 500);
    
    // Update status tab counts
    document.getElementById('pendingCount').textContent = applicantStats.pending || 0;
    document.getElementById('confirmedCount').textContent = applicantStats.accepted || 0;
    document.getElementById('cancelledCount').textContent = applicantStats.withdrawn || 0;
    document.getElementById('rejectedCount').textContent = applicantStats.rejected || 0;
    
    // Re-add click handlers after stats update
    setTimeout(() => {
        addStatCardClickHandlers();
    }, 100);
}

function updateDualProgressBar(progressId, currentValue, totalValue) {
    const progressBar = document.getElementById(progressId);
    if (!progressBar) return;
    
    // Clear existing content
    progressBar.innerHTML = '';
    
    if (totalValue === 0) {
        progressBar.style.width = '100%';
        return;
    }
    
    // Always show full width bar
    progressBar.style.width = '100%';
    
    // Calculate percentages within the bar
    const currentPercentage = (currentValue / totalValue) * 100;
    const remainingPercentage = 100 - currentPercentage;
    
    // Create the current status section
    const currentSection = document.createElement('div');
    currentSection.className = 'progress-current-section';
    currentSection.style.width = currentPercentage + '%';
    
    // Create the remaining total section
    const remainingSection = document.createElement('div');
    remainingSection.className = 'progress-remaining-section';
    remainingSection.style.width = remainingPercentage + '%';
    
    progressBar.appendChild(currentSection);
    progressBar.appendChild(remainingSection);
    
    // Add text overlay showing "current / total"
    const textOverlay = document.createElement('div');
    textOverlay.className = 'progress-text';
    textOverlay.textContent = `${currentValue}/${totalValue}`;
    
    progressBar.appendChild(textOverlay);
}

// ====== Fetch Housing ======
function fetchHousing() {
    fetch("../../backend/fetch_housing.php")
        .then(res => res.json())
        .then(data => {
            allHousingData = data;
            displayHousingData(data);
            fetchHousingStats();
            fetchNearbyHousing(); // Also fetch nearby housing count
        })
        .catch(error => {
            console.error('Error fetching housing data:', error);
            document.getElementById("housingList").innerHTML = '<div class="error">Failed to load housing data. Please try again.</div>';
        });
}

// ====== Fetch Nearby Housing ======
function fetchNearbyHousing() {
    fetch("../../backend/fetch_nearby_housing.php")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log('Fetched nearby housing data:', data);
                
                // Use the nearby count from server response (already filtered)
                const nearbyCount = data.nearby_count;
                const userLocation = data.user_area;
                
                console.log('User location:', userLocation);
                console.log('Server nearby count:', nearbyCount);
                
                // Update nearby housing count
                const nearbyElement = document.getElementById('statNearby');
                if (nearbyElement) {
                    nearbyElement.textContent = nearbyCount;
                }
                
                // Store nearby housing data for potential filtering
                window.nearbyHousingData = data.housing;
                window.userArea = data.user_area;
            }
        })
        .catch(error => {
            console.error('Error fetching nearby housing:', error);
        });
}

// ====== View Housing Details ======
function viewHousingDetails(housingId) {
    fetch(`../../backend/get_housing_details.php?id=${housingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showHousingDetailsModal(data.housing);
            } else {
                alert('Failed to load housing details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error fetching housing details:', error);
            alert('Failed to load housing details. Please try again.');
        });
}

function showHousingDetailsModal(housing) {
    // Close any existing housing details modal first
    closeHousingDetailsModal();
    
    const modal = document.createElement('div');
    modal.className = 'modal housing-details-modal-container';
    modal.innerHTML = `
        <div class="modal-content glass-card housing-details-modal">
            <div class="modal-header">
                <h3>${housing.title}</h3>
                <button class="close-btn" onclick="closeHousingDetailsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="housing-details-grid">
                    <div class="detail-section">
                        <h4>Basic Information</h4>
                        <div class="detail-row"><span class="detail-label">Location:</span><span class="detail-value">${housing.location}</span></div>
                        <div class="detail-row"><span class="detail-label">Rent:</span><span class="detail-value">৳${housing.rent}</span></div>
                        <div class="detail-row"><span class="detail-label">Property Type:</span><span class="detail-value">${housing.property_type}</span></div>
                        <div class="detail-row"><span class="detail-label">Size:</span><span class="detail-value">${housing.size_sqft} sqft</span></div>
                    </div>
                    <div class="detail-section">
                        <h4>Property Details</h4>
                        <div class="detail-row"><span class="detail-label">Bedrooms:</span><span class="detail-value">${housing.bedrooms}</span></div>
                        <div class="detail-row"><span class="detail-label">Bathrooms:</span><span class="detail-value">${housing.bathrooms}</span></div>
                        <div class="detail-row"><span class="detail-label">Balconies:</span><span class="detail-value">${housing.balconies}</span></div>
                        <div class="detail-row"><span class="detail-label">Furnished:</span><span class="detail-value">${housing.furnished_status}</span></div>
                    </div>
                    <div class="detail-section">
                        <h4>Financial Details</h4>
                        <div class="detail-row"><span class="detail-label">Service Charge:</span><span class="detail-value">৳${housing.service_charge || 0}</span></div>
                        <div class="detail-row"><span class="detail-label">Advance Deposit:</span><span class="detail-value">৳${housing.advance_deposit || 0}</span></div>
                        <div class="detail-row"><span class="detail-label">Negotiable:</span><span class="detail-value">${housing.negotiable ? 'Yes' : 'No'}</span></div>
                    </div>
                    <div class="detail-section full-width">
                        <h4>Description</h4>
                        <p class="housing-description">${housing.description}</p>
                    </div>
                    <div class="detail-section full-width">
                        <h4>House Photos</h4>
                        <div id="housingPhotos-${housing.id}" class="housing-photos-container">
                            <div class="loading-photos">
                                <i class="fas fa-spinner fa-spin"></i> Loading photos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="details-btn" onclick="closeHousingDetailsModal()">Close</button>
                <button class="apply-btn" onclick="applyForHousing(${housing.id}); closeHousingDetailsModal();">Apply Now</button>
            </div>
        </div>`;
    
    // Add click outside to close functionality
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeHousingDetailsModal();
        }
    });
    
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
    
    // Load housing photos
    loadHousingPhotos(housing.id);
}

function loadHousingPhotos(housingId) {
    const photosContainer = document.getElementById(`housingPhotos-${housingId}`);
    if (!photosContainer) return;
    
    fetch(`../../backend/get_housing_images.php?housing_id=${housingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayHousingPhotos(photosContainer, data.images);
            } else {
                photosContainer.innerHTML = `
                    <div class="no-photos">
                        <i class="fas fa-image text-white/40 text-2xl mb-2"></i>
                        <p class="text-white/60">No photos available</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading housing photos:', error);
            photosContainer.innerHTML = `
                <div class="no-photos">
                    <i class="fas fa-exclamation-triangle text-red-400 text-2xl mb-2"></i>
                    <p class="text-red-400">Failed to load photos</p>
                </div>
            `;
        });
}

function displayHousingPhotos(container, images) {
    if (!images || images.length === 0) {
        container.innerHTML = `
            <div class="no-photos">
                <i class="fas fa-image text-white/40 text-4xl mb-3"></i>
                <p class="text-white/60">No photos available for this property</p>
            </div>
        `;
        return;
    }
    
    let photosHTML = '<div class="photos-grid">';
    
    images.forEach((image, index) => {
        const isPrimary = image.is_primary == 1;
        photosHTML += `
            <div class="photo-item ${isPrimary ? 'primary-photo' : ''}" onclick="openPhotoViewer('${image.image_path}', '${image.image_name || 'House Photo'}')">
                <img src="${image.image_path}" alt="${image.image_name || 'House Photo'}" loading="lazy" />
                ${isPrimary ? '<div class="primary-badge"><i class="fas fa-star"></i> Primary</div>' : ''}
                <div class="photo-overlay">
                    <i class="fas fa-expand text-white text-xl"></i>
                </div>
            </div>
        `;
    });
    
    photosHTML += '</div>';
    container.innerHTML = photosHTML;
}

function openPhotoViewer(imagePath, imageName) {
    const viewer = document.createElement('div');
    viewer.className = 'photo-viewer-modal';
    viewer.innerHTML = `
        <div class="photo-viewer-content">
            <div class="photo-viewer-header">
                <h4>${imageName}</h4>
                <button class="close-btn" onclick="closePhotoViewer()">&times;</button>
            </div>
            <div class="photo-viewer-body">
                <img src="${imagePath}" alt="${imageName}" />
            </div>
        </div>
    `;
    
    viewer.addEventListener('click', function(e) {
        if (e.target === viewer) {
            closePhotoViewer();
        }
    });
    
    document.body.appendChild(viewer);
    setTimeout(() => viewer.classList.add('show'), 10);
}

function closePhotoViewer() {
    const viewer = document.querySelector('.photo-viewer-modal');
    if (viewer) {
        viewer.classList.remove('show');
        setTimeout(() => viewer.remove(), 300);
    }
}

function closeHousingDetailsModal() {
    // First try to find the specific housing details modal container
    const modal = document.querySelector('.housing-details-modal-container');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
        return;
    }
    
    // Fallback: find any modal with housing-details-modal class inside
    const housingModal = document.querySelector('.housing-details-modal');
    if (housingModal) {
        const parentModal = housingModal.closest('.modal');
        if (parentModal) {
            parentModal.classList.remove('show');
            setTimeout(() => parentModal.remove(), 300);
        }
    }
}

// ====== Apply for Housing ======
function applyForHousing(housingId) {
    const message = prompt('Add a message with your application (optional):') || 'Application submitted';
    if (message !== null) {
        fetch('../../backend/housing_management.php?action=apply', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ housing_id: housingId, message: message })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Application submitted successfully!');
                    fetchHousing();
                    fetchHousingStats();
                } else {
                    alert('Failed to submit application: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error applying for housing:', error);
                alert('Failed to submit application. Please try again.');
            });
    }
}

// 🆕 ====== Delete My Housing Post ======
function deleteMyHousing(housingId) {
    if (!confirm('Are you sure you want to delete this housing post? This action cannot be undone.')) return;

    fetch('../../backend/delete_housing_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: housingId })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Housing post deleted successfully.');
                fetchHousing();
                // Refresh the My Housing section as well
                location.reload();
            } else {
                alert('Failed to delete post: ' + (data.message || 'Unknown error.'));
            }
        })
        .catch(err => {
            console.error('Error deleting housing post:', err);
            alert('Failed to delete post. Please try again.');
        });
}

// ====== Delete Post Function for My Housing Posts ======
function deletePost(serviceId) {
    if (!confirm('Are you sure you want to delete this housing post? This action cannot be undone.')) return;

    fetch('../../backend/delete_housing_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: serviceId })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Housing post deleted successfully.');
                // Remove the post element from the DOM
                const postElement = document.getElementById(`post-${serviceId}`);
                if (postElement) {
                    postElement.remove();
                }
                // Refresh the housing list
                fetchHousing();
            } else {
                alert('Failed to delete post: ' + (data.message || 'Unknown error.'));
            }
        })
        .catch(err => {
            console.error('Error deleting housing post:', err);
            alert('Failed to delete post. Please try again.');
        });
}

// ====== Post Housing ======
function redirectToPostService() {
    window.location.href = '../../Post Service Page/post_service.php';
}
function openPostForm() { 
    // Redirect to the proper Post Service Page instead of showing a modal
    redirectToPostService();
}
function closePostForm() { document.getElementById("postModal")?.classList.add("hidden"); }

// Remove the old form submission handler since we're redirecting to Post Service Page
// The housing posting functionality is now handled by the Post Service Page

// ... (rest of your code for expenses, stats, dropdown, etc. remains unchanged)

// ====== Status Management Functions ======
function showStatusTab(status) {
    // Hide all status sections
    document.querySelectorAll('.status-tab-section').forEach(sec => {
        sec.classList.add('hidden');
        sec.classList.remove('active');
    });
    
    // Show selected section
    const targetSection = document.getElementById(status);
    if (targetSection) {
        targetSection.classList.remove('hidden');
        targetSection.classList.add('active');
    }
    
    // Update tab buttons
    document.querySelectorAll('.status-tab-btn').forEach(btn => {
        btn.classList.remove('active');
        // Check if this button corresponds to the current status
        const btnText = btn.textContent.toLowerCase();
        if (btnText.includes(status)) {
            btn.classList.add('active');
        }
    });
}

function refreshStatus() {
    fetchMyApplications();
    fetchHousingStats();
    loadMyConfirmedHousing(); // Refresh confirmed housing info
}

function fetchMyApplications() {
    fetch('../../backend/housing_management.php?action=get_my_applications')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMyApplications(data.applications);
            }
        })
        .catch(error => {
            console.error('Error fetching applications:', error);
        });
}

function displayMyApplications(applications) {
    const statusLists = {
        pending: document.getElementById('pendingList'),
        confirmed: document.getElementById('confirmedList'),
        cancelled: document.getElementById('cancelledList'),
        rejected: document.getElementById('rejectedList')
    };
    
    // Clear all lists
    Object.values(statusLists).forEach(list => list.innerHTML = '');
    
    applications.forEach(app => {
        const statusKey = app.status === 'accepted' ? 'confirmed' : 
                         app.status === 'withdrawn' ? 'cancelled' : app.status;
        
        if (statusLists[statusKey]) {
            statusLists[statusKey].innerHTML += `
                <div class="status-item">
                    <div class="status-item-header">
                        <h4>${app.title}</h4>
                        <span class="status-badge status-${app.status}">${app.status}</span>
                    </div>
                    <div class="status-item-body">
                        <p><strong>Location:</strong> ${app.location}</p>
                        <p><strong>Rent:</strong> ৳${app.rent}</p>
                        <p><strong>Owner:</strong> ${app.owner_name}</p>
                        <p><strong>Applied:</strong> ${new Date(app.created_at).toLocaleDateString()}</p>
                        ${app.message ? `<p><strong>Message:</strong> ${app.message}</p>` : ''}
                    </div>
                    <div class="status-item-actions">
                        ${app.status === 'pending' || app.status === 'shortlisted' ? 
                            `<button class="cancel-btn" onclick="withdrawApplication(${app.application_id})">Withdraw</button>` : ''}
                    </div>
                </div>
            `;
        }
    });
    
    // Show empty state if no applications
    Object.entries(statusLists).forEach(([status, list]) => {
        if (list.innerHTML === '') {
            list.innerHTML = `<div class="no-content">No ${status} applications</div>`;
        }
    });
}

function withdrawApplication(applicationId) {
    if (confirm('Are you sure you want to withdraw this application?')) {
        fetch('../../backend/housing_management.php?action=withdraw_application', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: applicationId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Application withdrawn successfully');
                    fetchMyApplications();
                    fetchHousingStats();
                } else {
                    alert('Failed to withdraw application: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error withdrawing application:', error);
                alert('Failed to withdraw application. Please try again.');
            });
    }
}

// ====== Owner Dashboard Functions ======
function loadOwnerDashboard() {
    fetchMyHousingPosts();
}

function fetchMyHousingPosts() {
    fetch('../../backend/owner_dashboard.php?action=get_my_housing_posts')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMyHousingPosts(data.posts);
            }
        })
        .catch(error => {
            console.error('Error fetching housing posts:', error);
        });
}

function displayMyHousingPosts(posts) {
    const container = document.getElementById('myHousingList');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (posts.length === 0) {
        container.innerHTML = '<div class="no-content">No housing posts yet.</div>';
        return;
    }
    
    posts.forEach(post => {
        container.innerHTML += `
            <div class="card glass-card owner-housing-card">
                <div class="card-header">
                    <h3>${post.title}</h3>
                    <span class="availability-badge ${post.availability}">${post.availability}</span>
                </div>
                <div class="card-body">
                    <p><strong>Location:</strong> ${post.location}</p>
                    <p><strong>Rent:</strong> ৳${post.rent}</p>
                    <p><strong>Type:</strong> ${post.property_type}</p>
                    <div class="application-stats">
                        <span class="stat-item">Pending: ${post.pending_applications}</span>
                        <span class="stat-item">Shortlisted: ${post.shortlisted_applications}</span>
                        <span class="stat-item">Accepted: ${post.accepted_applications}</span>
                    </div>
                </div>
                <div class="card-actions">
                    <button class="details-btn" onclick="viewApplications(${post.service_id})">
                        View Applications (${post.total_applications})
                    </button>
                    <button class="delete-btn" onclick="deletePost(${post.service_id})">Delete</button>
                </div>
            </div>
        `;
    });
}

function viewApplications(housingId) {
    fetch(`../../backend/owner_dashboard.php?action=get_applications_for_housing&housing_id=${housingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showApplicationsModal(data.applications, housingId);
            }
        })
        .catch(error => {
            console.error('Error fetching applications:', error);
        });
}

function showApplicationsModal(applications, housingId) {
    const modal = document.createElement('div');
    modal.className = 'modal applications-modal';
    modal.innerHTML = `
        <div class="modal-content glass-card">
            <div class="modal-header">
                <h3>Applications for Housing</h3>
                <button class="close-btn" onclick="closeApplicationsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="applications-tabs">
                    <button class="app-tab-btn active" onclick="showAppTab('pending')">Pending (${applications.pending.length})</button>
                    <button class="app-tab-btn" onclick="showAppTab('shortlisted')">Shortlisted (${applications.shortlisted.length})</button>
                    <button class="app-tab-btn" onclick="showAppTab('accepted')">Accepted (${applications.accepted.length})</button>
                    <button class="app-tab-btn" onclick="showAppTab('rejected')">Rejected (${applications.rejected.length})</button>
                </div>
                <div class="applications-content">
                    ${generateApplicationsContent(applications)}
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
}

function generateApplicationsContent(applications) {
    let content = '';
    
    Object.entries(applications).forEach(([status, apps]) => {
        const isActive = status === 'pending' ? 'active' : 'hidden';
        content += `<div id="app-${status}" class="app-tab-section ${isActive}">`;
        
        if (apps.length === 0) {
            content += `<div class="no-content">No ${status} applications</div>`;
        } else {
            apps.forEach(app => {
                content += `
                    <div class="application-item">
                        <div class="applicant-info">
                            <h4>${app.applicant_name}</h4>
                            ${status === 'shortlisted' || status === 'accepted' ? `
                                <div class="contact-info-highlight">
                                    <p class="phone-highlight"><strong>📞 Phone:</strong> <span class="phone-number">${app.applicant_phone}</span></p>
                                    <p><strong>📧 Email:</strong> ${app.applicant_email}</p>
                                </div>
                            ` : `
                                <p><strong>Email:</strong> ${app.applicant_email}</p>
                            `}
                            <p><strong>📍 Location:</strong> ${app.applicant_location}</p>
                            <p><strong>📅 Applied:</strong> ${new Date(app.created_at).toLocaleDateString()}</p>
                            ${app.message ? `<p><strong>💬 Message:</strong> ${app.message}</p>` : ''}
                        </div>
                        <div class="application-actions">
                            ${status === 'pending' ? `
                                <button class="shortlist-btn" onclick="shortlistApplicant(${app.application_id})">Shortlist</button>
                                <button class="reject-btn" onclick="rejectApplicant(${app.application_id})">Reject</button>
                            ` : ''}
                            ${status === 'shortlisted' ? `
                                <button class="confirm-btn" onclick="confirmTenant(${app.application_id})">Confirm Tenant</button>
                                <button class="reject-btn" onclick="rejectApplicant(${app.application_id})">Reject</button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
        }
        
        content += '</div>';
    });
    
    return content;
}

function shortlistApplicant(applicationId) {
    fetch('../../backend/housing_management.php?action=shortlist_applicant', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ application_id: applicationId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Applicant shortlisted successfully');
                closeApplicationsModal();
                loadOwnerDashboard();
            } else {
                alert('Failed to shortlist applicant: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error shortlisting applicant:', error);
        });
}

function confirmTenant(applicationId) {
    const startDate = prompt('Enter tenancy start date (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
    if (startDate) {
        fetch('../../backend/housing_management.php?action=confirm_tenant', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: applicationId, start_date: startDate })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Tenant confirmed successfully');
                    closeApplicationsModal();
                    loadOwnerDashboard();
                } else {
                    alert('Failed to confirm tenant: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error confirming tenant:', error);
            });
    }
}

function rejectApplicant(applicationId) {
    if (confirm('Are you sure you want to reject this applicant?')) {
        fetch('../../backend/housing_management.php?action=reject_applicant', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: applicationId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Applicant rejected successfully');
                    closeApplicationsModal();
                    loadOwnerDashboard();
                } else {
                    alert('Failed to reject applicant: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error rejecting applicant:', error);
            });
    }
}

function showAppTab(status) {
    // Hide all app tab sections
    document.querySelectorAll('.app-tab-section').forEach(section => {
        section.classList.add('hidden');
        section.classList.remove('active');
    });
    
    // Show selected section
    const targetSection = document.getElementById(`app-${status}`);
    if (targetSection) {
        targetSection.classList.remove('hidden');
        targetSection.classList.add('active');
    }
    
    // Update tab buttons
    document.querySelectorAll('.app-tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase().includes(status)) {
            btn.classList.add('active');
        }
    });
}

function closeApplicationsModal() {
    const modal = document.querySelector('.applications-modal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

// ====== Profile Dropdown Functions ======
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        fetch('../../backend/logout.php', {
            method: 'POST'
        }).then(() => {
            window.location.href = '../../Login Page/login.html';
        }).catch(() => {
            // Fallback logout
            window.location.href = '../../Login Page/login.html';
        });
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userProfile = document.querySelector('.user-profile');
    const dropdown = document.getElementById('userDropdown');
    
    if (userProfile && dropdown && !userProfile.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// ====== Stat Card Navigation Functions ======
function navigateToStatus(statusType) {
    // Switch to status tab
    showSection('status');
    
    // Switch to specific status sub-tab
    setTimeout(() => {
        showStatusTab(statusType);
    }, 100);
}

// Add click handlers to stat cards
function addStatCardClickHandlers() {
    const statCards = document.querySelectorAll('.stat-card');
    console.log('Found stat cards:', statCards.length);
    
    statCards.forEach((card, index) => {
        const title = card.querySelector('.stat-title')?.textContent?.toLowerCase();
        console.log('Stat card title:', title);
        
        if (title) {
            card.style.cursor = 'pointer';
            // Remove any existing click handlers
            card.replaceWith(card.cloneNode(true));
            const newCard = document.querySelectorAll('.stat-card')[index];
            newCard.style.cursor = 'pointer';
            
            newCard.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Stat card clicked:', title);
                
                if (title.includes('pending')) {
                    console.log('Navigating to pending status');
                    navigateToStatus('pending');
                } else if (title.includes('applied') || title.includes('requests')) {
                    console.log('Navigating to applied requests');
                    navigateToStatus('pending'); // Show all applications
                } else if (title.includes('confirmed')) {
                    console.log('Navigating to confirmed status');
                    navigateToStatus('confirmed');
                } else if (title.includes('cancelled')) {
                    console.log('Navigating to cancelled status');
                    navigateToStatus('cancelled');
                } else if (title.includes('nearby')) {
                    console.log('Showing nearby housing');
                    showNearbyHousing();
                }
            });
        }
    });
}

// ====== Show Nearby Housing ======
function showNearbyHousing() {
    // Make sure we're on the Find House tab
    showSection('find');
    
    // Get user's generalized location and filter housing data
    fetch("../../backend/fetch_nearby_housing.php")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log('Nearby housing data:', data);
                
                // Use the already filtered housing data from server
                const userLocation = data.user_area;
                const nearbyHousing = data.housing; // Already filtered on server side
                
                console.log('User location:', userLocation);
                console.log('Server-filtered nearby housing:', nearbyHousing);
                
                displayHousingData(nearbyHousing);
                
                // Update search location to show user's area
                if (userLocation) {
                    const searchLocation = document.getElementById('searchLocation');
                    if (searchLocation) {
                        searchLocation.value = userLocation;
                    }
                }
                
                // Show a message about the filtering
                const housingList = document.getElementById('housingList');
                if (nearbyHousing.length > 0) {
                    housingList.insertAdjacentHTML('afterbegin', 
                        `<div class="filter-info" style="background: rgba(106, 186, 157, 0.2); padding: 10px; border-radius: 8px; margin-bottom: 15px; color: white;">
                            <i class="fas fa-map-marker-alt"></i> Showing ${nearbyHousing.length} housing options in your area: ${userLocation}
                            <button onclick="clearNearbyFilter()" style="float: right; background: transparent; border: 1px solid white; color: white; padding: 4px 8px; border-radius: 4px; cursor: pointer;">Show All</button>
                        </div>`
                    );
                } else {
                    housingList.innerHTML = `<div class="no-content">No housing posts found in your area (${userLocation}). ${userLocation ? 'Try browsing all available housing.' : 'Please set your generalized location in your profile.'}</div>`;
                }
            } else {
                console.error('Failed to fetch nearby housing:', data.message);
                // Fallback: show all housing
                displayHousingData(allHousingData);
            }
        })
        .catch(error => {
            console.error('Error fetching nearby housing:', error);
            // Fallback: show all housing
            displayHousingData(allHousingData);
        });
}

function clearNearbyFilter() {
    const searchLocation = document.getElementById('searchLocation');
    if (searchLocation) {
        searchLocation.value = '';
    }
    displayHousingData(allHousingData);
}

// Debug function to test nearby housing logic
function debugNearbyHousing() {
    fetch("../../backend/debug_nearby_housing.php")
        .then(res => res.json())
        .then(data => {
            console.log('=== NEARBY HOUSING DEBUG ===');
            console.log('Current user:', data.current_user);
            console.log('User generalized location:', data.user_generalized_location);
            console.log('Total housing posts:', data.total_housing_posts);
            console.log('Nearby count:', data.nearby_count);
            console.log('Nearby housing:', data.nearby_housing);
            console.log('All housing locations:', data.all_housing_locations);
            console.log('=== END DEBUG ===');
            
            // Replace alert with popup card
            if (typeof showPopupCard === 'function') {
                showPopupCard(`User Location: ${data.user_generalized_location || 'Not set'}<br>Total Housing: ${data.total_housing_posts}<br>Nearby Housing: ${data.nearby_count}<br>Check console for detailed info.`, 'Nearby Housing Debug');
            } else {
                console.log('DEBUG POPUP:', data);
            }
        })
        .catch(error => {
            console.error('Debug error:', error);
        });
}

// ====== Load My Confirmed Housing ======
function loadMyConfirmedHousing() {
    fetch("../../backend/get_my_confirmed_housing.php")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayMyHouseInfo(data);
            } else {
                console.error('Failed to load confirmed housing:', data.message);
                displayMyHouseInfo({ has_confirmed_housing: false });
            }
        })
        .catch(error => {
            console.error('Error loading confirmed housing:', error);
            displayMyHouseInfo({ has_confirmed_housing: false });
        });
}

function displayMyHouseInfo(data) {
    const myHouseInfo = document.getElementById('myHouseInfo');
    if (!myHouseInfo) return;
    
    if (!data.has_confirmed_housing) {
        myHouseInfo.innerHTML = `
            <div class="no-content">
                <p>No confirmed housing yet.</p>
                <small>Apply for housing and wait for confirmation to see details here.</small>
            </div>
        `;
        return;
    }
    
    const housing = data.housing;
    const coordinates = housing.coordinates ? `<br><small style="color: rgba(255,255,255,0.7);">📍 ${housing.coordinates}</small>` : '';
    const generalizedLocation = housing.generalized_location ? `<br><small style="color: rgba(106, 186, 157, 0.9);">[${housing.generalized_location}]</small>` : '';
    
    myHouseInfo.innerHTML = `
        <div class="confirmed-housing-details">
            <div class="housing-header">
                <h4 style="color: #fff; margin: 0 0 8px 0;">${housing.title}</h4>
                <span class="status-badge confirmed">✅ Confirmed</span>
            </div>
            
            <div class="housing-location" style="margin: 8px 0;">
                <strong>📍 Location:</strong> ${housing.location}
                ${coordinates}
                ${generalizedLocation}
            </div>
            
            <div class="housing-basic-info" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 12px 0;">
                <div><strong>Property Type:</strong> ${housing.property_type}</div>
                <div><strong>Rent:</strong> ৳${Number(housing.rent).toLocaleString()}/month</div>
                <div><strong>Bedrooms:</strong> ${housing.bedrooms}</div>
                <div><strong>Bathrooms:</strong> ${housing.bathrooms}</div>
                <div><strong>Size:</strong> ${housing.size_sqft} sqft</div>
                <div><strong>Floor:</strong> ${housing.floor_no}/${housing.total_floors}</div>
            </div>
            
            <div class="housing-financial" style="margin: 12px 0;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div><strong>Service Charge:</strong> ৳${Number(housing.service_charge).toLocaleString()}</div>
                    <div><strong>Advance Deposit:</strong> ৳${Number(housing.advance_deposit).toLocaleString()}</div>
                </div>
            </div>
            
            <div class="housing-owner" style="margin: 12px 0; padding: 8px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                <strong>Owner Contact:</strong><br>
                <small>Name: ${housing.owner.name}</small><br>
                <small>Email: ${housing.owner.email}</small><br>
                ${housing.owner.phone ? `<small>Phone: ${housing.owner.phone}</small>` : ''}
            </div>
            
            <div class="housing-dates" style="margin: 8px 0; font-size: 12px; color: rgba(255,255,255,0.7);">
                <div>Applied: ${new Date(housing.application_date).toLocaleDateString()}</div>
                <div>Confirmed: ${new Date(housing.confirmed_date).toLocaleDateString()}</div>
                <div>Available from: ${new Date(housing.available_from).toLocaleDateString()}</div>
            </div>
            
            <div class="housing-actions" style="margin-top: 12px;">
                <button class="details-btn" onclick="viewFullHousingDetails(${housing.housing_id})">
                    <i class="fas fa-eye"></i> View Full Details
                </button>
            </div>
        </div>
    `;
}

function viewFullHousingDetails(housingId) {
    // Use existing housing details modal
    viewHousingDetails(housingId);
}

window.onload = () => {
    fetchHousing();
    fetchHousingStats();
    loadOwnerDashboard();
    fetchMyApplications();
    loadExpenses();
    loadMyConfirmedHousing(); // Load confirmed housing info
    initializeNearbyHousesCard(); // Initialize nearby houses card
    
    // Add stat card click handlers after a short delay to ensure DOM is ready
    setTimeout(() => {
        addStatCardClickHandlers();
    }, 500);
    
    // Add debug button (temporary)
    setTimeout(() => {
        const debugBtn = document.createElement('button');
        debugBtn.textContent = 'Debug Nearby Housing';
        debugBtn.onclick = debugNearbyHousing;
        debugBtn.style.cssText = 'position: fixed; top: 10px; right: 10px; z-index: 9999999; background: red; color: white; padding: 5px; border: none; border-radius: 4px; cursor: pointer;';
        document.body.appendChild(debugBtn);
    }, 1000);
};

// ====== Nearby Houses Card Functions ======
function initializeNearbyHousesCard() {
    // Create animated chart bars
    createNearbyChart();
    
    // Load nearby houses data
    fetchNearbyHousesData();
    
    // Set user location
    updateUserLocation();
}

function createNearbyChart() {
    const chartContainer = document.getElementById('nearbyChart');
    if (!chartContainer) return;
    
    // Clear existing bars
    chartContainer.innerHTML = '';
    
    // Create 10 animated bars with varying heights
    const barHeights = [25, 45, 35, 55, 30, 50, 40, 35, 45, 25];
    
    barHeights.forEach((height, index) => {
        const bar = document.createElement('div');
        bar.className = 'nearby-bar';
        bar.style.height = height + 'px';
        bar.style.animationDelay = (index * 0.1) + 's';
        
        // Add hover tooltip
        bar.title = `${Math.floor(Math.random() * 5) + 1} houses`;
        
        chartContainer.appendChild(bar);
    });
}

function fetchNearbyHousesData() {
    fetch('../../backend/fetch_nearby_housing.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNearbyStats(data.nearby_count, data.breakdown);
            } else {
                console.error('Failed to fetch nearby houses:', data.message);
                // Set default values
                updateNearbyStats(24, { new: 3, affordable: 12, premium: 9 });
            }
        })
        .catch(error => {
            console.error('Error fetching nearby houses:', error);
            // Set default values
            updateNearbyStats(24, { new: 3, affordable: 12, premium: 9 });
        });
}

function updateNearbyStats(totalCount, breakdown) {
    // Update main count
    document.getElementById('statNearby').textContent = totalCount;
    
    // Update mini stats
    document.getElementById('nearbyNew').textContent = breakdown.new || 3;
    document.getElementById('nearbyAffordable').textContent = breakdown.affordable || 12;
    document.getElementById('nearbyPremium').textContent = breakdown.premium || 9;
}

function updateUserLocation() {
    // This would typically get the user's location from their profile
    // For now, we'll use a placeholder
    const locationElement = document.getElementById('userLocation');
    if (locationElement) {
        locationElement.textContent = 'Dhaka North'; // This should come from user data
    }
}

function refreshNearbyHouses() {
    // Show loading state
    const refreshBtn = document.querySelector('.nearby-btn.secondary');
    const originalContent = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    refreshBtn.disabled = true;
    
    // Simulate refresh
    setTimeout(() => {
        fetchNearbyHousesData();
        createNearbyChart();
        
        // Reset button
        refreshBtn.innerHTML = originalContent;
        refreshBtn.disabled = false;
        
        // Show success feedback
        showNotification('Nearby houses refreshed!', 'success');
    }, 1500);
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#00b894' : '#667eea'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
