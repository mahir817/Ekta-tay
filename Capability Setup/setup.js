// Submit form logic
document.getElementById("capabilityForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("../backend/capability.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            console.log("Response from backend:", data);
            document.getElementById("message").innerText = data.message;
            if (data.success && data.redirect_url) {
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 1000);
            }
        })
        .catch(err => console.error("Fetch error:", err));
});

// UI Toggle Effect
document.querySelectorAll(".capability-card").forEach(card => {
    card.addEventListener("click", () => {
        let checkbox = card.querySelector("input");
        checkbox.checked = !checkbox.checked;
        card.classList.toggle("active", checkbox.checked);
    });
});
