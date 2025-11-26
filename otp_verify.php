<?php
require_once __DIR__ . '/includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email OTP Verification | ComicVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            /* Mixed Red & Blue Palette */
            --bg-color: #0b0f15; /* Deeper Dark Blue */
            --card-color: #141822; 
            --border-color: #2c3340; /* Subtler Border */
            --input-color: #1c212c;
            
            --brand-red: #ec1d24; /* ComicVerse Red (Primary Action) */
            --brand-red-light: #ff333b; 
            --accent-blue: #00bcd4; /* Electric Blue Accent (Focus/Links) */

            --text-color: #e0e6f1;
            --subtext-color: #9ab4cc;
            --error-color: #ff6666;
            --success-color: #00a652;
        }

        body {
            background: var(--bg-color);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            padding: 16px;
            overflow: hidden;
        }
        
        /* Red and Blue Lighting Background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Two radial gradients for the red and blue glow */
            background: radial-gradient(circle at top right, rgba(236, 29, 36, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at bottom left, rgba(0, 188, 212, 0.1) 0%, transparent 40%);
            z-index: -1;
            animation: color-shift 20s infinite alternate ease-in-out;
        }

        @keyframes color-shift {
            0% { background-position: 0% 0%; }
            100% { background-position: 100% 100%; }
        }

        .card {
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 30px; /* Consistent padding */
            width: 95%;
            max-width: 520px;
            /* Box shadow uses a subtle blend of the two main colors */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.6), 0 0 15px rgba(236, 29, 36, 0.15), 0 0 15px rgba(0, 188, 212, 0.15);
            position: relative;
            z-index: 1;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .logo {
            background: var(--brand-red);
            color: #fff;
            font-weight: 900;
            padding: 6px 12px;
            border-radius: 6px;
            box-shadow: 0 0 8px rgba(236, 29, 36, 0.6); /* Red logo glow */
        }

        .title {
            font-weight: 900;
            font-size: 26px;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        .subtitle {
            color: var(--subtext-color);
            margin-bottom: 20px;
            font-size: 15px;
        }

        .input {
            width: 100%;
            padding: 16px; /* Enhanced padding */
            background: var(--input-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-color);
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box; /* Include padding in width */
        }

        .input:focus {
            border-color: var(--accent-blue); /* Focus uses Electric Blue */
            box-shadow: 0 0 10px rgba(0, 188, 212, 0.8); /* Stronger Blue Glow */
        }

        .btn {
            width: 100%;
            margin-top: 20px;
            padding: 14px;
            background: var(--brand-red); /* Button remains Red for primary action */
            border: none;
            color: #fff;
            font-weight: 800;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(236, 29, 36, 0.4);
            transition: background 0.3s, box-shadow 0.3s;
        }

        .btn:hover:not(:disabled) {
            background: var(--brand-red-light);
            box-shadow: 0 6px 15px rgba(236, 29, 36, 0.8);
        }

        .btn:disabled {
            background: #444;
            cursor: not-allowed;
            box-shadow: none;
            opacity: 0.6;
        }

        .btn-secondary {
            flex-grow: 1;
            padding: 12px;
            background: var(--input-color);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s, border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #232936;
            border-color: var(--accent-blue);
            box-shadow: 0 0 5px rgba(0, 188, 212, 0.4); /* Subtle blue glow on secondary hover */
        }

        .btn-secondary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .msg {
            margin-top: 15px;
            font-size: 13px;
            padding: 8px 0;
            text-align: center; 
        }

        .error {
            color: var(--error-color);
        }

        .ok {
            color: var(--success-color);
        }

        .footer {
            margin-top: 20px;
            text-align: center;
        }

        .link {
            color: var(--subtext-color);
            text-decoration: none;
            transition: color 0.3s;
        }

        .link:hover {
            color: var(--accent-blue); /* Link hover uses Electric Blue */
        }

        .otp-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin: 12px 0 20px;
            cursor: text;
        }

        .otp-grid .box {
            height: 50px; 
            line-height: 50px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            background: var(--input-color);
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        
        .otp-grid:focus-within .box {
            border-color: #555;
        }

        /* Highlight the next box to be filled, using the RED brand accent */
        .otp-grid .box.active {
            border-color: var(--brand-red); 
            box-shadow: 0 0 8px rgba(236, 29, 36, 0.6);
            background: #2a1f26; /* Slightly darker/redder background for active box */
        }

        .inline {
            font-size: 13px;
            color: var(--subtext-color);
            display: block;
            margin: 0;
            text-align: right;
            white-space: nowrap;
        }

        .row {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <div class="card" id="stepEmail">
        <div class="brand"><span class="logo">CV</span><strong>Verify your ComicVerse account</strong></div>
        <div class="title">Enter Your Email</div>
        <div class="subtitle">We will send a 6-digit code to your email address.</div>
        <input type="email" id="emailInput" class="input" placeholder="you@example.com" required>
        <button id="sendBtn" class="btn">Send OTP</button>
        <div id="emailMsg" class="msg"></div>
        <div class="footer"><a href="login.php" class="link">Back to Login</a></div>
    </div>

    <div class="card" id="stepOtp" style="display:none;">
        <div class="brand"><span class="logo">CV</span><strong>Email Verification</strong></div>
        <div class="title">Verify Your Email</div>
        <div class="subtitle">We sent a 6-digit code to <strong id="sentTo"></strong>. Enter it below.</div>
        <div class="otp-grid" id="otpGrid">
            <div class="box"></div><div class="box"></div><div class="box"></div>
            <div class="box"></div><div class="box"></div><div class="box"></div>
        </div>
        <input type="text" id="codeInput" class="input" placeholder="Enter 6-digit code" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required 
               style="position:absolute; opacity:0; pointer-events:none; left:-9999px;">
        
        <button id="verifyBtn" class="btn">Verify</button>
        <div class="row">
            <button id="resendBtn" class="btn-secondary" disabled>Send Code Again</button>
            <span id="countdown" class="inline" style="margin:0;">Resend available in 1:30</span>
        </div>
        <div id="otpMsg" class="msg"></div>
        <div class="footer"><a href="#" id="changeEmail" class="link">Change email</a></div>
    </div>

    <script>
        const stepEmail = document.getElementById('stepEmail');
        const stepOtp = document.getElementById('stepOtp');
        const emailInput = document.getElementById('emailInput');
        const sendBtn = document.getElementById('sendBtn');
        const emailMsg = document.getElementById('emailMsg');
        const sentTo = document.getElementById('sentTo');
        const otpGrid = document.getElementById('otpGrid');
        const codeInput = document.getElementById('codeInput');
        const verifyBtn = document.getElementById('verifyBtn');
        const otpMsg = document.getElementById('otpMsg');
        const resendBtn = document.getElementById('resendBtn');
        const countdownEl = document.getElementById('countdown');
        const boxes = document.querySelectorAll('#otpGrid .box');

        let countdown = 90;
        function playBeep(freq=880, duration=150, type='sine'){
            try {
                const Actx = window.AudioContext || window.webkitAudioContext; if (!Actx) return;
                const ctx = new Actx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = type; osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                osc.connect(gain); gain.connect(ctx.destination);
                osc.start();
                gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + (duration/1000));
                setTimeout(()=>{ try{ osc.stop(); ctx.close(); }catch(_){} }, duration+30);
            } catch(_){}
        }
        
        // Timer formatting
        function fmt(s){ 
            const m = Math.floor(s/60), r = s%60; 
            return m+':' + String(r).padStart(2,'0'); 
        }

        // Timer function
        function tick(){ 
            if (countdown>0) { 
                countdownEl.textContent = 'Resend available in ' + fmt(countdown); 
                countdown--; 
                setTimeout(tick, 1000); 
            } else { 
                countdownEl.textContent = 'You can request a new code'; 
                resendBtn.disabled = false; 
            } 
        }

        // Reflect input to visual boxes and add active class for UX
        function reflect(){ 
            const v = (codeInput.value||'').replace(/[^0-9]/g,'').slice(0,6); 
            codeInput.value = v; 
            boxes.forEach((b,i)=>{ 
                b.textContent = v[i]||''; 
                // Set active class on the next empty box, or the last box if full
                b.classList.toggle('active', i === v.length && i < 6); 
            });
            if (v.length === 6) { boxes[5].classList.add('active'); } // Keep the last box active if full
            else if (v.length < 6) { boxes[v.length].classList.add('active'); } // Highlight the next expected box
            else if (v.length === 0) { boxes[0].classList.add('active'); } // Highlight first box if empty
        }
        
        // Custom focus behavior to keep the actual input in focus
        otpGrid.addEventListener('click', () => { 
            codeInput.focus(); 
            reflect(); // Ensure active box is updated on click
        });
        codeInput.addEventListener('focus', reflect); // Ensure visual update on focus
        codeInput.addEventListener('blur', () => { // Remove active class on blur
            boxes.forEach(b => b.classList.remove('active'));
        });


        codeInput.addEventListener('input', reflect);
        codeInput.addEventListener('paste', () => setTimeout(reflect, 50));

        // Start OTP Request (Step 1 -> Step 2)
        async function sendOtp(){
            emailMsg.textContent = '';
            const email = emailInput.value.trim();
            if (!email) { emailMsg.textContent = 'Please enter a valid email'; emailMsg.className = 'msg error'; return; }
            
            // Basic email format check
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                emailMsg.textContent = 'Please enter a correctly formatted email address.'; emailMsg.className = 'msg error'; return;
            }

            sendBtn.disabled = true;
            try {
                const fd = new FormData(); fd.append('action','send'); fd.append('email', email);
                const r = await fetch('verify_api.php', { method:'POST', body: fd }); 
                const j = await r.json();
                
                if (j.status === 'success') {
                    sentTo.textContent = email;
                    stepEmail.style.display = 'none'; stepOtp.style.display = 'block';
                    resendBtn.disabled = true; countdown = 90; tick();
                    otpMsg.textContent = 'Code sent to your email'; otpMsg.className = 'msg ok';
                    codeInput.focus(); // Auto-focus OTP input
                    reflect(); // Update OTP boxes immediately
                    playBeep(920,160,'sine');
                } else if (j.status === 'wait') {
                    emailMsg.textContent = 'Please wait ' + (j.seconds||30) + 's before requesting again'; emailMsg.className = 'msg error';
                    playBeep(300,200,'square');
                } else {
                    emailMsg.textContent = j.message || 'Unable to send code'; emailMsg.className = 'msg error';
                    playBeep(300,200,'square');
                }
            } catch(e){ 
                emailMsg.textContent = 'Network error. Check your connection.'; emailMsg.className = 'msg error'; 
                playBeep(300,200,'square');
            }
            finally { 
                sendBtn.disabled = false; 
            }
        }

        // Verify OTP (Step 2)
        async function verifyOtp(){
            otpMsg.textContent = '';
            const email = sentTo.textContent;
            const code = codeInput.value.replace(/[^0-9]/g,'');
            if (code.length !== 6) { otpMsg.textContent = 'Enter the 6-digit code'; otpMsg.className = 'msg error'; return; }
            
            verifyBtn.disabled = true;
            try {
                const fd = new FormData(); fd.append('action','verify'); fd.append('email', email); fd.append('code', code);
                const r = await fetch('verify_api.php', { method:'POST', body: fd }); 
                const j = await r.json();
                
                if (j.status === 'success') { 
                    otpMsg.textContent = 'Email verified successfully! Redirecting...'; otpMsg.className = 'msg ok'; 
                    playBeep(1100,180,'triangle');
                    setTimeout(()=>{ window.location.href = 'index.php'; }, 600); 
                }
                else { 
                    otpMsg.textContent = j.message || 'Invalid or expired code. Try again.'; otpMsg.className = 'msg error'; 
                    playBeep(300,220,'square');
                }
            } catch(e){ 
                otpMsg.textContent = 'Network error during verification.'; otpMsg.className = 'msg error'; 
                playBeep(300,220,'square');
            }
            finally { 
                verifyBtn.disabled = false; 
            }
        }

        // Resend OTP
        async function resend(){
            resendBtn.disabled = true; 
            countdown = 30; // Shorter wait time for resend attempts (customizable)
            tick();
            
            otpMsg.textContent = ''; // Clear previous message
            
            try {
                const fd = new FormData(); fd.append('action','send'); fd.append('email', sentTo.textContent);
                const r = await fetch('verify_api.php', { method:'POST', body: fd });
                const j = await r.json();
                if (j.status === 'success') {
                     otpMsg.textContent = 'New code sent!'; otpMsg.className = 'msg ok';
                     playBeep(920,160,'sine');
                } else {
                     otpMsg.textContent = j.message || 'Unable to resend code.'; otpMsg.className = 'msg error';
                     playBeep(300,200,'square');
                }
            } catch(e){
                 otpMsg.textContent = 'Network error during resend.'; otpMsg.className = 'msg error';
            }
        }

        // Event Listeners
        sendBtn.addEventListener('click', sendOtp);
        verifyBtn.addEventListener('click', verifyOtp);
        resendBtn.addEventListener('click', resend);
        document.getElementById('changeEmail').addEventListener('click', function(e){ 
            e.preventDefault(); 
            stepOtp.style.display = 'none'; 
            stepEmail.style.display = 'block'; 
            emailInput.focus();
            // Clear OTP input and stop the countdown when returning to email step
            codeInput.value = '';
            reflect(); // Clear visual boxes
            countdown = 0; 
            countdownEl.textContent = ''; 
            resendBtn.disabled = true;
        });

        // Initialize the input reflection on page load if the OTP step is somehow active, or after state changes
        // When stepEmail is displayed, ensure email input is focused
        document.addEventListener('DOMContentLoaded', () => {
            if (stepEmail.style.display !== 'none') {
                emailInput.focus();
            }
        });
    </script>
</body>
</html>+
