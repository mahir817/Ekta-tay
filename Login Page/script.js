document.addEventListener("DOMContentLoaded", function () {
    // Login form validation
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
