document.getElementById("capabilityForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch("../backend/capability.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            console.log("Response from backend:", data); // 👈 debug log
            document.getElementById("message").innerText = data.message;
            if (data.success && data.redirect_url) {
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 1000);
            }
        })
        .catch(err => console.error("Fetch error:", err));
});
