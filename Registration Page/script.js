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
                themeToggle.innerHTML = '<i class="fa fa-sun"></i>'; // Sun icon for light mode
            } else {
                localStorage.setItem('theme', 'light');
                themeToggle.innerHTML = '<i class="fa fa-moon"></i>'; // Moon icon for dark mode
            }
        });
    }

    // Real-Time Validation
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    const submitButton = document.getElementById('submitButton');
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');

    // Password Toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');

    if (togglePassword && passwordField) {
        togglePassword.addEventListener('click', function () {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                passwordField.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    }

    // Confirm Password Toggle
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordField = document.getElementById('confirm-password');

    if (toggleConfirmPassword && confirmPasswordField) {
        toggleConfirmPassword.addEventListener('click', function () {
            if (confirmPasswordField.type === 'password') {
                confirmPasswordField.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                confirmPasswordField.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    }

    // Real-Time Username Validation
    if (usernameInput) {
        usernameInput.addEventListener('input', function () {
            const username = this.value.trim();
            const usernameError = document.getElementById('username-error');
            if (username.length < 3) {
                if (!usernameError) {
                    const errorMessage = document.createElement('div');
                    errorMessage.id = 'username-error';
                    errorMessage.style.color = 'red';
                    errorMessage.textContent = 'Username must be at least 3 characters.';
                    this.parentElement.appendChild(errorMessage);
                }
            } else {
                if (usernameError) {
                    usernameError.remove();
                }
            }
        });
    }

    // Real-Time Email Validation
    if (emailInput) {
        emailInput.addEventListener('input', function () {
            const email = this.value.trim();
            const emailError = document.getElementById('email-error');
            if (!email.includes('@')) {
                if (!emailError) {
                    const errorMessage = document.createElement('div');
                    errorMessage.id = 'email-error';
                    errorMessage.style.color = 'red';
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

    // Real-Time Password Validation
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            const password = this.value.trim();
            const passwordError = document.getElementById('password-error');
            if (password.length < 6) {
                if (!passwordError) {
                    const errorMessage = document.createElement('div');
                    errorMessage.id = 'password-error';
                    errorMessage.style.color = 'red';
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

            // Get the selected role
            const selectedRole = document.querySelector('input[name="role"]:checked').value;

            // Get form values
            const username = usernameInput.value.trim();
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();
            const confirmPassword = confirmPasswordInput.value.trim();

            // Validation
            if (username.length < 3) {
                alert('Username must be at least 3 characters.');
                return;
            }

            if (!email.includes('@')) {
                alert('Please enter a valid email address.');
                return;
            }

            if (password.length < 6) {
                alert('Password must be at least 6 characters.');
                return;
            }

            if (password !== confirmPassword) {
                alert('Passwords do not match.');
                return;
            }
            buttonText.textContent = 'Registering...';
            loadingSpinner.style.display = 'inline-block';
            submitButton.disabled = true;

            setTimeout(() => {
                buttonText.textContent = 'REGISTER';
                loadingSpinner.style.display = 'none';
                submitButton.disabled = false;

                alert(`Registration successful as a ${selectedRole}!`);

                window.location.href = "dashboard.html";
            }, 2000); 
        });
    }
});