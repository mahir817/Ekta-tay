document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById('loginForm');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    // Password toggle (eye icon)
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

    // Login form submission
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const email = document.getElementById('email').value.trim();
            const password = passwordInput.value.trim();

            // Basic validation
            if (!email || !password) {
                alert("Please enter email and password.");
                return;
            }

            // Disable form during request
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = "Logging in...";

            // Send login request to PHP backend
            fetch('../backend/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, password: password })
            })
                .then(res => res.json())
                .then(data => {
                    submitButton.disabled = false;
                    submitButton.textContent = "LOGIN";

                    if (data.success) {
                        // Redirect to dashboard
                        window.location.href = data.redirect_url || "../Dashboard/dashboard.php";
                    } else {
                        alert("Login failed: " + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("An error occurred. Please try again.");
                    submitButton.disabled = false;
                    submitButton.textContent = "LOGIN";
                });
        });
    }
});
