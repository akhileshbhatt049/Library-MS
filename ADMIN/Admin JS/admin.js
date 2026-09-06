// ADMIN/Admin JS/admin.js
document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const adminWrapper = document.querySelector('.admin-wrapper');
    if(sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            adminWrapper.classList.toggle('sidebar-open');
        });
    }

    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-link').forEach(link => {
        if(link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });

    const modals = document.querySelectorAll('.modal');
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = trigger.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('open');
        });
    });

    document.querySelectorAll('.close-modal, [data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            modals.forEach(m => m.classList.remove('open'));
        });
    });

    const searchInput = document.getElementById('table-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('.data-table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});

async function postJSON(url, data) {
    const formData = new FormData();
    for (const key in data) formData.append(key, data[key]);
    try {
        const res = await fetch(url, { method: 'POST', body: formData });
        return await res.json();
    } catch(err) {
        return { success: false, message: 'Network error' };
    }
}
