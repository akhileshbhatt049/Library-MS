
//   Contact.js — Contact form submission with 7-second toast notifications

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("contactForm");
    if (!form) return;

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const btn = document.getElementById("contact-submit-btn");
        const formData = new FormData(this);

        btn.disabled = true;
        btn.textContent = "Sending…";

        try {
            const res = await postForm(
                "/PHP_programs/Library-MS/PHP/Contact.php",
                formData,
            );

            if (res.success) {
                toast(res.message, "success", 7000);
                form.reset();
            } else {
                toast(res.message, "error", 7000);
            }
        } catch {
            toast("Network error. Please try again.", "error", 7000);
        } finally {
            btn.disabled = false;
            btn.textContent = "Send Message";
        }
    });
});
