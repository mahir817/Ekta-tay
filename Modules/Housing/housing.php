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
    <button class="tab-btn active" onclick="showSection('find')">Find Housing</button>
    <button class="tab-btn" onclick="showSection('my')">My Housing</button>
    <button class="tab-btn" onclick="showSection('expenses')">Expenses</button>
    <button class="tab-btn" onclick="showSection('rent')">Rent Splitting</button>
    <button class="tab-btn" onclick="showSection('apps')">Applications</button>
  </div>

  <!-- Sections -->
  <div id="find" class="tab-section active glass-card">
    <h2>Find Housing</h2>
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
    <h2>My Housing Posts</h2>
    <button class="add-btn" onclick="openPostForm()">+ Post New Housing</button>
    <div id="myHousingList" class="card-grid">
      <?php
      // Example placeholder for user housing posts
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

  <div id="expenses" class="tab-section hidden glass-card">
    <h2>Expense Management</h2>
    <button class="add-btn" onclick="openExpenseForm()">+ Add Expense</button>
    <div id="expensesTable"></div>
    <canvas id="expenseChart" style="margin-top:20px;"></canvas>
  </div>

  <div id="rent" class="tab-section hidden glass-card">
    <h2>Rent Splitting</h2>
    <div id="rentSplitTable"></div>
  </div>

  <div id="apps" class="tab-section hidden glass-card">
    <h2>Applications</h2>
    <div id="applicationsList" class="card-grid"></div>
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
