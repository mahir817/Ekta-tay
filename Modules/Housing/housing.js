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


// ====== Fetch Housing ======
function fetchHousing() {
    fetch("../../backend/fetch_housing.php")
        .then(res => res.json())
        .then(data => {
            let list = document.getElementById("housingList");
            list.innerHTML = "";
            data.forEach(h => {
                list.innerHTML += `
          <div class="card">
            <h3>${h.title}</h3>
            <p>${h.location}</p>
            <p>Rent: ${h.rent} BDT</p>
            ${h.khotiyan ? `<p>Verified ✅ (${h.khotiyan})</p>` : ""}
            <button>Apply</button>
          </div>`;
            });

            // demo counters from dataset
            const stats = calculateStatsFromHousing(data);
            updateFindTabStats(stats);
        });
}

// ====== Post Housing ======
function redirectToPostService() {
    // Redirect to unified posting page (frontend form), adjust path if needed
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

// ====== Expenses ======
function openExpenseForm() { document.getElementById("expenseModal").classList.remove("hidden"); }
function closeExpenseForm() { document.getElementById("expenseModal").classList.add("hidden"); }

document.getElementById("expenseForm")?.addEventListener("submit", e => {
    e.preventDefault();
    fetch("../../backend/add_expense.php", {
        method: "POST",
        body: new FormData(e.target)
    }).then(r => r.text()).then(msg => {
        alert(msg);
        closeExpenseForm();
        loadExpenses();
    });
});

function loadExpenses() {
    fetch("../../backend/fetch_expenses.php")
        .then(res => res.json())
        .then(data => {
            // Build legend and total for donut
            renderExpenseDonut(data);
        });
}

window.onload = () => {
    fetchHousing();
    loadExpenses();
};

// ===== New Helpers for redesigned UI =====
function calculateStatsFromHousing(items) {
    // Placeholder logic: derive counters from flags if present, else demo values
    const stats = {
        pending: 0,
        applied: 0,
        confirmed: 0,
        cancelled: 0,
        nearby: 24
    };
    items.forEach(it => {
        const status = (it.status || '').toLowerCase();
        if (status === 'pending') stats.pending++;
        else if (status === 'applied') stats.applied++;
        else if (status === 'confirmed') stats.confirmed++;
        else if (status === 'cancelled') stats.cancelled++;
    });
    return stats;
}

function updateFindTabStats(stats) {
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = String(val); };
    set('statPending', stats.pending);
    set('statApplied', stats.applied);
    set('statConfirmed', stats.confirmed);
    set('statCancelled', stats.cancelled);
    set('statNearby', stats.nearby);

    // Add click handlers to status cards to redirect to status page
    addStatusCardClickHandlers();
}

function addStatusCardClickHandlers() {
    // Add click handler to pending card
    const pendingCard = document.querySelector('.stat-card:nth-child(1)');
    if (pendingCard) {
        pendingCard.style.cursor = 'pointer';
        pendingCard.onclick = () => {
            showSection('status');
            setTimeout(() => showStatusTab('pending'), 100);
        };
    }

    // Add click handler to applied card
    const appliedCard = document.querySelector('.stat-card:nth-child(2)');
    if (appliedCard) {
        appliedCard.style.cursor = 'pointer';
        appliedCard.onclick = () => {
            showSection('status');
            setTimeout(() => showStatusTab('pending'), 100);
        };
    }

    // Add click handler to confirmed card
    const confirmedCard = document.querySelector('.stat-card:nth-child(3)');
    if (confirmedCard) {
        confirmedCard.style.cursor = 'pointer';
        confirmedCard.onclick = () => {
            showSection('status');
            setTimeout(() => showStatusTab('confirmed'), 100);
        };
    }

    // Add click handler to cancelled card
    const cancelledCard = document.querySelector('.stat-card:nth-child(4)');
    if (cancelledCard) {
        cancelledCard.style.cursor = 'pointer';
        cancelledCard.onclick = () => {
            showSection('status');
            setTimeout(() => showStatusTab('cancelled'), 100);
        };
    }
}

function calculateSplit() {
    const total = parseFloat(document.getElementById('totalRent')?.value || '0');
    const mates = parseInt(document.getElementById('numRoommates')?.value || '0');
    const out = document.getElementById('splitResult');
    const container = document.getElementById('roommatesContainer');
    if (!out) return;
    if (!total || !mates || mates <= 0) {
        out.textContent = 'Enter total and roommates to calculate.';
        if (container) container.innerHTML = '';
        return;
    }
    const share = Math.ceil((total / mates));
    out.textContent = `Each pays: ৳${share}`;

    // Generate detailed roommate inputs
    if (container) {
        container.innerHTML = '';
        const list = document.createElement('div');
        for (let i = 1; i <= mates; i++) {
            const row = document.createElement('div');
            row.style.display = 'grid';
            row.style.gridTemplateColumns = '1fr 1fr';
            row.style.gap = '8px';
            row.style.margin = '6px 0';

            const name = document.createElement('input');
            name.type = 'text';
            name.placeholder = `Roommate ${i} name`;
            name.dataset.role = 'roommate-name';

            const amount = document.createElement('input');
            amount.type = 'number';
            amount.placeholder = `Amount (default ${share})`;
            amount.value = String(share);
            amount.min = '0';
            amount.step = '1';
            amount.dataset.role = 'roommate-amount';

            row.appendChild(name);
            row.appendChild(amount);
            list.appendChild(row);
        }

        // Summary row
        const summary = document.createElement('div');
        summary.style.marginTop = '8px';
        summary.style.fontWeight = '600';
        summary.id = 'roommatesSummary';
        container.appendChild(list);
        container.appendChild(summary);

        const updateSummary = () => {
            const amounts = Array.from(container.querySelectorAll("input[data-role='roommate-amount']"))
                .map(i => Number(i.value) || 0);
            const sum = amounts.reduce((a, b) => a + b, 0);
            const diff = total - sum;
            summary.textContent = `Allocated: ৳${sum} / ৳${total} (${diff === 0 ? 'balanced' : (diff > 0 ? 'remaining ৳' + diff : 'over by ৳' + (-diff))})`;
        };
        container.addEventListener('input', (e) => {
            const t = e.target;
            if (t && t.matches("input[data-role='roommate-amount']")) updateSummary();
        });
        updateSummary();
    }
}

function renderExpenseDonut(expenses) {
    const legend = document.getElementById('expenseLegend');
    const totalEl = document.getElementById('expenseTotal');
    if (!legend || !totalEl) return;

    // Group by status/category (fallback to name)
    const colorPalette = ['#667eea', '#764ba2', '#f5576c', '#f093fb', '#4ade80'];
    const groups = {};
    let total = 0;
    expenses.forEach((e) => {
        const key = (e.category || e.status || e.name || 'Other');
        const amt = Number(e.amount) || 0;
        total += amt;
        groups[key] = (groups[key] || 0) + amt;
    });

    totalEl.textContent = `৳${total}`;

    // Legend
    legend.innerHTML = '';
    const keys = Object.keys(groups);
    keys.forEach((k, idx) => {
        const color = colorPalette[idx % colorPalette.length];
        const item = document.createElement('div');
        item.className = 'legend-item';
        item.innerHTML = `<span class="legend-dot" style="background:${color}"></span>
            <span class="legend-text">${k}</span>
            <span class="legend-amount">৳${groups[k]}</span>`;
        legend.appendChild(item);
    });

    // Update donut gradient dynamically (optional enhancement)
    const donut = document.getElementById('expenseDonut');
    if (donut && keys.length) {
        let start = 0;
        const segments = keys.map((k) => groups[k]);
        const colors = keys.map((_, idx) => colorPalette[idx % colorPalette.length]);
        const gradientParts = segments.map((value, idx) => {
            const angleStart = start;
            const angleEnd = start + (value / total) * 360;
            start = angleEnd;
            return `${colors[idx]} ${angleStart}deg ${angleEnd}deg`;
        });
        donut.style.background = `conic-gradient(${gradientParts.join(', ')})`;
    }
}

// === Add roommates to expenses ===
function addRoommatesToExpenses() {
    const container = document.getElementById('roommatesContainer');
    if (!container) return;
    const nameInputs = Array.from(container.querySelectorAll("input[data-role='roommate-name']"));
    const amountInputs = Array.from(container.querySelectorAll("input[data-role='roommate-amount']"));
    if (!nameInputs.length || !amountInputs.length) {
        alert('Please calculate and fill roommate details first.');
        return;
    }

    const entries = nameInputs.map((n, idx) => {
        const name = (n.value || `Roommate ${idx + 1}`).trim();
        const amount = Number(amountInputs[idx]?.value || 0);
        return { name, amount };
    }).filter(e => e.amount > 0);

    if (!entries.length) {
        alert('No valid amounts to add.');
        return;
    }

    // Post each as an expense with category 'Rent'
    const requests = entries.map(e => {
        const form = new FormData();
        form.append('name', `Rent - ${e.name}`);
        form.append('amount', String(e.amount));
        form.append('due_date', new Date().toISOString().slice(0, 10));
        form.append('category', 'Rent');
        return fetch('../../backend/add_expense.php', { method: 'POST', body: form })
            .then(r => r.text());
    });

    Promise.all(requests)
        .then(() => {
            alert('Roommate rents added to expenses.');
            loadExpenses();
        })
        .catch(() => alert('Failed to add some expenses.'));
}

// ====== Edit My House ======
function openEditHouse() { document.getElementById('editHouseModal')?.classList.remove('hidden'); }
function closeEditHouse() { document.getElementById('editHouseModal')?.classList.add('hidden'); }

document.getElementById('editHouseForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = new FormData(e.target);
    fetch('../../backend/update_my_house.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(resp => {
            alert(resp.message || 'Saved');
            // Simple UI reflection
            const info = document.getElementById('myHouseInfo');
            if (info) {
                info.innerHTML = `<p><strong>Address:</strong> ${form.get('address') || '-'}<br>
                    <strong>Rent:</strong> ৳${form.get('rent') || '-'}<br>
                    <strong>Bedrooms:</strong> ${form.get('bedrooms') || '-'} | <strong>Bathrooms:</strong> ${form.get('bathrooms') || '-'}<br>
                    <strong>Notes:</strong> ${form.get('notes') || '-'}</p>`;
            }
            closeEditHouse();
        })
        .catch(() => {
            // Fallback local update
            const info = document.getElementById('myHouseInfo');
            if (info) {
                info.innerHTML = `<p><strong>Address:</strong> ${form.get('address') || '-'}<br>
                    <strong>Rent:</strong> ৳${form.get('rent') || '-'}<br>
                    <strong>Bedrooms:</strong> ${form.get('bedrooms') || '-'} | <strong>Bathrooms:</strong> ${form.get('bathrooms') || '-'}<br>
                    <strong>Notes:</strong> ${form.get('notes') || '-'}</p>`;
            }
            closeEditHouse();
        });
});

// ====== Status Page Functions ======
function showStatusTab(statusType) {
    // Hide all status tab sections
    document.querySelectorAll('.status-tab-section').forEach(sec => {
        sec.classList.add("hidden");
        sec.classList.remove("active");
    });

    // Show selected status section
    const activeSection = document.getElementById(statusType);
    if (activeSection) {
        activeSection.classList.remove("hidden");
        activeSection.classList.add("active");
    }

    // Remove active class from all status tab buttons
    document.querySelectorAll('.status-tab-btn').forEach(btn => btn.classList.remove("active"));

    // Add active class to clicked button
    const buttons = document.querySelectorAll('.status-tab-btn');
    buttons.forEach(btn => {
        const onclick = btn.getAttribute('onclick') || '';
        if (onclick.includes(statusType)) {
            btn.classList.add("active");
        }
    });

    // Load status data for the selected tab
    loadStatusData(statusType);
}

function loadStatusData(statusType) {
    // This would typically fetch from a backend endpoint
    // For now, we'll use mock data
    const mockData = {
        pending: [
            {
                id: 1,
                title: "Modern Apartment in Dhanmondi",
                location: "Dhanmondi, Dhaka",
                rent: "25000",
                appliedDate: "2024-01-15",
                status: "pending"
            },
            {
                id: 2,
                title: "Shared Room in Uttara",
                location: "Uttara, Dhaka",
                rent: "15000",
                appliedDate: "2024-01-20",
                status: "pending"
            }
        ],
        confirmed: [
            {
                id: 3,
                title: "Studio Apartment in Gulshan",
                location: "Gulshan, Dhaka",
                rent: "30000",
                appliedDate: "2024-01-10",
                confirmedDate: "2024-01-12",
                status: "confirmed"
            }
        ],
        cancelled: [
            {
                id: 4,
                title: "Room in Mirpur",
                location: "Mirpur, Dhaka",
                rent: "18000",
                appliedDate: "2024-01-05",
                cancelledDate: "2024-01-08",
                status: "cancelled"
            }
        ],
        rejected: [
            {
                id: 5,
                title: "Apartment in Banani",
                location: "Banani, Dhaka",
                rent: "35000",
                appliedDate: "2024-01-03",
                rejectedDate: "2024-01-06",
                status: "rejected"
            }
        ]
    };

    // Update status counts
    updateStatusCounts(mockData);

    const data = mockData[statusType] || [];
    const container = document.getElementById(statusType + 'List');

    if (!container) return;

    if (data.length === 0) {
        container.innerHTML = '<div class="no-status">No ' + statusType + ' applications found.</div>';
        return;
    }

    container.innerHTML = data.map(item => `
        <div class="status-item">
            <div class="status-item-header">
                <h4 class="status-item-title">${item.title}</h4>
                <span class="status-badge ${item.status}">${item.status}</span>
            </div>
            <div class="status-item-details">
                <p><strong>Location:</strong> ${item.location}</p>
                <p><strong>Rent:</strong> ৳${item.rent}</p>
                <p><strong>Applied:</strong> ${item.appliedDate}</p>
                ${item.confirmedDate ? `<p><strong>Confirmed:</strong> ${item.confirmedDate}</p>` : ''}
                ${item.cancelledDate ? `<p><strong>Cancelled:</strong> ${item.cancelledDate}</p>` : ''}
                ${item.rejectedDate ? `<p><strong>Rejected:</strong> ${item.rejectedDate}</p>` : ''}
            </div>
            <div class="status-item-actions">
                ${item.status === 'pending' ? `
                    <button class="status-btn danger" onclick="cancelApplication(${item.id})">Cancel</button>
                    <button class="status-btn secondary" onclick="viewDetails(${item.id})">View Details</button>
                ` : ''}
                ${item.status === 'confirmed' ? `
                    <button class="status-btn primary" onclick="contactLandlord(${item.id})">Contact Landlord</button>
                    <button class="status-btn secondary" onclick="viewDetails(${item.id})">View Details</button>
                ` : ''}
                ${item.status === 'cancelled' || item.status === 'rejected' ? `
                    <button class="status-btn secondary" onclick="viewDetails(${item.id})">View Details</button>
                    <button class="status-btn primary" onclick="reapply(${item.id})">Reapply</button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

function updateStatusCounts(mockData) {
    // Update count displays
    const setCount = (id, count) => {
        const element = document.getElementById(id);
        if (element) element.textContent = count;
    };

    setCount('pendingCount', mockData.pending.length);
    setCount('confirmedCount', mockData.confirmed.length);
    setCount('cancelledCount', mockData.cancelled.length);
    setCount('rejectedCount', mockData.rejected.length);
}

function refreshStatus() {
    // Get current active status tab
    const activeTab = document.querySelector('.status-tab-btn.active');
    if (activeTab) {
        const onclick = activeTab.getAttribute('onclick') || '';
        const statusType = onclick.match(/showStatusTab\('(\w+)'\)/)?.[1];
        if (statusType) {
            loadStatusData(statusType);
        }
    }
}

function cancelApplication(id) {
    if (confirm('Are you sure you want to cancel this application?')) {
        // This would typically make an API call to cancel the application
        alert('Application cancelled successfully.');
        refreshStatus();
    }
}

function contactLandlord(id) {
    // This would typically open a contact form or messaging interface
    alert('Contact landlord functionality would be implemented here.');
}

function viewDetails(id) {
    // This would typically open a detailed view modal
    alert('View details functionality would be implemented here for application ID: ' + id);
}

function reapply(id) {
    if (confirm('Are you sure you want to reapply for this housing?')) {
        // This would typically make an API call to reapply
        alert('Reapplication submitted successfully.');
        refreshStatus();
    }
}

// ====== User Dropdown Functions ======
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../../backend/logout.php';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function (event) {
    const dropdown = document.getElementById('userDropdown');
    const userProfile = document.querySelector('.user-profile');

    if (dropdown && userProfile && !userProfile.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});