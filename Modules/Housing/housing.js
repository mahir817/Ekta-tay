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
    fetch("../../backend/get_housing_stats.php")
        .then(res => res.json())
        .then(stats => {
            updateFindTabStats(stats);
        })
        .catch(error => {
            console.error('Error fetching housing stats:', error);
            const fallbackStats = {
                pending: 0,
                applied: 0,
                confirmed: 0,
                cancelled: 0,
                nearby: allHousingData.length
            };
            updateFindTabStats(fallbackStats);
        });
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
    if (confirm('Are you sure you want to apply for this housing?')) {
        fetch('../../backend/apply_housing.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ housing_id: housingId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Application submitted successfully!');
                    fetchHousing();
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

window.onload = () => {
    fetchHousing();
    loadExpenses();
};
