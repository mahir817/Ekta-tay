function showSection(id) {
    document.querySelectorAll('.tab-section').forEach(sec => sec.classList.add("hidden"));
    document.getElementById(id).classList.remove("hidden");
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove("active"));
    document.querySelector(`[onclick="showSection('${id}')"]`).classList.add("active");
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
            let table = "<table><tr><th>Name</th><th>Amount</th><th>Status</th></tr>";
            data.forEach(ex => {
                table += `<tr>
          <td>${ex.name}</td>
          <td>${ex.amount}</td>
          <td>${ex.status}</td>
        </tr>`;
            });
            table += "</table>";
            document.getElementById("expensesTable").innerHTML = table;
        });
}

window.onload = () => {
    fetchHousing();
    loadExpenses();
};
