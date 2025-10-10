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

        const propertyDetails = `
            <div class="property-details">
                <span class="detail-item">${h.bedrooms} Bed${h.bedrooms > 1 ? 's' : ''}</span>
                <span class="detail-item">${h.bathrooms} Bath${h.bathrooms > 1 ? 's' : ''}</span>
                ${h.size_sqft ? `<span class="detail-item">${h.size_sqft} sqft</span>` : ''}
                <span class="detail-item">${h.furnished_status}</span>
            </div>
        `;

        const availabilityInfo = h.available_from ?
            `<p class="availability">Available from: ${new Date(h.available_from).toLocaleDateString()}</p>` : '';

        const negotiableText = h.negotiable ? '<span class="negotiable">Negotiable</span>' : '';

        list.innerHTML += `
            <div class="card housing-card">
                <div class="card-header">
                    <h3>${h.title}</h3>
                    ${verificationBadge}
                </div>
                <div class="card-body">
                    <p class="location">📍 ${h.location}</p>
                    <p class="rent">Rent: ৳${h.rent}</p>
                    ${propertyDetails}
                    <p class="description">${h.description}</p>
                    ${availabilityInfo}
                    <div class="card-footer">
                        ${negotiableText}
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
    // Update applicant stats
    document.getElementById('statPending').textContent = applicantStats.pending || 0;
    document.getElementById('statApplied').textContent = (applicantStats.pending || 0) + (applicantStats.shortlisted || 0) + (applicantStats.accepted || 0);
    document.getElementById('statConfirmed').textContent = applicantStats.accepted || 0;
    document.getElementById('statCancelled').textContent = (applicantStats.rejected || 0) + (applicantStats.withdrawn || 0);
    
    // Update status tab counts
    document.getElementById('pendingCount').textContent = applicantStats.pending || 0;
    document.getElementById('confirmedCount').textContent = applicantStats.accepted || 0;
    document.getElementById('cancelledCount').textContent = applicantStats.withdrawn || 0;
    document.getElementById('rejectedCount').textContent = applicantStats.rejected || 0;
}

// ====== Fetch Housing ======
function fetchHousing() {
    fetch("../../backend/fetch_housing.php")
        .then(res => res.json())
        .then(data => {
            allHousingData = data;
            displayHousingData(data);
            fetchHousingStats();
        })
        .catch(error => {
            console.error('Error fetching housing data:', error);
            document.getElementById("housingList").innerHTML = '<div class="error">Failed to load housing data. Please try again.</div>';
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
    const modal = document.createElement('div');
    modal.className = 'modal';
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
                </div>
            </div>
            <div class="modal-footer">
                <button class="details-btn" onclick="closeHousingDetailsModal()">Close</button>
                <button class="apply-btn" onclick="applyForHousing(${housing.id}); closeHousingDetailsModal();">Apply Now</button>
            </div>
        </div>`;
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
}

function closeHousingDetailsModal() {
    const modal = document.querySelector('.modal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
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

    fetch('../../backend/delete_my_house.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: housingId })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Housing post deleted successfully.');
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
    window.location.href = '../../Modules/Jobs/post_job.php';
}
function openPostForm() { document.getElementById("postModal")?.classList.remove("hidden"); }
function closePostForm() { document.getElementById("postModal")?.classList.add("hidden"); }

document.getElementById("postHousingForm")?.addEventListener("submit", e => {
    e.preventDefault();
    fetch("../../backend/post_housing.php", {
        method: "POST",
        body: new FormData(e.target)
    }).then(r => r.text()).then(msg => {
        alert(msg);
        closePostForm();
        fetchHousing();
    });
});

// ... (rest of your code for expenses, stats, dropdown, etc. remains unchanged)

// ====== Status Management Functions ======
function showStatusTab(status) {
    // Hide all status sections
    document.querySelectorAll('.status-tab-section').forEach(sec => {
        sec.classList.add('hidden');
        sec.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(status).classList.remove('hidden');
    document.getElementById(status).classList.add('active');
    
    // Update tab buttons
    document.querySelectorAll('.status-tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
}

function refreshStatus() {
    fetchMyApplications();
    fetchHousingStats();
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
                            <p><strong>Phone:</strong> ${app.applicant_phone}</p>
                            <p><strong>Email:</strong> ${app.applicant_email}</p>
                            <p><strong>Location:</strong> ${app.applicant_location}</p>
                            <p><strong>Applied:</strong> ${new Date(app.created_at).toLocaleDateString()}</p>
                            ${app.message ? `<p><strong>Message:</strong> ${app.message}</p>` : ''}
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

function showAppTab(status) {
    document.querySelectorAll('.app-tab-section').forEach(sec => {
        sec.classList.add('hidden');
        sec.classList.remove('active');
    });
    document.getElementById(`app-${status}`).classList.remove('hidden');
    document.getElementById(`app-${status}`).classList.add('active');
    
    document.querySelectorAll('.app-tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
}

function shortlistApplicant(applicationId) {
    fetch('../../backend/housing_management.php?action=shortlist', {
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

function closeApplicationsModal() {
    const modal = document.querySelector('.applications-modal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

window.onload = () => {
    fetchHousing();
    fetchHousingStats();
    loadOwnerDashboard();
    fetchMyApplications();
    loadExpenses();
};
