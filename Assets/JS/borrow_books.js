/**
 * borrow_books.js — Helper stubs (main logic is now inline in BorrowBooks.php)
 * Kept for backward compatibility if referenced elsewhere.
 */

// openBorrowModal is defined inline in BorrowBooks.php — stub here for safety
if (typeof openBorrowModal === 'undefined') {
    window.openBorrowModal = function (book) {
        document.getElementById('modal-book-title').textContent  = book.title;
        document.getElementById('modal-book-author').textContent = book.author;
        document.getElementById('modal-book-isbn').textContent   = book.isbn;
        document.getElementById('input-book-title').value  = book.title;
        document.getElementById('input-book-author').value = book.author;
        document.getElementById('input-book-isbn').value   = book.isbn;
        if (typeof openModal === 'function') openModal('borrowModal');
    };
}
