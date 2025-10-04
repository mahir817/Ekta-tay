
document.addEventListener("DOMContentLoaded", function () {

    const serviceType = document.getElementById("service_type");
    const housingForm = document.getElementById("housingForm");
    const jobForm = document.getElementById("jobForm");
    const foodForm = document.getElementById("foodForm");
    const form = document.querySelector("form");

    // toggle form sections
    serviceType.addEventListener("change", function () {
        housingForm.style.display = "none";
        jobForm.style.display = "none";
        foodForm.style.display = "none";

        if (this.value === "housing") housingForm.style.display = "block";
        if (this.value === "job") jobForm.style.display = "block";
        if (this.value === "food") foodForm.style.display = "block";
    });

    // simple validation before submit
    form.addEventListener("submit", function (e) {
        let type = serviceType.value;

        if (!type) {
            alert("Please select a service type first!");
            e.preventDefault();
            return;
        }

        if (type === "housing") {
            let rent = form.querySelector("input[name='rent']").value.trim();
            if (!rent) {
                alert("Rent is required for housing posts.");
                e.preventDefault();
            }
        }

        if (type === "job") {
            let title = form.querySelector("input[name='title']").value.trim();
            let description = form.querySelector("textarea[name='description']").value.trim();
            if (!title || !description) {
                alert("Job Title and Description are required.");
                e.preventDefault();
            }
        }

        if (type === "food") {
            let provider = form.querySelector("input[name='provider_name']").value.trim();
            let price = form.querySelector("input[name='price']").value.trim();
            if (!provider || !price) {
                alert("Provider Name and Price are required for food services.");
                e.preventDefault();
            }
        }
    });
});
