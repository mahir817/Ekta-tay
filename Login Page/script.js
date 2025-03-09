document.getElementById('loginForm').addEventListener('submit', function (event) {
    event.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    // Basic validation
    if (email && password) {
        alert('Login successful!');
        // You can add further logic here, like redirecting to another page
    } else {
        alert('Please fill in all fields.');
    }
});