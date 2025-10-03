<?php
include("../../backend/session.php"); // check user session
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Housing | Ekta-tay</title>
  <link rel="stylesheet" href="housing.css">
  <script src="housing.js" defer></script>
</head>
<body>

<div class="housing-container">

  <!-- Top Navigation Tabs -->
  <div class="tabs glass-card">
    <button class="tab-btn active" onclick="showSection('find')">Find House</button>
    <button class="tab-btn" onclick="showSection('my')">My House</button>
  </div>

  <!-- Sections -->
  <div id="find" class="tab-section active glass-card">
    <div class="section-header">
      <h2>Find House</h2>
      <div class="mini-actions">
        <button class="add-btn" onclick="fetchHousing()">Refresh</button>
      </div>
    </div>

    <!-- Overview Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Pending</span>
        </div>
        <p class="stat-value" id="statPending">0</p>
        <p class="stat-sub">Applications pending</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Applied Requests</span>
        </div>
        <p class="stat-value" id="statApplied">0</p>
        <p class="stat-sub">Total submitted</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Confirmed</span>
        </div>
        <p class="stat-value" id="statConfirmed">0</p>
        <p class="stat-sub">Approved requests</p>
      </div>
      <div class="stat-card">
        <div class="stat-header">
          <span class="stat-title">Cancelled</span>
        </div>
        <p class="stat-value" id="statCancelled">0</p>
        <p class="stat-sub">Closed requests</p>
      </div>
      <div class="stat-card wide">
        <div class="stat-header">
          <span class="stat-title">Nearby Houses</span>
        </div>
        <p class="stat-value" id="statNearby">24</p>
        <p class="stat-sub">Houses available near you</p>
      </div>
    </div>

    <div class="filters">
      <input type="text" id="searchLocation" placeholder="Search by location...">
      <select id="rentRange">
        <option value="">Rent Range</option>
        <option value="0-10000">0-10k</option>
        <option value="10000-30000">10k-30k</option>
        <option value="30000-50000">30k-50k</option>
      </select>
      <button onclick="fetchHousing()" class="add-btn">Search</button>
    </div>
    <div id="housingList" class="card-grid">
      <!-- Housing posts loaded via AJAX -->
    </div>
  </div>

  <div id="my" class="tab-section hidden glass-card">
    <div class="section-header">
      <h2>My House</h2>
      <div class="mini-actions">
        <button class="add-btn" onclick="openPostForm()">+ Post Housing</button>
      </div>
    </div>

    <div class="info-grid">
      <!-- My House Info -->
      <div class="card">
        <h3>House Details</h3>
        <div id="myHouseInfo">
          <p>No house linked yet.</p>
        </div>
      </div>

      <!-- Split Rent -->
      <div class="card">
        <h3>Split Rent</h3>
        <div class="split-form">
          <input type="number" id="totalRent" placeholder="Total monthly rent (BDT)">
          <input type="number" id="numRoommates" placeholder="Number of roommates">
          <button class="add-btn" onclick="calculateSplit()">Calculate</button>
        </div>
        <div id="splitResult" class="split-result"></div>
      </div>

      <!-- Expenses Analysis -->
      <div class="card">
        <h3>Expenses Analysis</h3>
        <div class="expense-chart">
          <div class="chart-circle" id="expenseDonut">
            <div class="chart-center">
              <p class="chart-total" id="expenseTotal">৳0</p>
              <span class="chart-label">This month</span>
            </div>
          </div>
          <div class="expense-legend" id="expenseLegend"></div>
        </div>
      </div>
    </div>

    <div class="subsection">
      <h3 style="margin-bottom:10px;">My Housing Posts</h3>
      <div id="myHousingList" class="card-grid">
        <?php
        if(isset($userHousing) && count($userHousing) > 0){
          foreach($userHousing as $post){
            echo "<div class='card glass-card'>
                    <h3>".htmlspecialchars($post['title'])."</h3>
                    <p>Location: ".htmlspecialchars($post['location'])."</p>
                    <p>Rent: ৳".htmlspecialchars($post['rent'])."</p>
                    <p>".htmlspecialchars($post['description'])."</p>
                  </div>";
          }
        } else {
          echo "<div class='glass-card no-content'>No posts yet.</div>";
        }
        ?>
      </div>
    </div>
  </div>

  <!-- Hidden modal trigger in My House for expenses -->
  <div style="display:none">
    <button class="add-btn" onclick="openExpenseForm()" id="hiddenExpenseBtn">+ Add Expense</button>
  </div>

</div>

<!-- Modal for Posting Housing -->
<div id="postModal" class="modal hidden">
  <div class="modal-content glass-card">
    <h3>Post New Housing</h3>
    <form id="postHousingForm">
      <input type="text" name="title" placeholder="Title" required>
      <input type="text" name="location" placeholder="Location" required>
      <input type="number" name="rent" placeholder="Rent" required>
      <input type="text" name="khotiyan" placeholder="Khotiyan/Porcha No (optional)">
      <textarea name="description" placeholder="Description"></textarea>
      <button type="submit" class="add-btn">Submit</button>
      <button type="button" onclick="closePostForm()" class="add-btn cancel-btn">Cancel</button>
    </form>
  </div>
</div>

<!-- Modal for Adding Expense -->
<div id="expenseModal" class="modal hidden">
  <div class="modal-content glass-card">
    <h3>Add Expense</h3>
    <form id="expenseForm">
      <input type="text" name="name" placeholder="Expense Name" required>
      <input type="number" name="amount" placeholder="Amount" required>
      <input type="date" name="due_date" required>
      <button type="submit" class="add-btn">Save</button>
      <button type="button" onclick="closeExpenseForm()" class="add-btn cancel-btn">Cancel</button>
    </form>
  </div>
</div>

</body>
</html>
