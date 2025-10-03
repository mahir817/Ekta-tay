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
