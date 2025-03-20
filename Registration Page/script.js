document.addEventListener("DOMContentLoaded", function () {
    // Registration form validation
    document.getElementById('registrationForm').addEventListener('submit', function (event) {
        event.preventDefault();

        // Get the selected role
        const selectedRole = document.querySelector('input[name="role"]:checked').value;

        // Get form values
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const confirmPassword = document.getElementById('confirm-password').value.trim();

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

        // Display success message with the selected role
        alert(`Registration successful as a ${selectedRole}!`);
        window.location.href = "dashboard.html"; // Redirect after registration
    });
});