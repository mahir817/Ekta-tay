document.addEventListener("DOMContentLoaded", function () {
    // Dark Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;

    // Check for saved theme in localStorage
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        themeToggle.innerHTML = '<i class="fa fa-sun"></i>';
    }

    // Toggle Dark Mode
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

    // Form Elements
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const citySelect = document.getElementById('city');
    const locationSelect = document.getElementById('location');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    const submitButton = document.getElementById('submitButton');
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');

    // Password Toggle
    const togglePassword = document.getElementById('togglePassword');
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

    // Confirm Password Toggle
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
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

    // Real-Time First Name Validation
    if (firstNameInput) {
        firstNameInput.addEventListener('input', function () {
            const firstName = this.value.trim();
            const firstNameError = document.getElementById('firstName-error');
            if (firstName.length < 2) {
                if (!firstNameError) {
                    const errorMessage = document.createElement('div');
                    errorMessage.id = 'firstName-error';
                    errorMessage.className = 'error-message';
                    errorMessage.textContent = 'First name must be at least 2 characters.';
                    this.parentElement.appendChild(errorMessage);
                }
            } else {
                if (firstNameError) {
                    firstNameError.remove();
                }
            }
        });
    }

    // Real-Time Last Name Validation
    if (lastNameInput) {
        lastNameInput.addEventListener('input', function () {
            const lastName = this.value.trim();
            const lastNameError = document.getElementById('lastName-error');
            if (lastName.length < 2) {
                if (!lastNameError) {
                    const errorMessage = document.createElement('div');
                    errorMessage.id = 'lastName-error';
                    errorMessage.className = 'error-message';
                    errorMessage.textContent = 'Last name must be at least 2 characters.';
                    this.parentElement.appendChild(errorMessage);
                }
            } else {
                if (lastNameError) {
                    lastNameError.remove();
                }
            }
        });
    }

    // Real-Time Email Validation
    if (emailInput) {
        emailInput.addEventListener('input', function () {
            const email = this.value.trim();
            const emailError = document.getElementById('email-error');
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                if (!emailError) {
                    const errorMessage = document.createElement('div');
                    errorMessage.id = 'email-error';
                    errorMessage.className = 'error-message';
                    errorMessage.textContent = 'Please enter a valid email address.';
                    this.parentElement.appendChild(errorMessage);
                }
            } else {
                if (emailError) {
                    emailError.remove();
                }
            }
        });
    }

    // Real-Time Phone Validation
    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            const phone = this.value.trim();
            const phoneError = document.getElementById('phone-error');
            if (!/^[0-9]{11,15}$/.test(phone)) {
                if (!phoneError) {
                    const errorMessage = document.createElement('div');
                    errorMessage.id = 'phone-error';
                    errorMessage.className = 'error-message';
                    errorMessage.textContent = 'Phone must be 11-15 digits.';
                    this.parentElement.appendChild(errorMessage);
                }
            } else {
                if (phoneError) {
                    phoneError.remove();
                }
            }
        });
    }

    // Real-Time Password Validation
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            const password = this.value.trim();
            const passwordError = document.getElementById('password-error');
            if (password.length < 6) {
                if (!passwordError) {
                    const errorMessage = document.createElement('div');
                    errorMessage.id = 'password-error';
                    errorMessage.className = 'error-message';
                    errorMessage.textContent = 'Password must be at least 6 characters.';
                    this.parentElement.appendChild(errorMessage);
                }
            } else {
                if (passwordError) {
                    passwordError.remove();
                }
            }
        });
    }

    // Registration Form Submission
    if (document.getElementById('registrationForm')) {
        document.getElementById('registrationForm').addEventListener('submit', function (event) {
            event.preventDefault();

            // Get form values
            const firstName = firstNameInput.value.trim();
            const lastName = lastNameInput.value.trim();
            const email = emailInput.value.trim();
            const phone = phoneInput.value.trim();
            const city = citySelect.value;
            const location = locationSelect.value;
            const gender = document.querySelector('input[name="gender"]:checked').value;
            const password = passwordInput.value.trim();
            const confirmPassword = confirmPasswordInput.value.trim();
            const role = document.querySelector('input[name="role"]:checked').value;

            // Validation
            let isValid = true;
            const errors = [];

            if (firstName.length < 2) {
                errors.push('First name must be at least 2 characters.');
                isValid = false;
            }

            if (lastName.length < 2) {
                errors.push('Last name must be at least 2 characters.');
                isValid = false;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.push('Please enter a valid email address.');
                isValid = false;
            }

            if (!/^[0-9]{11,15}$/.test(phone)) {
                errors.push('Phone must be 11-15 digits.');
                isValid = false;
            }

            if (city === "") {
                errors.push('Please select your city.');
                isValid = false;
            }

            if (location === "") {
                errors.push('Please select your location.');
                isValid = false;
            }

            if (password.length < 6) {
                errors.push('Password must be at least 6 characters.');
                isValid = false;
            }

            if (password !== confirmPassword) {
                errors.push('Passwords do not match.');
                isValid = false;
            }

            if (!isValid) {
                alert(errors.join('\n'));
                return;
            }

            // Show loading state
            buttonText.textContent = 'Registering...';
            loadingSpinner.style.display = 'inline-block';
            submitButton.disabled = true;

            // Simulate form submission
            setTimeout(() => {
                // Reset button state
                buttonText.textContent = 'REGISTER';
                loadingSpinner.style.display = 'none';
                submitButton.disabled = false;

                // Show success message
                alert(`Registration successful!\nYou've registered as a ${role} (${gender}).`);

                // Redirect to dashboard
                window.location.href = "dashboard.html";
            }, 2000);
        });
    }
});