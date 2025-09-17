document.getElementById("capabilityForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch("../backend/capability.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            document.getElementById("message").innerText = data.message;
            if (data.success) {
                setTimeout(() => {
                    window.location.href = "../Dashboard/dashboard.php";
                }, 1000);
            }
        })
        .catch(err => console.error(err));
});
