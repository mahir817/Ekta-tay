## 🏠 Ekta-Tay

### *A Unified Digital Ecosystem for Students & Bachelors in Bangladesh*

**Ekta-Tay** is an all-in-one web platform that connects students and bachelors with essential daily services — from finding housing and tuition jobs to managing expenses, jobs etc

The goal is simple: **bring every essential need into one digital space** — “একটাতে” (Ekta-tay).

---

## 🚀 Features

### 🧩 Core Modules

* 🏡 **Housing & Roommate Finder** – Post or search for rooms, flats, and roommates.
* 📚 **Tuition Finder** – Students can find tuitions or post jobs as teachers.
* 💼 **Job Board** – Explore or post local part-time jobs.
* 💰 **Expense & Payment Tracker** – Track daily expenses, mark payments, and manage due lists.
* 💬 **Community & Networking** – Build connections with peers, landlords, recruiters, and mentors.

---

## 🎯 Project Goal

To create a **digital ecosystem** where students and bachelors can **live smarter and simpler** — eliminating the need to juggle multiple apps for jobs, housing, or food.

---

## 👤 User Roles

| Role             | Description                                                                       |
| ---------------- | --------------------------------------------------------------------------------- |
| **Seekers**      | Students/bachelors who search for services like housing, tuition, or jobs.        |
| **Providers**    | Individuals or businesses offering housing, jobs, food, etc.                      |
| **Hybrid Users** | Students who can both post and apply (e.g., post a tuition job or apply for one). |
| **Admins**       | Manage users, disputes, analytics, and system-wide configurations.                |

---

## 🧠 Core Concept

### 🔹 Capabilities-Based System

Instead of fixed roles, users gain *capabilities* (e.g., `find_tuition`, `offer_food`, `find_job`, `post_housing`).

* On first login, users select what they want to do.
* The dashboard and features dynamically adapt to those capabilities.
* Capabilities are stored in a `user_capabilities` table and can be updated anytime.

### 🔹 Unified Job/Service Posting

All types of listings (housing, tuition, food, job) go into a **single “service posts” table** with tags.

* Simplifies management and scalability.
* Dashboards automatically filter posts by type and user interest.

---

## ⚙️ System Workflow

1. **Registration** → User creates an account with basic info.
2. **Capability Setup** → Choose interests (e.g., Find Job, Post Housing).
3. **Dynamic Dashboard** → System shows relevant menus automatically.
4. **Post & Browse Services** → Unified posting system for all modules.

---


## 🧩 Tech Stack

| Layer                  | Technologies                             |
| ---------------------- | ---------------------------------------- |
| **Frontend**           | HTML, CSS, JavaScript (Vanilla)          |
| **Backend**            | PHP (flat-file structured)               |
| **Database**           | MySQL                                    |
| **Styling**            | Custom CSS (Tailwind-inspired utilities) |
| **Version Control**    | Git + GitHub                             |
| **Server Environment** | XAMPP / Localhost (Apache + MySQL)       |

---

## 💻 Setup Guide

### 1️⃣ Clone the Repository

```bash
git clone https://github.com/mahir817/Ekta-tay.git
cd Ekta-tay
```

### 2️⃣ Setup Database

* Import `sql/ekta_tay (4).sql` into your MySQL server.
* Update `backend/db.php` with your local DB credentials.

### 3️⃣ Run Locally

* Place the project folder in your XAMPP `htdocs` directory.
* Start Apache & MySQL from XAMPP.(Port:8080)
* Visit `http://localhost:8080/Ekta-tay/` in your browser.

---


## 👤 User Profile Module

* Manage user info, profile picture, preferences.
* Access “My Posts”, “My Applications”, and “Shortlists”.
* Settings.

---

## 📈 Future Enhancements

* Order meals, laundry, or other local services.
* Find and connect with mentors for international study guidance.
* Use ML-based recommendation system for matching tutors/jobs.
* Introduce secure payment gateway integration.
* Enable push notifications (WebSocket).
* Expand community forum + real-time messaging.

---

## 📧 Contact

**Developer:** [Mahir Ahmed](https://github.com/mahir817)
**Email:** [mahir101748@gmail.com](mailto:mahir101748@gmail.com) 
**Location:** Bangladesh

---

