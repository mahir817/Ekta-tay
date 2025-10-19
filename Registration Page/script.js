document.addEventListener("DOMContentLoaded", function () {
    // === Dark Mode Toggle ===
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        themeToggle.innerHTML = '<i class="fa fa-sun"></i>';
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
                themeToggle.innerHTML = '<i class="fa fa-sun"></i>';
            } else {
                localStorage.setItem('theme', 'light');
                themeToggle.innerHTML = '<i class="fa fa-moon"></i>';
            }
        });
    }

    // === Form Elements ===
    const form = document.getElementById('registrationForm');
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const locationSelect = document.getElementById('location');
    const genderSelect = document.getElementById('gender');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    const submitButton = document.getElementById('submitButton');
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');

    // === Password Toggle ===
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    }

    if (toggleConfirmPassword && confirmPasswordInput) {
        toggleConfirmPassword.addEventListener('click', function () {
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                confirmPasswordInput.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    }

    // === Real-time Validation (Optional) ===
    firstNameInput?.addEventListener('input', () => {
        const err = document.getElementById('firstName-error');
        if (firstNameInput.value.trim().length < 2) {
            if (!err) {
                const e = document.createElement('div');
                e.id = 'firstName-error';
                e.className = 'error-message';
                e.textContent = 'First name must be at least 2 characters.';
                firstNameInput.parentElement.appendChild(e);
            }
        } else err?.remove();
    });

    lastNameInput?.addEventListener('input', () => {
        const err = document.getElementById('lastName-error');
        if (lastNameInput.value.trim().length < 2) {
            if (!err) {
                const e = document.createElement('div');
                e.id = 'lastName-error';
                e.className = 'error-message';
                e.textContent = 'Last name must be at least 2 characters.';
                lastNameInput.parentElement.appendChild(e);
            }
        } else err?.remove();
    });

    emailInput?.addEventListener('input', () => {
        const err = document.getElementById('email-error');
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regex.test(emailInput.value.trim())) {
            if (!err) {
                const e = document.createElement('div');
                e.id = 'email-error';
                e.className = 'error-message';
                e.textContent = 'Invalid email address.';
                emailInput.parentElement.appendChild(e);
            }
        } else err?.remove();
    });

    phoneInput?.addEventListener('input', () => {
        const err = document.getElementById('phone-error');
        if (!/^[0-9]{11,15}$/.test(phoneInput.value.trim())) {
            if (!err) {
                const e = document.createElement('div');
                e.id = 'phone-error';
                e.className = 'error-message';
                e.textContent = 'Phone must be 11-15 digits.';
                phoneInput.parentElement.appendChild(e);
            }
        } else err?.remove();
    });

    passwordInput?.addEventListener('input', () => {
        const err = document.getElementById('password-error');
        if (passwordInput.value.trim().length < 6) {
            if (!err) {
                const e = document.createElement('div');
                e.id = 'password-error';
                e.className = 'error-message';
                e.textContent = 'Password must be at least 6 characters.';
                passwordInput.parentElement.appendChild(e);
            }
        } else err?.remove();
    });

    // === Form Submission ===
    form?.addEventListener('submit', function (e) {
        e.preventDefault();

        const firstName = firstNameInput.value.trim();
        const lastName = lastNameInput.value.trim();
        const email = emailInput.value.trim();
        const phone = phoneInput.value.trim();
        const location = locationSelect.value;
        const gender = genderSelect.value;
        const password = passwordInput.value.trim();
        const confirmPassword = confirmPasswordInput.value.trim();
        const role = document.querySelector('input[name="role"]:checked').value;

        // Validation
        let errors = [];
        if (firstName.length < 2) errors.push("First name too short");
        if (lastName.length < 2) errors.push("Last name too short");
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push("Invalid email");
        if (!/^[0-9]{11,15}$/.test(phone)) errors.push("Phone must be 11-15 digits");
        if (!location) errors.push("Select a location");
        if (!gender) errors.push("Select gender");
        if (password.length < 6) errors.push("Password too short");
        if (password !== confirmPassword) errors.push("Passwords do not match");

        if (errors.length > 0) {
            alert(errors.join("\n"));
            return;
        }

        // Show loading
        buttonText.textContent = 'Registering...';
        loadingSpinner.style.display = 'inline-block';
        submitButton.disabled = true;

        // Prepare data
        const formData = {
            full_name: firstName + " " + lastName,
            email: email,
            phone: phone,
            location: location,
            gender: gender,
            password: password,
            role: role === 'tutor' ? 'recruiter' : 'student'
        };

        // Send to backend
        fetch('../backend/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
            .then(res => res.json())
            .then(data => {
                buttonText.textContent = 'REGISTER';
                loadingSpinner.style.display = 'none';
                submitButton.disabled = false;

                if (data.success) {
                    window.location.href = data.redirect_url || "../Dashboard/dashboard.php";
                } else {
                    alert("Registration failed: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("An error occurred. Please try again.");
                buttonText.textContent = 'REGISTER';
                loadingSpinner.style.display = 'none';
                submitButton.disabled = false;
            });
    });
});
