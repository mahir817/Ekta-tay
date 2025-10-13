// Profile Page JavaScript
let profileData = null;
let currentTab = 'posts';
let currentPostType = 'all';
let currentAppStatus = 'all';

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadProfileData();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Edit profile form
    document.getElementById('editProfileForm').addEventListener('submit', handleEditProfile);
    
    // Add expense form
    document.getElementById('addExpenseForm').addEventListener('submit', handleAddExpense);
}

// Load profile data
async function loadProfileData() {
    try {
        showLoading();
        const response = await fetch('../backend/profile.php');
        const result = await response.json();
        
        if (result.success) {
            profileData = result.data;
            updateProfileDisplay();
            loadCurrentTabContent();
        } else {
            showError('Failed to load profile data: ' + result.message);
        }
    } catch (error) {
        showError('Error loading profile: ' + error.message);
    } finally {
        hideLoading();
    }
}

// Update profile display
function updateProfileDisplay() {
    const user = profileData.user;
    
    // Update profile header
    document.getElementById('profileName').textContent = user.name || 'No name';
    document.getElementById('profileLocation').textContent = user.generalized_location || user.location || 'Location not set';
    
    // Update avatar (always show initials since profile_img is not supported)
    const avatarElements = document.querySelectorAll('#profileAvatar, #sidebarProfile .rounded-full');
    avatarElements.forEach(el => {
        el.textContent = user.name ? user.name.charAt(0).toUpperCase() : 'U';
    });
    
    // Update capabilities badges
    updateCapabilitiesBadges();
    
    // Update about section
    updateAboutSection();
    
    // Update quick stats
    updateQuickStats();
}

// Update capabilities badges
function updateCapabilitiesBadges() {
    const badgesContainer = document.getElementById('capabilitiesBadges');
    const capabilities = profileData.capabilities || [];
    
    const capabilityIcons = {
        'find_room': '🏠 Find Housing',
        'offer_room': '🏠 Offer Housing',
        'find_job': '💼 Find Job',
        'post_job': '💼 Post Job',
        'find_tutor': '🎓 Find Tuition',
        'offer_tuition': '🎓 Offer Tuition',
        'food_service': '🍽️ Food Service',
        'expense_tracking': '💰 Expense Tracking'
    };
    
    badgesContainer.innerHTML = capabilities.map(cap => 
        `<span class="capability-badge">${capabilityIcons[cap] || cap}</span>`
    ).join('');
}

// Update about section
function updateAboutSection() {
    const aboutSection = document.getElementById('aboutSection');
    const user = profileData.user;
    
    aboutSection.innerHTML = `
        <div class="flex items-center justify-between py-2">
            <span class="text-white/70">Email</span>
            <span class="text-white">${user.email}</span>
        </div>
        <div class="flex items-center justify-between py-2">
            <span class="text-white/70">Phone</span>
            <span class="text-white">${user.phone || 'Not set'}</span>
        </div>
        <div class="flex items-center justify-between py-2">
            <span class="text-white/70">Gender</span>
            <span class="text-white">${user.gender ? user.gender.charAt(0).toUpperCase() + user.gender.slice(1) : 'Not set'}</span>
        </div>
        <div class="flex items-center justify-between py-2">
            <span class="text-white/70">Joined</span>
            <span class="text-white">${new Date(user.created_at).toLocaleDateString()}</span>
        </div>
    `;
}

// Update quick stats
function updateQuickStats() {
    const statsContainer = document.getElementById('quickStats');
    const stats = profileData.stats;
    
    statsContainer.innerHTML = `
        <div class="text-center">
            <div class="text-2xl font-bold text-white">${stats.total_posts}</div>
            <div class="text-white/60 text-sm">Posts</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-white">${stats.total_applications}</div>
            <div class="text-white/60 text-sm">Applications</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-white">${stats.pending_applications}</div>
            <div class="text-white/60 text-sm">Pending</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-white">${stats.total_expenses}</div>
            <div class="text-white/60 text-sm">Expenses</div>
        </div>
    `;
}

// Tab management
function showTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.classList.add('text-white/70', 'hover:text-white', 'border-transparent', 'hover:border-white/20');
        btn.classList.remove('text-white', 'border-blue-400');
    });
    
    event.target.classList.add('active');
    event.target.classList.remove('text-white/70', 'hover:text-white', 'border-transparent', 'hover:border-white/20');
    event.target.classList.add('text-white', 'border-blue-400');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
        content.classList.remove('active');
    });
    
    const targetTab = document.getElementById(tabName + 'Tab');
    if (targetTab) {
        targetTab.classList.remove('hidden');
        targetTab.classList.add('active');
    }
    
    currentTab = tabName;
    loadCurrentTabContent();
}

// Load current tab content
function loadCurrentTabContent() {
    switch(currentTab) {
        case 'posts':
            loadPosts();
            break;
        case 'applications':
            loadApplications();
            break;
        case 'expenses':
            loadExpenses();
            break;
        case 'capabilities':
            loadCapabilities();
            break;
        case 'settings':
            // Settings tab is static
            break;
    }
}

// Load posts
function loadPosts() {
    const container = document.getElementById('postsContainer');
    const posts = profileData.posts;
    
    let allPosts = [];
    if (currentPostType === 'all') {
        allPosts = [...posts.tuition, ...posts.job, ...posts.housing, ...posts.food];
    } else {
        allPosts = posts[currentPostType] || [];
    }
    
    if (allPosts.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>No posts found</h3>
                <p>You haven't created any posts yet. Create your first post to get started!</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = allPosts.map(post => `
        <div class="post-card">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h4 class="text-white font-semibold text-lg">${post.title}</h4>
                    <div class="flex items-center space-x-4 text-sm text-white/60 mt-1">
                        <span class="capitalize">${post.type}</span>
                        <span>${post.location}</span>
                        <span class="status-badge status-active">Active</span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-white font-semibold">৳${post.price || 'N/A'}</div>
                    <div class="text-white/60 text-sm">${post.application_count || 0} applications</div>
                </div>
            </div>
            <p class="text-white/80 text-sm mb-4">${post.description.substring(0, 150)}${post.description.length > 150 ? '...' : ''}</p>
            <div class="flex items-center justify-between">
                <span class="text-white/60 text-sm">${new Date(post.created_at).toLocaleDateString()}</span>
                <div class="space-x-2">
                    <button class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-lg hover:bg-blue-500/30 transition-colors">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </button>
                    <button class="px-3 py-1 bg-red-500/20 text-red-300 rounded-lg hover:bg-red-500/30 transition-colors">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Show post type
function showPostType(type) {
    // Update buttons
    document.querySelectorAll('.post-type-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-blue-500/20', 'text-blue-300');
        btn.classList.add('bg-white/10', 'text-white/70');
    });
    
    event.target.classList.add('active', 'bg-blue-500/20', 'text-blue-300');
    event.target.classList.remove('bg-white/10', 'text-white/70');
    
    currentPostType = type;
    loadPosts();
}

// Load applications
function loadApplications() {
    const container = document.getElementById('applicationsContainer');
    const applications = profileData.applications || [];
    
    let filteredApps = applications;
    if (currentAppStatus !== 'all') {
        filteredApps = applications.filter(app => app.app_status === currentAppStatus);
    }
    
    if (filteredApps.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-paper-plane"></i>
                <h3>No applications found</h3>
                <p>You haven't applied to any posts yet.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = filteredApps.map(app => `
        <div class="application-card">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h4 class="text-white font-semibold">${app.title}</h4>
                    <div class="flex items-center space-x-4 text-sm text-white/60 mt-1">
                        <span class="capitalize">${app.type}</span>
                        <span>${app.location}</span>
                        <span>by ${app.poster_name}</span>
                    </div>
                </div>
                <span class="status-badge status-${app.app_status}">${app.app_status}</span>
            </div>
            <p class="text-white/80 text-sm mb-3">${app.cover_letter ? app.cover_letter.substring(0, 100) + '...' : 'No cover letter'}</p>
            <div class="flex items-center justify-between text-sm">
                <span class="text-white/60">Applied: ${new Date(app.applied_at).toLocaleDateString()}</span>
                <span class="text-white font-semibold">৳${app.price || 'N/A'}</span>
            </div>
        </div>
    `).join('');
}

// Show application status
function showApplicationStatus(status) {
    // Update buttons
    document.querySelectorAll('.app-status-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    event.target.classList.add('active');
    
    currentAppStatus = status;
    loadApplications();
}

// Load expenses
async function loadExpenses() {
    try {
        const response = await fetch('../backend/user_expenses.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            updateExpenseSummary(data.summary);
            updateExpensesList(data.expenses);
            updateExpenseChart(data.category_breakdown);
        }
    } catch (error) {
        console.error('Error loading expenses:', error);
    }
}

// Update expense summary
function updateExpenseSummary(summary) {
    const container = document.getElementById('expenseSummary');
    
    container.innerHTML = `
        <div class="bg-white/5 rounded-xl p-4">
            <div class="text-2xl font-bold text-white">৳${parseFloat(summary.total_amount || 0).toLocaleString()}</div>
            <div class="text-white/60 text-sm">Total Expenses</div>
        </div>
        <div class="bg-white/5 rounded-xl p-4">
            <div class="text-2xl font-bold text-green-400">৳${parseFloat(summary.paid_amount || 0).toLocaleString()}</div>
            <div class="text-white/60 text-sm">Paid (${summary.paid_count || 0})</div>
        </div>
        <div class="bg-white/5 rounded-xl p-4">
            <div class="text-2xl font-bold text-red-400">৳${parseFloat(summary.unpaid_amount || 0).toLocaleString()}</div>
            <div class="text-white/60 text-sm">Unpaid (${summary.unpaid_count || 0})</div>
        </div>
    `;
}

// Update expenses list
function updateExpensesList(expenses) {
    const container = document.getElementById('expensesList');
    
    if (expenses.length === 0) {
        container.innerHTML = `
            <div class="text-center text-white/60 py-8">
                <i class="fas fa-wallet text-2xl mb-2"></i>
                <p>No expenses recorded</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = expenses.map(expense => `
        <div class="expense-item">
            <div class="flex-1">
                <div class="text-white font-medium">${expense.name}</div>
                <div class="text-white/60 text-sm">${new Date(expense.created_at).toLocaleDateString()}</div>
            </div>
            <div class="text-right">
                <div class="text-white font-semibold">৳${parseFloat(expense.amount).toLocaleString()}</div>
                <span class="status-badge status-${expense.status === 'paid' ? 'active' : 'pending'}">${expense.status}</span>
            </div>
        </div>
    `).join('');
}

// Update expense chart
function updateExpenseChart(categories) {
    const container = document.getElementById('expenseChart');
    
    if (categories.length === 0) {
        container.innerHTML = `
            <div class="text-center text-white/60">
                <i class="fas fa-chart-pie text-4xl mb-2"></i>
                <p>No expense data</p>
            </div>
        `;
        return;
    }
    
    // Simple list view for categories
    container.innerHTML = `
        <div class="space-y-2">
            ${categories.map(cat => `
                <div class="flex items-center justify-between p-2 bg-white/5 rounded-lg">
                    <span class="text-white/80">${cat.category}</span>
                    <span class="text-white font-semibold">৳${parseFloat(cat.total_amount).toLocaleString()}</span>
                </div>
            `).join('')}
        </div>
    `;
}

// Load capabilities
function loadCapabilities() {
    const container = document.getElementById('capabilitiesGrid');
    const userCapabilities = profileData.capabilities || [];
    
    const allCapabilities = [
        { name: 'find_room', label: 'Find Housing', icon: '🏠' },
        { name: 'offer_room', label: 'Offer Housing', icon: '🏠' },
        { name: 'find_job', label: 'Find Job', icon: '💼' },
        { name: 'post_job', label: 'Post Job', icon: '💼' },
        { name: 'find_tutor', label: 'Find Tuition', icon: '🎓' },
        { name: 'offer_tuition', label: 'Offer Tuition', icon: '🎓' },
        { name: 'food_service', label: 'Food Service', icon: '🍽️' },
        { name: 'expense_tracking', label: 'Expense Tracking', icon: '💰' }
    ];
    
    container.innerHTML = allCapabilities.map(cap => `
        <label class="flex items-center p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-colors cursor-pointer">
            <input type="checkbox" name="capabilities[]" value="${cap.name}" 
                   class="capability-checkbox mr-3" 
                   ${userCapabilities.includes(cap.name) ? 'checked' : ''}>
            <div class="flex items-center">
                <span class="text-2xl mr-3">${cap.icon}</span>
                <div>
                    <div class="text-white font-medium">${cap.label}</div>
                    <div class="text-white/60 text-sm">Access ${cap.label.toLowerCase()} features</div>
                </div>
            </div>
        </label>
    `).join('');
}

// Update capabilities
async function updateCapabilities() {
    const checkboxes = document.querySelectorAll('input[name="capabilities[]"]:checked');
    const capabilities = Array.from(checkboxes).map(cb => cb.value);
    
    try {
        const formData = new FormData();
        capabilities.forEach(cap => formData.append('capabilities[]', cap));
        
        const response = await fetch('../backend/update_capabilities.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            profileData.capabilities = result.data;
            updateCapabilitiesBadges();
            showSuccess('Capabilities updated successfully!');
        } else {
            showError('Failed to update capabilities: ' + result.message);
        }
    } catch (error) {
        showError('Error updating capabilities: ' + error.message);
    }
}

// Modal functions
function openEditProfileModal() {
    const user = profileData.user;
    
    // Populate form
    document.getElementById('editName').value = user.name || '';
    document.getElementById('editPhone').value = user.phone || '';
    document.getElementById('editLocation').value = user.location || '';
    document.getElementById('editGeneralizedLocation').value = user.generalized_location || '';
    document.getElementById('editGender').value = user.gender || '';
    
    document.getElementById('editProfileModal').classList.remove('hidden');
}

function closeEditProfileModal() {
    document.getElementById('editProfileModal').classList.add('hidden');
}

function openAddExpenseModal() {
    document.getElementById('addExpenseModal').classList.remove('hidden');
}

function closeAddExpenseModal() {
    document.getElementById('addExpenseModal').classList.add('hidden');
    document.getElementById('addExpenseForm').reset();
}

// Handle edit profile
async function handleEditProfile(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        
        const response = await fetch('../backend/update_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (result.data) {
                profileData.user = { ...profileData.user, ...result.data };
                updateProfileDisplay();
            }
            closeEditProfileModal();
            showSuccess('Profile updated successfully!');
        } else {
            showError('Failed to update profile: ' + result.message);
        }
    } catch (error) {
        showError('Error updating profile: ' + error.message);
    }
}

// Handle add expense
async function handleAddExpense(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        
        const response = await fetch('../backend/user_expenses.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeAddExpenseModal();
            showSuccess('Expense added successfully!');
            if (currentTab === 'expenses') {
                loadExpenses();
            }
        } else {
            showError('Failed to add expense: ' + result.message);
        }
    } catch (error) {
        showError('Error adding expense: ' + error.message);
    }
}

// Utility functions
function showLoading() {
    // Add loading indicators where needed
    console.log('Loading...');
}

function hideLoading() {
    // Remove loading indicators
    console.log('Loading complete');
}

function showSuccess(message) {
    // Create and show success notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg z-50';
    notification.innerHTML = `<i class="fas fa-check mr-2"></i>${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function showError(message) {
    // Create and show error notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-xl shadow-lg z-50';
    notification.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i>${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function confirmDeleteAccount() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        // Implement account deletion
        alert('Account deletion feature will be implemented');
    }
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../backend/logout.php';
    }
}
