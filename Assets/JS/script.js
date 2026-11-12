/**
 * script.js — Login / Register / Forgot Password (OTP flow)
 * Library Management System
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── Tab Switching ──────────────────────────────────────────
    const tabBtns   = document.querySelectorAll('.tab-btn[data-target]');
    const authForms = document.querySelectorAll('.auth-form');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            authForms.forEach(f => f.classList.remove('active'));
            btn.classList.add('active');
            const el = document.getElementById(btn.getAttribute('data-target'));
            if (el) el.classList.add('active');
        });
    });

    // ── Password Visibility Toggle ─────────────────────────────
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const input = this.previousElementSibling;
            if (!input || input.type === undefined) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });

    // ── Password Strength Meter ────────────────────────────────
    function measureStrength(val) {
        let s = 0;
        if (val.length >= 8) s++;
        if (/[A-Z]/.test(val)) s++;
        if (/[0-9]/.test(val)) s++;
        if (/[^a-zA-Z0-9]/.test(val)) s++;
        return s;
    }
    function applyStrength(bar, val) {
        if (!bar) return;
        const s     = measureStrength(val);
        const width = [0, 33, 66, 85, 100][s] + '%';
        const color = [null, 'var(--danger)', 'var(--warning)', 'var(--warning)', 'var(--success)'][s];
        bar.style.width           = val ? width : '0';
        bar.style.backgroundColor = color || '';
    }
    const regPass = document.getElementById('reg_password');
    const strBar  = document.getElementById('strength-bar');
    if (regPass) regPass.addEventListener('input', e => applyStrength(strBar, e.target.value));

    const rstPass = document.getElementById('reset_password');
    const rstBar  = document.getElementById('reset-strength-bar');
    if (rstPass) rstPass.addEventListener('input', e => applyStrength(rstBar, e.target.value));

    // ── Register multi-step ────────────────────────────────────
    const btnNext  = document.getElementById('btn-next');
    const btnBack  = document.getElementById('btn-back');
    const regStep1 = document.getElementById('reg-step-1');
    const regStep2 = document.getElementById('reg-step-2');
    const dot1     = document.getElementById('dot-1');
    const dot2     = document.getElementById('dot-2');

    btnNext?.addEventListener('click', () => {
        const name  = document.getElementById('reg_name')?.value.trim();
        const email = document.getElementById('reg_email')?.value.trim();
        const phone = document.getElementById('reg_phone')?.value.trim();
        if (!name || !email || !phone) { toast('Please fill all fields', 'error'); return; }
        regStep1.classList.remove('active');
        regStep2.classList.add('active');
        dot1.classList.remove('active');
        dot2.classList.add('active');
    });
    btnBack?.addEventListener('click', () => {
        regStep2.classList.remove('active');
        regStep1.classList.add('active');
        dot2.classList.remove('active');
        dot1.classList.add('active');
    });

    // ── Login form ─────────────────────────────────────────────
    document.getElementById('form-login')?.addEventListener('submit', async e => {
        e.preventDefault();
        const btn   = e.target.querySelector('[type="submit"]');
        const email = document.getElementById('login_email').value;
        const pass  = document.getElementById('login_password').value;
        btn.disabled = true; btn.textContent = 'Signing in…';
        try {
            const res = await postJSON('/PHP_programs/Library-MS/PHP/login/auth/login.php', { email, password: pass });
            if (res.success)  window.location.href = res.redirect;
            else              toast(res.message, 'error', 7000);
        } catch { toast('Network error. Please try again.', 'error', 7000); }
        finally { btn.disabled = false; btn.textContent = 'Sign In'; }
    });

    // ── Register form ──────────────────────────────────────────
    document.getElementById('form-register')?.addEventListener('submit', async e => {
        e.preventDefault();
        const btn  = e.target.querySelector('[type="submit"]');
        const name = document.getElementById('reg_name').value;
        const em   = document.getElementById('reg_email').value;
        const ph   = document.getElementById('reg_phone').value;
        const pw   = document.getElementById('reg_password').value;
        const cf   = document.getElementById('reg_confirm').value;
        if (pw !== cf) { toast('Passwords do not match', 'error', 7000); return; }
        if (measureStrength(pw) < 2) { toast('Password too weak — add letters, numbers and symbols', 'error', 7000); return; }
        btn.disabled = true; btn.textContent = 'Creating…';
        try {
            const res = await postJSON('/PHP_programs/Library-MS/PHP/login/auth/register.php', {
                fullname: name, email: em, phone: ph, password: pw, confirmPassword: cf
            });
            if (res.success) { toast(res.message, 'success', 7000); setTimeout(() => location.reload(), 2000); }
            else               toast(res.message, 'error', 7000);
        } catch { toast('Network error.', 'error', 7000); }
        finally { btn.disabled = false; btn.textContent = 'Create Account'; }
    });

    // ── Forgot Password — Show / Hide ──────────────────────────
    const loginMain    = document.getElementById('login-main');
    const forgotSec    = document.getElementById('forgot-section');
    const forgotLink   = document.querySelector('.forgot-link');
    const backToLogin  = document.getElementById('back-to-login');

    forgotLink?.addEventListener('click', e => {
        e.preventDefault();
        loginMain.style.display = 'none';
        forgotSec.style.display  = 'block';
        showForgotStep(1);
    });
    backToLogin?.addEventListener('click', e => {
        e.preventDefault();
        forgotSec.style.display  = 'none';
        loginMain.style.display = 'block';
        clearCountdown();
    });

    // ── OTP Countdown state ────────────────────────────────────
    let countdownTimer = null;
    let otpEmail       = '';

    function showForgotStep(n) {
        [1, 2, 3].forEach(i => {
            const el = document.getElementById('forgot-step-' + i);
            if (el) el.style.display = (i === n) ? 'block' : 'none';
        });
    }
    function startCountdown(seconds) {
        clearCountdown();
        let left = seconds;
        const el = document.getElementById('otp-countdown');
        const update = () => {
            if (!el) return;
            const m = String(Math.floor(left / 60)).padStart(2, '0');
            const s = String(left % 60).padStart(2, '0');
            el.textContent = m + ':' + s;
            el.style.color = left <= 10 ? 'var(--danger)' : 'var(--primary)';
            if (left <= 0) {
                clearCountdown();
                toast('OTP expired. Please request a new one.', 'warning', 7000);
                const btn = document.getElementById('btn-resend-otp');
                const verBtn = document.getElementById('btn-verify-otp');
                if (btn)    btn.style.display    = 'inline-block';
                if (verBtn) verBtn.disabled      = true;
            }
            left--;
        };
        update();
        countdownTimer = setInterval(update, 1000);
    }
    function clearCountdown() {
        if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
    }

    // ── Step 1: Send OTP ───────────────────────────────────────
    document.getElementById('btn-send-otp')?.addEventListener('click', async function () {
        const email = document.getElementById('forgot_email')?.value.trim();
        if (!email) { toast('Please enter your email address', 'error', 7000); return; }
        this.disabled = true; this.textContent = 'Sending…';
        try {
            const res = await postJSON('/PHP_programs/Library-MS/PHP/login/auth/send-otp.php', { email });
            if (res.success) {
                otpEmail = email;
                toast(res.message, 'success', 7000);
                showForgotStep(2);
                startCountdown(60);
                document.getElementById('otp_input')?.focus();
            } else {
                toast(res.message, 'error', 7000);
            }
        } catch { toast('Network error. Check your connection.', 'error', 7000); }
        finally { this.disabled = false; this.textContent = 'Send OTP'; }
    });

    // ── Step 2: Verify OTP ─────────────────────────────────────
    document.getElementById('btn-verify-otp')?.addEventListener('click', async function () {
        const otp = document.getElementById('otp_input')?.value.trim();
        if (!otp || !/^\d{6}$/.test(otp)) { toast('Enter a valid 6-digit OTP', 'error', 7000); return; }
        this.disabled = true; this.textContent = 'Verifying…';
        try {
            const res = await postJSON('/PHP_programs/Library-MS/PHP/login/auth/verify-otp.php', { email: otpEmail, otp });
            if (res.success) {
                clearCountdown();
                toast(res.message, 'success', 7000);
                showForgotStep(3);
                document.getElementById('reset_password')?.focus();
            } else {
                toast(res.message, 'error', 7000);
            }
        } catch { toast('Network error.', 'error', 7000); }
        finally { this.disabled = false; this.textContent = 'Verify OTP'; }
    });

    // Allow pressing Enter in OTP input to verify
    document.getElementById('otp_input')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') document.getElementById('btn-verify-otp')?.click();
    });

    // ── Resend OTP ─────────────────────────────────────────────
    document.getElementById('btn-resend-otp')?.addEventListener('click', async function () {
        this.disabled = true; this.textContent = 'Sending…';
        try {
            const res = await postJSON('/PHP_programs/Library-MS/PHP/login/auth/send-otp.php', { email: otpEmail });
            if (res.success) {
                toast(res.message, 'success', 7000);
                this.style.display = 'none';
                startCountdown(60);
                const verBtn = document.getElementById('btn-verify-otp');
                if (verBtn) verBtn.disabled = false;
                document.getElementById('otp_input').value = '';
                document.getElementById('otp_input')?.focus();
            } else {
                toast(res.message, 'error', 7000);
            }
        } catch { toast('Network error.', 'error', 7000); }
        finally { this.disabled = false; this.textContent = 'Resend OTP'; }
    });

    // ── Step 3: Reset Password ─────────────────────────────────
    document.getElementById('btn-reset-password')?.addEventListener('click', async function () {
        const pw = document.getElementById('reset_password')?.value;
        const cf = document.getElementById('reset_confirm')?.value;
        if (!pw || pw.length < 8) { toast('Password must be at least 8 characters', 'error', 7000); return; }
        if (pw !== cf) { toast('Passwords do not match', 'error', 7000); return; }
        this.disabled = true; this.textContent = 'Resetting…';
        try {
            const res = await postJSON('/PHP_programs/Library-MS/PHP/login/auth/reset-password.php', {
                password: pw, confirmPassword: cf
            });
            if (res.success) {
                toast(res.message, 'success', 7000);
                setTimeout(() => {
                    forgotSec.style.display  = 'none';
                    loginMain.style.display = 'block';
                }, 2500);
            } else {
                toast(res.message, 'error', 7000);
            }
        } catch { toast('Network error.', 'error', 7000); }
        finally { this.disabled = false; this.textContent = 'Reset Password'; }
    });

});
