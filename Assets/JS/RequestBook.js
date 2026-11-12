document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.querySelector('.reason-textarea');
    const charCount = document.getElementById('char-count');

    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            const len = this.value.length;
            charCount.textContent = `${len} / 20 characters minimum`;
            if (len >= 20) {
                charCount.style.color = 'var(--success)';
            } else {
                charCount.style.color = 'var(--text-muted)';
            }
        });
    }

    const form = document.getElementById('requestBookForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            postJSON('/Library-MS/PHP/RequestBook.php', data)
            .then(response => {
                if(response.success) {
                    toast('Success', response.message, 'success');
                    
                    // Append to table
                    const tbody = document.getElementById('history-tbody');
                    const noMsg = document.getElementById('no-requests-msg');
                    
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${data.BookTitle}</td>
                        <td>${data.AuthorName}</td>
                        <td>Just now</td>
                    `;
                    
                    if (tbody) {
                        tbody.insertBefore(tr, tbody.firstChild);
                    } else if (noMsg) {
                        noMsg.style.display = 'none';
                        // if we want to create the table dynamically we could, but a reload is easier
                        setTimeout(() => location.reload(), 1500);
                    }
                    
                    form.reset();
                    if(charCount) charCount.textContent = '0 / 20 characters minimum';
                    if(charCount) charCount.style.color = 'var(--text-muted)';
                } else {
                    toast('Error', response.message, 'error');
                }
            })
            .catch(error => {
                toast('Error', 'Network error occurred', 'error');
            });
        });
    }
});
