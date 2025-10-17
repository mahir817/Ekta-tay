
document.addEventListener("DOMContentLoaded", function () {

    const serviceType = document.getElementById("service_type");
    const housingForm = document.getElementById("housingForm");
    const jobForm = document.getElementById("jobForm");
    const tuitionForm = document.getElementById("tuitionForm");
    const foodForm = document.getElementById("foodForm");
    const form = document.querySelector("form");
    const titleInput = form.querySelector("input[name='title']");
    const descriptionInput = form.querySelector("textarea[name='description']");
    const priceInput = form.querySelector("input[name='price']");

    function setRequiredForType(type) {
        // clear all
        form.querySelectorAll('[data-dynamic-required="1"]').forEach(el => {
            el.removeAttribute('required');
        });

        if (type === 'housing') {
            const rentEl = form.querySelector("input[name='rent']");
            if (rentEl) rentEl.setAttribute('required', 'required');
            rentEl && rentEl.setAttribute('data-dynamic-required', '1');
        }

        if (type === 'job') {
            const companyEl = form.querySelector("input[name='company']");
            if (companyEl) companyEl.setAttribute('required', 'required');
            companyEl && companyEl.setAttribute('data-dynamic-required', '1');
        }

        if (type === 'tuition') {
            [
                "select[name='tuition_subject']",
                "select[name='tuition_class_level']",
                "select[name='tuition_type']"
            ].forEach(sel => {
                const el = form.querySelector(sel);
                if (el) {
                    el.setAttribute('required', 'required');
                    el.setAttribute('data-dynamic-required', '1');
                }
            });
        }

        if (type === 'food') {
            const providerEl = form.querySelector("input[name='provider_name']");
            if (providerEl) providerEl.setAttribute('required', 'required');
            providerEl && providerEl.setAttribute('data-dynamic-required', '1');
            if (priceInput) priceInput.setAttribute('required', 'required');
            priceInput && priceInput.setAttribute('data-dynamic-required', '1');
            const locationEl = form.querySelector("input[name='location']");
            if (locationEl) locationEl.setAttribute('required', 'required');
            locationEl && locationEl.setAttribute('data-dynamic-required', '1');
        }
    }

    // toggle form sections
    serviceType.addEventListener("change", function () {
        housingForm.style.display = "none";
        jobForm.style.display = "none";
        tuitionForm.style.display = "none";
        foodForm.style.display = "none";

        if (this.value === "housing") housingForm.style.display = "block";
        if (this.value === "job") jobForm.style.display = "block";
        if (this.value === "tuition") tuitionForm.style.display = "block";
        if (this.value === "food") foodForm.style.display = "block";

        setRequiredForType(this.value);
    });

    // initialize in case of browser back or prefilled
    setRequiredForType(serviceType.value);

    // simple validation before submit
    form.addEventListener("submit", function (e) {
        let type = serviceType.value;

        if (!type) {
            alert("Please select a service type first!");
            e.preventDefault();
            return;
        }

        // basic general validation for all types
        const title = titleInput.value.trim();
        const description = descriptionInput.value.trim();
        if (!title || !description) {
            alert("Title and Description are required.");
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

        // Relaxed: company is optional; backend can accept empty
        if (type === "job") {
            // No required fields beyond general ones on client-side
        }

        if (type === "tuition") {
            const subject = form.querySelector("select[name='tuition_subject']").value;
            const classLevel = form.querySelector("select[name='tuition_class_level']").value;
            const tuitionType = form.querySelector("select[name='tuition_type']").value;
            
            if (!subject || !classLevel || !tuitionType) {
                alert("Subject, Class Level, and Tuition Type are required for tuition posts.");
                e.preventDefault();
            }
        }

        if (type === "food") {
            let provider = form.querySelector("input[name='provider_name']").value.trim();
            const price = priceInput.value.trim();
            if (!provider || !price) {
                alert("Provider Name and Price are required for food services (set price above).");
                e.preventDefault();
            }
        }
    });
});
