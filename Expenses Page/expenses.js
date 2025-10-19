// =========================
// Sidebar helpers
// =========================
function toggleDropdown() {
  const dropdown = document.getElementById('userDropdown');
  if (dropdown) dropdown.classList.toggle('show');
}

function logout() {
  if (confirm('Are you sure you want to logout?')) {
    window.location.href = '../backend/logout.php';
  }
}

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  if (sidebar) sidebar.classList.toggle('mobile-open');
}

document.addEventListener('click', function (e) {
  const d = document.getElementById('userDropdown');
  const p = document.querySelector('.user-profile');
  if (d && p && !p.contains(e.target) && !d.contains(e.target)) {
    d.classList.remove('show');
  }
});

// =========================
// Calculator
// =========================
(function () {
  const calc = document.getElementById('calculator');
  const display = document.getElementById('calcDisplay');
  const toggleBtn = document.getElementById('toggleCalc');

  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      calc.style.display = calc.style.display === 'none' ? 'block' : 'none';
    });
  }

  document.querySelectorAll('#calculator button[data-key]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const k = btn.getAttribute('data-key');
      if (k === 'C') {
        display.value = '';
        return;
      }
      if (k === '=') {
        try {
          display.value = eval(display.value);
        } catch (e) {
          display.value = 'Error';
        }
        return;
      }
      display.value += k;
    });
  });
})();

// =========================
// Expense Handlers
// =========================

// ---- Add Expense ----
const expenseForm = document.getElementById('expenseForm');
if (expenseForm) {
  expenseForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(expenseForm);
    fetch('../backend/add_expense.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          loadExpensesWithChart();
          expenseForm.reset();
        } else {
          alert(data.message || 'Failed to add expense');
        }
      })
      .catch(() => alert('Network error while adding expense'));
  });
}

// ---- Filter Apply ----
document.getElementById('applyFilters')?.addEventListener('click', loadExpensesWithChart);

// ---- Load Expenses ----
function loadExpenses() {
  const params = new URLSearchParams({
    category: document.getElementById('filterCategory').value || '',
    status: document.getElementById('filterStatus').value || '',
    from: document.getElementById('filterFrom').value || '',
    to: document.getElementById('filterTo').value || '',
    sort: document.getElementById('sortBy').value || 'date_desc',
  });

  fetch('../backend/fetch_expenses.php?' + params, { credentials: 'same-origin' })
    .then((r) => r.json())
    .then((data) => {
      const list = document.getElementById('expensesList');
      list.innerHTML = '';

      if (!data || !data.success) {
        list.innerHTML =
          '<div class="glass-card" style="padding:12px;">No expenses found.</div>';
        return;
      }

      let sumMonth = 0,
        sumPending = 0,
        sumPaid = 0;

      (data.expenses || []).forEach((exp) => {
        const card = document.createElement('div');
        card.className = 'card glass-card';
        card.style.padding = '12px';

        const toggleStatus =
          exp.status === 'paid'
            ? 'unpaid'
            : exp.status === 'unpaid'
            ? 'pending'
            : 'paid';

        card.innerHTML = `
          <h3>${exp.title}</h3>
          <div class="job-meta">
            <span class="job-tag">${exp.category}</span>
            <span class="job-tag">${exp.status}</span>
            <span class="job-tag">${exp.date}</span>
          </div>
          <p class="salary">৳${Number(exp.amount).toLocaleString()}</p>
          <div style="display:flex; gap:8px;">
            <button class="apply-btn" onclick="markExpense('${exp.id}', '${toggleStatus}')">
              Mark ${toggleStatus.charAt(0).toUpperCase() + toggleStatus.slice(1)}
            </button>
            <button class="apply-btn" style="background:#c62828;" onclick="deleteExpense('${exp.id}')">
              Delete
            </button>
          </div>
        `;

        list.appendChild(card);

        const d = new Date(exp.date);
        const now = new Date();
        if (d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear())
          sumMonth += Number(exp.amount) || 0;
        if (exp.status === 'pending') sumPending += Number(exp.amount) || 0;
        if (exp.status === 'paid') sumPaid += Number(exp.amount) || 0;
      });

      document.getElementById('sumThisMonth').textContent =
        'This Month: ৳' + sumMonth.toLocaleString();
      document.getElementById('sumPending').textContent =
        'Pending: ৳' + sumPending.toLocaleString();
      document.getElementById('sumPaid').textContent =
        'Paid: ৳' + sumPaid.toLocaleString();
      document.getElementById('sumSavings').textContent =
        'Savings: ৳' + Math.max(0, sumMonth - sumPaid).toLocaleString();
    })
    .catch((err) => {
      console.error('Error loading expenses:', err);
      document.getElementById('expensesList').innerHTML =
        '<div class="glass-card" style="padding:12px;">Error loading data.</div>';
    });
}

// ---- Mark Expense (Paid / Unpaid / Pending) ----
function markExpense(id, status) {
  const sid = String(id);
  const payload = { id: sid, status: status };
  if (sid[0].toLowerCase() === 's') payload.source = 'shared';

  console.log('Updating status:', payload);

  fetch('../backend/mark_expense.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
    .then(async (res) => {
      let data;
      try {
        data = await res.json();
      } catch (e) {
        console.error('Invalid JSON from server', await res.text());
        alert('Server returned invalid JSON');
        return;
      }
      if (data.success) {
        loadExpenses();
      } else {
        alert(data.message || 'Failed to update expense status');
      }
    })
    .catch((err) => {
      console.error('Network error while updating status:', err);
      alert('Network error while updating status');
    });
}

// ---- Delete Expense ----
function deleteExpense(id) {
  if (!confirm('Are you sure you want to delete this expense?')) return;

  fetch('../backend/delete_expenses.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        loadExpenses();
      } else {
        alert(data.message || 'Failed to delete expense');
      }
    })
    .catch((err) => {
      console.error('Network error while deleting expense:', err);
      alert('Network error while deleting expense');
    });
}

// Toggle filter section
document.getElementById('toggleFilters')?.addEventListener('click', () => {
  const filterBox = document.getElementById('filterSection');
  if (filterBox.style.display === 'none' || filterBox.style.display === '') {
    filterBox.style.display = 'block';
  } else {
    filterBox.style.display = 'none';
  }
});


// Toggle Add Expense section
document.getElementById('toggleAddExpense')?.addEventListener('click', () => {
  const addBox = document.getElementById('addExpenseSection');
  if (addBox.style.display === 'none' || addBox.style.display === '') {
    addBox.style.display = 'block';
  } else {
    addBox.style.display = 'none';
  }
});


// =========================
// Enhanced Pie Chart
// =========================
function createPieChart(categories) {
  const canvas = document.getElementById('expensePieChart');
  const ctx = canvas.getContext('2d');
  const centerX = canvas.width / 2;
  const centerY = canvas.height / 2;
  const radius = 80;
  
  // Clear canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  
  if (!categories || categories.length === 0) {
    // Draw empty state
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
    ctx.fillStyle = 'rgba(255, 255, 255, 0.1)';
    ctx.fill();
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
    ctx.lineWidth = 2;
    ctx.stroke();
    
    updateChartLegend([]);
    document.getElementById('chartTotal').textContent = '৳0';
    return;
  }
  
  // Calculate total and percentages
  const total = categories.reduce((sum, cat) => sum + parseFloat(cat.amount || 0), 0);
  
  // Color palette
  const colors = [
    '#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe',
    '#43e97b', '#38f9d7', '#ffecd2', '#fcb69f', '#a8edea'
  ];
  
  let currentAngle = -Math.PI / 2; // Start from top
  
  categories.forEach((category, index) => {
    const percentage = parseFloat(category.amount || 0) / total;
    const sliceAngle = percentage * 2 * Math.PI;
    
    // Draw slice
    ctx.beginPath();
    ctx.moveTo(centerX, centerY);
    ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
    ctx.closePath();
    ctx.fillStyle = colors[index % colors.length];
    ctx.fill();
    
    // Add subtle stroke
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
    ctx.lineWidth = 1;
    ctx.stroke();
    
    currentAngle += sliceAngle;
  });
  
  // Update legend and total
  updateChartLegend(categories, colors);
  document.getElementById('chartTotal').textContent = '৳' + total.toLocaleString();
}

function updateChartLegend(categories, colors = []) {
  const legendContainer = document.getElementById('chartLegend');
  
  if (!categories || categories.length === 0) {
    legendContainer.innerHTML = `
      <div class="legend-item">
        <div class="legend-dot" style="background: rgba(255, 255, 255, 0.2);"></div>
        <span class="legend-text">No expense data</span>
        <span class="legend-amount">৳0</span>
      </div>
    `;
    return;
  }
  
  const defaultColors = [
    '#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe',
    '#43e97b', '#38f9d7', '#ffecd2', '#fcb69f', '#a8edea'
  ];
  
  legendContainer.innerHTML = categories.map((category, index) => `
    <div class="legend-item">
      <div class="legend-dot" style="background: ${colors[index] || defaultColors[index % defaultColors.length]};"></div>
      <span class="legend-text">${category.category || 'Unknown'}</span>
      <span class="legend-amount">৳${parseFloat(category.amount || 0).toLocaleString()}</span>
    </div>
  `).join('');
}

// =========================
// Enhanced Load Expenses with Chart
// =========================
function loadExpensesWithChart() {
  const params = new URLSearchParams({
    category: document.getElementById('filterCategory').value || '',
    status: document.getElementById('filterStatus').value || '',
    from: document.getElementById('filterFrom').value || '',
    to: document.getElementById('filterTo').value || '',
    sort: document.getElementById('sortBy').value || 'date_desc',
  });

  fetch('../backend/fetch_expenses.php?' + params, { credentials: 'same-origin' })
    .then((r) => r.json())
    .then((data) => {
      const list = document.getElementById('expensesList');
      list.innerHTML = '';

      if (!data || !data.success) {
        list.innerHTML =
          '<div class="glass-card" style="padding:12px;">No expenses found.</div>';
        createPieChart([]);
        return;
      }

      let sumMonth = 0,
        sumPending = 0,
        sumPaid = 0;
      
      // Group expenses by category for chart
      const categoryTotals = {};

      (data.expenses || []).forEach((exp) => {
        const card = document.createElement('div');
        card.className = 'card glass-card';
        card.style.padding = '12px';

        const toggleStatus =
          exp.status === 'paid'
            ? 'unpaid'
            : exp.status === 'unpaid'
            ? 'pending'
            : 'paid';

        card.innerHTML = `
          <h3>${exp.title}</h3>
          <div class="job-meta">
            <span class="job-tag">${exp.category}</span>
            <span class="job-tag">${exp.status}</span>
            <span class="job-tag">${exp.date}</span>
          </div>
          <p class="salary">৳${Number(exp.amount).toLocaleString()}</p>
          <div style="display:flex; gap:8px;">
            <button class="apply-btn" onclick="markExpense('${exp.id}', '${toggleStatus}')">
              Mark ${toggleStatus.charAt(0).toUpperCase() + toggleStatus.slice(1)}
            </button>
            <button class="apply-btn" style="background:#c62828;" onclick="deleteExpense('${exp.id}')">
              Delete
            </button>
          </div>
        `;

        list.appendChild(card);

        // Calculate sums
        const d = new Date(exp.date);
        const now = new Date();
        if (d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear())
          sumMonth += Number(exp.amount) || 0;
        if (exp.status === 'pending') sumPending += Number(exp.amount) || 0;
        if (exp.status === 'paid') sumPaid += Number(exp.amount) || 0;
        
        // Group by category for chart
        const category = exp.category || 'Others';
        categoryTotals[category] = (categoryTotals[category] || 0) + (Number(exp.amount) || 0);
      });

      // Update summary cards
      document.getElementById('sumThisMonth').textContent =
        'This Month: ৳' + sumMonth.toLocaleString();
      document.getElementById('sumPending').textContent =
        'Pending: ৳' + sumPending.toLocaleString();
      document.getElementById('sumPaid').textContent =
        'Paid: ৳' + sumPaid.toLocaleString();
      document.getElementById('sumSavings').textContent =
        'Savings: ৳' + Math.max(0, sumMonth - sumPaid).toLocaleString();
      
      // Create chart data
      const chartData = Object.entries(categoryTotals).map(([category, amount]) => ({
        category,
        amount
      }));
      
      createPieChart(chartData);
    })
    .catch((err) => {
      console.error('Error loading expenses:', err);
      document.getElementById('expensesList').innerHTML =
        '<div class="glass-card" style="padding:12px;">Error loading data.</div>';
      createPieChart([]);
    });
}

// Replace the old loadExpenses function
function loadExpenses() {
  loadExpensesWithChart();
}

// Update mark and delete functions to refresh chart
const originalMarkExpense = markExpense;
function markExpense(id, status) {
  const sid = String(id);
  const payload = { id: sid, status: status };
  if (sid[0].toLowerCase() === 's') payload.source = 'shared';

  console.log('Updating status:', payload);

  fetch('../backend/mark_expense.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
    .then(async (res) => {
      let data;
      try {
        data = await res.json();
      } catch (e) {
        console.error('Invalid JSON from server', await res.text());
        alert('Server returned invalid JSON');
        return;
      }
      if (data.success) {
        loadExpensesWithChart(); // Use enhanced function
      } else {
        alert(data.message || 'Failed to update expense status');
      }
    })
    .catch((err) => {
      console.error('Network error while updating status:', err);
      alert('Network error while updating status');
    });
}

const originalDeleteExpense = deleteExpense;
function deleteExpense(id) {
  if (!confirm('Are you sure you want to delete this expense?')) return;

  fetch('../backend/delete_expenses.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        loadExpensesWithChart(); // Use enhanced function
      } else {
        alert(data.message || 'Failed to delete expense');
      }
    })
    .catch((err) => {
      console.error('Network error while deleting expense:', err);
      alert('Network error while deleting expense');
    });
}

// =========================
// Initial Load
// =========================
window.addEventListener('load', loadExpensesWithChart);
