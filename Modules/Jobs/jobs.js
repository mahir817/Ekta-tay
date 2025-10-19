// Jobs Module JavaScript

// Tab switching functionality
function showSection(id) {
  // Hide all tab sections
  document.querySelectorAll(".tab-section").forEach((sec) => {
    sec.classList.add("hidden");
    sec.classList.remove("active");
  });

  // Show selected section
  const activeSection = document.getElementById(id);
  activeSection.classList.remove("hidden");
  activeSection.classList.add("active");

  // Remove active class from all buttons
  document
    .querySelectorAll(".tab-btn")
    .forEach((btn) => btn.classList.remove("active"));

  // Add active class to clicked button
  const buttons = document.querySelectorAll(".tab-btn");
  buttons.forEach((btn) => {
    const onclick = btn.getAttribute("onclick") || "";
    if (onclick.includes(id)) {
      btn.classList.add("active");
    }
  });

  // Load data for the active section
  if (id === "find") {
    fetchJobs();
  } else if (id === "tuition") {
    fetchTuition();
  } else if (id === "my") {
    refreshMyPosts();
  }
}

// ====== Fetch Jobs ======
function fetchJobs() {
  showLoading("jobsList");

  // Get filter values
  const keyword = document.getElementById("searchKeyword")?.value || "";
  const jobType = document.getElementById("jobType")?.value || "";
  const salaryRange = document.getElementById("salaryRange")?.value || "";
  const location = document.getElementById("searchLocation")?.value || "";

  const params = new URLSearchParams({
    keyword,
    job_type: jobType,
    salary_range: salaryRange,
    location,
  });

  console.log(
    "Fetching jobs with URL:",
    `../../backend/fetch_jobs.php?${params}`
  );

  fetch(`../../backend/fetch_jobs.php?${params}`)
    .then((res) => {
      console.log("Response status:", res.status);
      console.log("Response headers:", res.headers);

      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }

      return res.text(); // Get as text first to see raw response
    })
    .then((text) => {
      console.log("Raw response:", text);

      try {
        const data = JSON.parse(text);
        console.log("Parsed data:", data);

        let list = document.getElementById("jobsList");
        list.innerHTML = "";

        // Check if response has an error
        if (data.error) {
          list.innerHTML = `<div class="no-content glass-card"><p>Error: ${
            data.message || data.error
          }</p></div>`;
          return;
        }

        if (!Array.isArray(data) || data.length === 0) {
          list.innerHTML =
            '<div class="no-content glass-card"><i class="fas fa-briefcase"></i><p>No jobs found matching your criteria</p></div>';
          updateJobStats({
            applied: 0,
            review: 0,
            interviews: 0,
            offers: 0,
            available: 0,
          });
          return;
        }

        data.forEach((job) => {
          const jobCard = createJobCard(job);
          list.appendChild(jobCard);
        });

        // Update stats based on fetched data
        const stats = calculateJobStats(data);
        updateJobStats(stats);
      } catch (parseError) {
        console.error("JSON parse error:", parseError);
        document.getElementById("jobsList").innerHTML =
          '<div class="no-content glass-card"><p>Error parsing server response. Please check console for details.</p></div>';
      }
    })
    .catch((error) => {
      console.error("Fetch error:", error);
      document.getElementById("jobsList").innerHTML =
        '<div class="no-content glass-card"><p>Error loading jobs: ' +
        error.message +
        ". Please try again.</p></div>";
    });
}

// ====== Fetch Tuition ======
function fetchTuition() {
  showLoading("tuitionList");

  // Get filter values
  const subject = document.getElementById("subjectFilter")?.value || "";
  const classLevel = document.getElementById("classLevel")?.value || "";
  const tuitionType = document.getElementById("tuitionType")?.value || "";
  const location = document.getElementById("tuitionLocation")?.value || "";

  const params = new URLSearchParams({
    type: "tuition",
    subject,
    class_level: classLevel,
    tuition_type: tuitionType,
    location,
  });

  console.log(
    "Fetching tuition with URL:",
    `../../backend/fetch_jobs.php?${params}`
  );

  fetch(`../../backend/fetch_jobs.php?${params}`)
    .then((res) => {
      console.log("Tuition response status:", res.status);

      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }

      return res.text(); // Get as text first to see raw response
    })
    .then((text) => {
      console.log("Raw tuition response:", text);

      try {
        const data = JSON.parse(text);
        console.log("Parsed tuition data:", data);

        let list = document.getElementById("tuitionList");
        list.innerHTML = "";

        // Check if response has an error
        if (data.error) {
          list.innerHTML = `<div class="no-content glass-card"><p>Error: ${
            data.message || data.error
          }</p></div>`;
          return;
        }

        if (!Array.isArray(data) || data.length === 0) {
          list.innerHTML =
            '<div class="no-content glass-card"><i class="fas fa-graduation-cap"></i><p>No tuition opportunities found</p></div>';
          updateTuitionStats({
            students: 0,
            earnings: 0,
            subjects: 0,
            requests: 0,
          });
          return;
        }

        data.forEach((tuition) => {
          const tuitionCard = createTuitionCard(tuition);
          list.appendChild(tuitionCard);
        });

        // Update stats
        const stats = calculateTuitionStats(data);
        updateTuitionStats(stats);
      } catch (parseError) {
        console.error("Tuition JSON parse error:", parseError);
        document.getElementById("tuitionList").innerHTML =
          '<div class="no-content glass-card"><p>Error parsing server response. Please check console for details.</p></div>';
      }
    })
    .catch((error) => {
      console.error("Error fetching tuition:", error);
      document.getElementById("tuitionList").innerHTML =
        '<div class="no-content glass-card"><p>Error loading tuition opportunities: ' +
        error.message +
        ". Please try again.</p></div>";
    });
}

// ====== Create Job Card ======
function createJobCard(job) {
  const card = document.createElement("div");
  card.className = "card";

  const jobTypeClass = job.type === "job" ? "job" : "tuition";
  const jobTypeDisplay = job.job_type || job.type;

  card.innerHTML = `
        <div class="job-type-indicator ${jobTypeClass}">${jobTypeDisplay}</div>
        <h3>${job.title}</h3>
        <div class="job-meta">
            ${job.company ? `<span class="job-tag">${job.company}</span>` : ""}
            <span class="job-tag">${job.location}</span>
            ${
              job.job_type ? `<span class="job-tag">${job.job_type}</span>` : ""
            }
        </div>
        <p class="salary">৳${formatSalary(job.price)}</p>
        <p>${truncateText(job.description, 100)}</p>
        <p style="font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 8px;">
            Posted by ${job.poster_name || "Anonymous"} • ${timeAgo(
    job.created_at
  )}
        </p>
        <button class="apply-btn" onclick="openApplicationModal(${
          job.service_id
        })">Apply Now</button>
    `;

  return card;
}

// ====== Create Tuition Card ======
function createTuitionCard(tuition) {
  const card = document.createElement("div");
  card.className = "card";

  card.innerHTML = `
        <div class="job-type-indicator tuition">Tuition</div>
        <h3>${tuition.title}</h3>
        <div class="job-meta">
            ${
              tuition.subject
                ? `<span class="job-tag">${tuition.subject}</span>`
                : ""
            }
            ${
              tuition.class_level
                ? `<span class="job-tag">${tuition.class_level}</span>`
                : ""
            }
            <span class="job-tag">${tuition.location}</span>
            ${
              tuition.tuition_type
                ? `<span class="job-tag">${tuition.tuition_type}</span>`
                : ""
            }
        </div>
        <p class="salary">৳${formatSalary(tuition.price)}/month</p>
        <p>${truncateText(tuition.description, 100)}</p>
        <p style="font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 8px;">
            Posted by ${tuition.poster_name || "Anonymous"} • ${timeAgo(
    tuition.created_at
  )}
        </p>
        <button class="apply-btn" onclick="openApplicationModal(${
          tuition.service_id
        })">Apply Now</button>
    `;

  return card;
}

// ====== Refresh Functions ======
function refreshJobs() {
  fetchJobs();
}

function refreshTuition() {
  fetchTuition();
}

function refreshMyPosts() {
  showLoading("myPostsList");

  fetch("../../backend/fetch_my_jobs.php")
    .then((res) => res.json())
    .then((data) => {
      let list = document.getElementById("myPostsList");
      list.innerHTML = "";

      if (data.length === 0) {
        list.innerHTML =
          '<div class="no-content glass-card"><i class="fas fa-plus"></i><p>You haven\'t posted any jobs yet</p><button class="add-btn" onclick="openPostForm()" style="margin-top: 10px;">Post Your First Job</button></div>';
        updateMyPostsStats({
          active: 0,
          applications: 0,
          views: 0,
          responseRate: 0,
        });
        return;
      }

      data.forEach((post) => {
        const postCard = createMyPostCard(post);
        list.appendChild(postCard);
      });

      // Update stats
      const stats = calculateMyPostsStats(data);
      updateMyPostsStats(stats);
    })
    .catch((error) => {
      console.error("Error fetching my posts:", error);
      document.getElementById("myPostsList").innerHTML =
        '<div class="no-content glass-card"><p>Error loading your posts. Please try again.</p></div>';
    });
}

// ====== Create My Post Card ======
function createMyPostCard(post) {
  const card = document.createElement("div");
  card.className = "card";

  const status = post.status || "active";
  const statusColor = status === "active" ? "#6aba9d" : "#f5576c";

  card.innerHTML = `
        <div class="job-type-indicator ${post.type}">${post.type}</div>
        <h3>${post.title}</h3>
        <div class="job-meta">
            <span class="job-tag">${post.location}</span>
            <span class="job-tag" style="background: ${statusColor}">${status}</span>
        </div>
        <p class="salary">৳${formatSalary(post.price)}</p>
        <p>${truncateText(post.description, 80)}</p>
        <p style="font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 8px;">
            Posted ${timeAgo(post.created_at)} • ${
    post.applications || 0
  } applications
        </p>
        <div style="position: absolute; bottom: 15px; right: 15px; display: flex; gap: 8px;">
            <button class="apply-btn" onclick="editPost(${
              post.service_id
            })" style="background: #764ba2;">Edit</button>
            <button class="apply-btn" onclick="deletePost(${
              post.service_id
            })" style="background: #f5576c;">Delete</button>
        </div>
    `;

  return card;
}

// ====== Modal Functions ======
function openPostForm() {
  window.location.href = "../../Post Service Page/post_service.php";
}

function closePostForm() {
  document.getElementById("postModal")?.classList.add("hidden");
}

function openTuitionPostForm() {
  window.location.href = "../../Post Service Page/post_service.php";
}

function closeTuitionPostForm() {
  document.getElementById("tuitionPostModal")?.classList.add("hidden");
}

function openApplicationModal(serviceId) {
  document.getElementById("applyServiceId").value = serviceId;
  document.getElementById("applicationModal")?.classList.remove("hidden");
}

function closeApplicationModal() {
  document.getElementById("applicationModal")?.classList.add("hidden");
}

// ====== Form Submissions ======
document.getElementById("postJobForm")?.addEventListener("submit", (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);

  fetch("../../backend/post_service.php", {
    method: "POST",
    body: formData,
  })
    .then((r) => r.json())
    .then((response) => {
      if (response.success) {
        alert("Job posted successfully!");
        closePostForm();
        refreshJobs();
        refreshMyPosts();
      } else {
        alert("Error: " + (response.message || "Failed to post job"));
      }
    })
    .catch((error) => {
      console.error("Error posting job:", error);
      alert("Error posting job. Please try again.");
    });
});

document.getElementById("postTuitionForm")?.addEventListener("submit", (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);

  fetch("../../backend/post_service.php", {
    method: "POST",
    body: formData,
  })
    .then((r) => r.json())
    .then((response) => {
      if (response.success) {
        alert("Tuition posted successfully!");
        closeTuitionPostForm();
        refreshTuition();
        refreshMyPosts();
      } else {
        alert("Error: " + (response.message || "Failed to post tuition"));
      }
    })
    .catch((error) => {
      console.error("Error posting tuition:", error);
      alert("Error posting tuition. Please try again.");
    });
});

document.getElementById("applicationForm")?.addEventListener("submit", (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);

  fetch("../../backend/apply_job.php", {
    method: "POST",
    body: formData,
  })
    .then((r) => r.json())
    .then((response) => {
      if (response.success) {
        alert("Application submitted successfully!");
        closeApplicationModal();
      } else {
        alert("Error: " + (response.message || "Failed to submit application"));
      }
    })
    .catch((error) => {
      console.error("Error submitting application:", error);
      alert("Error submitting application. Please try again.");
    });
});

// ====== Conditional Fields Logic ======
function toggleConditionalFields() {
  const typeSelect = document.querySelector('#postJobForm select[name="type"]');
  const jobFields = document.getElementById("jobFields");
  const tuitionFields = document.getElementById("tuitionFields");

  if (!typeSelect || !jobFields || !tuitionFields) return;

  typeSelect.addEventListener("change", function () {
    if (this.value === "job") {
      jobFields.classList.remove("hidden");
      tuitionFields.classList.add("hidden");
    } else if (this.value === "tuition") {
      jobFields.classList.add("hidden");
      tuitionFields.classList.remove("hidden");
    } else {
      jobFields.classList.add("hidden");
      tuitionFields.classList.add("hidden");
    }
  });
}

// ====== Utility Functions ======
function showLoading(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.innerHTML = '<div class="loading">Loading</div>';
  }
}

function formatSalary(amount) {
  return Number(amount).toLocaleString();
}

function truncateText(text, maxLength) {
  if (!text) return "";
  return text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
}

function timeAgo(dateString) {
  if (!dateString) return "recently";

  const now = new Date();
  const postDate = new Date(dateString);
  const diffInHours = Math.floor((now - postDate) / (1000 * 60 * 60));

  if (diffInHours < 1) return "just now";
  if (diffInHours < 24) return `${diffInHours}h ago`;

  const diffInDays = Math.floor(diffInHours / 24);
  if (diffInDays < 7) return `${diffInDays}d ago`;

  const diffInWeeks = Math.floor(diffInDays / 7);
  return `${diffInWeeks}w ago`;
}

// ====== Stats Calculation Functions ======
function calculateJobStats(jobs) {
  // This would typically come from backend, but for demo purposes:
  return {
    applied: Math.floor(jobs.length * 0.1), // 10% of available jobs
    review: Math.floor(jobs.length * 0.05), // 5% under review
    interviews: Math.floor(jobs.length * 0.02), // 2% interviews
    offers: Math.floor(jobs.length * 0.01), // 1% offers
    available: jobs.length,
  };
}

function calculateTuitionStats(tuitions) {
  return {
    students: Math.floor(tuitions.length * 0.08), // Demo calculation
    earnings: Math.floor(
      tuitions.reduce((sum, t) => sum + (Number(t.price) || 0), 0) * 0.1
    ),
    subjects: new Set(tuitions.map((t) => t.subject).filter(Boolean)).size,
    requests: tuitions.length,
  };
}

function calculateMyPostsStats(posts) {
  return {
    active: posts.filter((p) => p.status !== "closed").length,
    applications: posts.reduce(
      (sum, p) => sum + (Number(p.applications) || 0),
      0
    ),
    views: posts.reduce(
      (sum, p) => sum + (Number(p.views) || Math.floor(Math.random() * 100)),
      0
    ),
    responseRate:
      posts.length > 0
        ? Math.floor(
            (posts.filter((p) => p.applications > 0).length / posts.length) *
              100
          )
        : 0,
  };
}

// ====== Stats Update Functions ======
function updateJobStats(stats) {
  const setStatValue = (id, value) => {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
  };

  setStatValue("statApplied", stats.applied);
  setStatValue("statReview", stats.review);
  setStatValue("statInterviews", stats.interviews);
  setStatValue("statOffers", stats.offers);
  setStatValue("statAvailable", stats.available);
}

function updateTuitionStats(stats) {
  const setStatValue = (id, value) => {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
  };

  setStatValue("statStudents", stats.students);
  setStatValue("statEarnings", "৳" + formatSalary(stats.earnings));
  setStatValue("statSubjects", stats.subjects);
  setStatValue("statTuitionRequests", stats.requests);
}

function updateMyPostsStats(stats) {
  const setStatValue = (id, value) => {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
  };

  setStatValue("statActivePosts", stats.active);
  setStatValue("statApplicationsReceived", stats.applications);
  setStatValue("statViews", stats.views);
  setStatValue("statResponseRate", stats.responseRate + "%");
}

// ====== Post Management Functions ======
function editPost(serviceId) {
  // For now, just show an alert. In a full implementation, this would open an edit modal
  alert(`Edit functionality for post ${serviceId} would be implemented here.`);
}

function deletePost(serviceId) {
  if (confirm("Are you sure you want to delete this post?")) {
    fetch("../../backend/delete_service.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ service_id: serviceId }),
    })
      .then((r) => r.json())
      .then((response) => {
        if (response.success) {
          alert("Post deleted successfully!");
          refreshMyPosts();
        } else {
          alert(
            "Error deleting post: " + (response.message || "Unknown error")
          );
        }
      })
      .catch((error) => {
        console.error("Error deleting post:", error);
        alert("Error deleting post. Please try again.");
      });
  }
}

// ====== Initialize ======
window.onload = () => {
  // Check URL for tab parameter
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get("tab");

  if (tab === "tuition") {
    showSection("tuition");
  } else {
    // Load initial data for the default tab (find jobs)
    fetchJobs();
  }

  // Set up conditional fields toggle
  toggleConditionalFields();
};
