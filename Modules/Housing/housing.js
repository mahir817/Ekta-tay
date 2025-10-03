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
function openPostForm() { document.getElementById("postModal").classList.remove("hidden"); }
function closePostForm() { document.getElementById("postModal").classList.add("hidden"); }

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
    if (!out) return;
    if (!total || !mates || mates <= 0) {
        out.textContent = 'Enter total and roommates to calculate.';
        return;
    }
    const share = Math.ceil((total / mates));
    out.textContent = `Each pays: ৳${share}`;
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
