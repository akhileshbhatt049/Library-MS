<?php
    require_once 'PHP/login/includes/session.php';
    require_once 'PHP/login/includes/csrf.php';
    redirectIfLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to Library Management System">
    <link rel="stylesheet" href="Assets/CSS/global.css">
    <link rel="stylesheet" href="Assets/CSS/navbar.css">
    <link rel="stylesheet" href="Assets/CSS/CreateAccount.css">
    <title>LibraryMS—Login</title>
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-brand">
            <div class="auth-brand-content">
                <h1>LibraryMS</h1>
                <p>Your Gateway to Knowledge</p>

                <ul class="feature-list">
                    <li>
                        <img src="Assets/Images/Book.png" alt="Book Icon" style="width:55px;height:35px;margin-right:-5px;" />
                        Browse 1000+ Books
                    </li>
                    
                    <li>
                        <img src="Assets/Images/document.png" alt="Document Icon" style="width:55px;height:35px;margin-right:-5px;" />
                        Digital Syllabus Access
                    </li>
                    
                    <li style="margin-left:10px;">
                        <img src="Assets/Images/BorrowBook.png" alt="Request Icon" style="width:35px; height:35px; margin-right:-5px;" />
                        Easy Borrow Requests
                    </li>
                </ul>

            </div>
        </div>

        <div class="auth-form-side">
            <div class="auth-form-container">
               
                <!-- Account Buttons -->
                <div class="auth-tabs">
                    <button class="tab-btn active" data-target="login-wrapper">Sign In</button>
                    <button class="tab-btn" data-target="register-wrapper">Create Account</button>
                </div>

                <!-- LOGIN TAB -->
                <div id="login-wrapper" class="auth-form active">
                    <div id="login-main">
                        
                        <form id="form-login">
                            <?= csrfField() ?>
                            
                            <div class="form-group">
                                <label for="login_email">Email</label>
                                <input type="email" id="login_email" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="login_password">Password</label>

                                <div class="password-wrapper">
                                    <input type="password" id="login_password" class="form-control" required>

                                    <button class="password-toggle" title="Toggle password visibility">
                                        <img src="Assets/Images/viewButton.png" alt="view password" style="width:30px;height:20px;">
                                    </button>

                                </div>
                            </div>

                            <a href="#" class="forgot-link">Forgot Password?</a>
                            <button type="submit" class="btn btn-primary btn-full btn-lg">Sign In</button>
                        </form>
                    </div>

                    <div id="forgot-section" style="display: none;">
                        <a href="#" class="back-link" id="back-to-login">
                            <span style="font-size:1.5rem;position:relative; bottom:2px;">←</span> Back to login
                        </a>

                         <!-- Email -->
                        <div id="forgot-step-1">
                            <h3 style="margin-bottom:.4rem;"> Reset Password </h3>
                             
                            <p style="color:var(--text-muted);font-size:.87rem;margin-bottom:1.25rem;">
                                Enter your registered email and we'll send you a 6-digit OTP.
                            </p>
                            
                            <div class="form-group">
                                <label for="forgot_email">Email Address</label>
                                <input type="email" id="forgot_email" class="form-control" required placeholder="name@example.com">
                            </div>

                            <button type="button" id="btn-send-otp" class="btn btn-primary btn-full">Send OTP</button>
                        </div>

                        <!-- OTP -->
                        <div id="forgot-step-2" style="display:none;">
                            <h3 style="margin-bottom:.4rem;">Enter OTP</h3>
                            
                            <p style="color:var(--text-muted);font-size:.87rem;margin-bottom:.5rem;">
                                A 6-digit code was sent to your email. It expires in:
                            </p>
                            
                            <div style="font-size:1.5rem; font-weight:700; color:var(--primary); margin-bottom:1rem; font-family:monospace;" id="otp-countdown">
                                00:60
                            </div>
                            
                            <div class="form-group">
                                <label for="otp_input">OTP Code</label>
                                
                                <input type="text" id="otp_input" class="form-control" maxlength="6" pattern="\d{6}" inputmode="numeric" 
                                placeholder="6-digit code" style="font-size:1.5rem; letter-spacing:.4rem; text-align:center;">
                            </div>
                            
                            <button type="button" id="btn-verify-otp" class="btn btn-primary btn-full">Verify
                                OTP
                            </button>
                            
                            <button type="button" id="btn-resend-otp" class="btn btn-secondary btn-full" style="margin-top:.5rem;display:none;">
                                Resend OTP
                            </button>
                        </div>

                        <!-- New Password -->
                        <div id="forgot-step-3" style="display:none;">
                            
                            <h3 style="margin-bottom:.4rem;">New Password</h3>
                            
                            <p style="color:var(--text-muted);font-size:.87rem;margin-bottom:1.25rem;">OTP verified! Set
                                your new password.
                            </p>
                            
                            <div class="form-group">
                                <label>New Password</label>
                                
                                <div class="password-wrapper">
                                    <input type="password" id="reset_password" class="form-control" required placeholder="Min 8 chars, letters + numbers">

                                    <button class="password-toggle" type="button">
                                        <!-- <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg> -->
                                        Press Button
                                    </button>
                                </div>

                                <div class="password-strength">
                                    <div class="password-strength-bar" id="reset-strength-bar"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Confirm Password</label>
                                
                                <div class="password-wrapper">
                                    <input type="password" id="reset_confirm" class="form-control" required placeholder="Repeat password">
                                    
                                    <button class="password-toggle" type="button">
                                        <!-- <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg> -->
                                        Press Button
                                    </button>
                                </div>
                            </div>

                            <button type="button" id="btn-reset-password" class="btn btn-primary btn-full">
                                Reset Password
                            </button>
                        </div>
                    </div>
                </div>

                <!-- REGISTER TAB -->
                <div id="register-wrapper" class="auth-form">
                    <div class="step-indicator">
                        <div class="step-dot active" id="dot-1"></div>
                        <div class="step-dot" id="dot-2"></div>
                    </div>

                    <form id="form-register">
                        <?= csrfField() ?>

                        <div id="reg-step-1" class="form-step active">
                            <div class="form-group">
                                <label for="reg_name">Full Name</label>
                                <input type="text" id="reg_name" class="form-control" required>
                            </div>
                     
                            <div class="form-group">
                                <label for="reg_email">Email</label>
                                <input type="email" id="reg_email" class="form-control" required>
                            </div>
                     
                            <div class="form-group">
                                <label for="reg_phone">Phone Number</label>
                                <input type="text" id="reg_phone" class="form-control" required>
                            </div>
                     
                            <button type="button" id="btn-next" class="btn btn-primary btn-full">Next</button>
                        </div>

                        <div id="reg-step-2" class="form-step">
                            <div class="form-group">
                                
                                <label for="reg_password">Password</label>
                                
                                <div class="password-wrapper">
                                    <input type="password" id="reg_password" class="form-control" required>
                                
                                    <button class="password-toggle">
                                        <!-- <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg> -->
                                        Press Button
                                    </button>
                                </div>

                                <div class="password-strength">
                                    <div class="password-strength-bar" id="strength-bar"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="reg_confirm">Confirm Password</label>
                                
                                <div class="password-wrapper">
                                    <input type="password" id="reg_confirm" class="form-control" required>
                                    <button class="password-toggle">
                                        <!-- <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg> -->
                                        Press Button
                                    </button>
                                </div>
                            </div>

                            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                                <button type="button" id="btn-back" class="btn btn-secondary" style="flex:1;">Back</button>
                                <button type="submit" class="btn btn-primary" style="flex:2;">Create Account</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script src="Assets/JS/global.js"></script>
    <script src="Assets/JS/script.js"></script>
</body>

</html>