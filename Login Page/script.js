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

    // Login Form Submission
    document.getElementById('loginForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();

        if (username.length < 3) {
            alert('Username must be at least 3 characters.');
            return;
        }

        if (password.length < 6) {
            alert('Password must be at least 6 characters.');
            return;
        }

        alert('Login successful!');
        window.location.href = "dashboard.html";
    });
});