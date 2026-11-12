<?php
require_once 'PHP/login/config/database.php';
require_once 'PHP/login/includes/session.php';
require_once 'PHP/login/includes/functions.php';
require_once 'PHP/login/includes/csrf.php';

requireUser();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Library Management System</title>
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/global.css">
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/navbar.css">
    <link rel="stylesheet" href="/PHP_programs/Library-MS/Assets/CSS/Contact.css">
</head>

<body>
    <?php include 'PHP/login/includes/navbar.php'; ?>

    <div class="container" style="padding-top: 5%; padding-bottom: 2%;">

        <div class="page-header text-center">
            <h1>Contact Us</h1>
            <p class="text-muted">Have a question or feedback? We'd love to hear from you.</p>
        </div>

        <div class="contact-layout">
            <div class="contact-form-card card" style="padding-bottom: -39px;">
                <h2>Send us a Message</h2>
                <form id="contactForm">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="Name" class="form-control" value="<?= e(currentUserName()) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="Email" class="form-control" value="<?= e(currentUserEmail()) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="Message" class="form-control reason-textarea" required minlength="10" maxlength="5000" placeholder="How can we help you?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2" id="contact-submit-btn">Send Message</button>
                </form>
            </div>

            <div class="contact-info-side">
                <div class="contact-info-card card">
                    <h3>Library Information</h3>

                    <div class="info-item">
                        <span class="icon">📍</span>
                        <div>
                            <strong>Address</strong>
                            <p class="text-muted">Kantipur College of Management and Information Technology, Basuki Marg, Mid-Baneshwor</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <span class="icon">📞</span>
                        <div>
                            <strong>Phone</strong>
                            <p class="text-muted">+977 9761819656</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <span class="icon">✉️</span>
                        <div>
                            <strong>Email</strong>
                            <p class="text-muted">KCMIT@edu.com</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <span class="icon">🕒</span>
                        <div>
                            <strong>Opening Hours</strong>
                            <p class="text-muted">Mon - Fri: 6:30 AM - 5:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-4 map-placeholder">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.6082528386896!2d85.33812657508042!3d27.698500276187616!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb199bc3725273%3A0x2237abfde1ac9646!2sKantipur%20College%20of%20Management%20and%20Information%20Technology!5e0!3m2!1sen!2snp!4v1788595869667!5m2!1sen!2snp" width="100%" height="250px" style="border:0; border-radius:20px;"></iframe>
        </div>
    </div>
    <script src="/PHP_programs/Library-MS/Assets/JS/global.js"></script>
    <script src="/PHP_programs/Library-MS/Assets/JS/Contact.js"></script>
</body>

</html>