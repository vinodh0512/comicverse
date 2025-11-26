<?php
require_once __DIR__ . '/includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | ComicVerse</title>
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
            padding: 30px; 
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
            box-sizing: border-box;
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
    </style>
</head>
<body>
    <div class="card">
        <div class="brand"><span class="logo">CV</span><strong>Reset your password</strong></div>
        <div class="title">Forgot Password</div>
        <div class="subtitle">Enter your account email. We'll send you a reset link.</div>
        <input type="email" id="emailInput" class="input" placeholder="you@example.com" required>
        <button id="sendBtn" class="btn">Send Reset Link</button>
        <div id="msg" class="msg"></div>
        <div class="footer"><a href="login.php" class="link">Back to Login</a></div>
    </div>

    <script>
        const emailInput = document.getElementById('emailInput');
        const sendBtn = document.getElementById('sendBtn');
        const msg = document.getElementById('msg');
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
        
        // Auto-focus the input on load
        document.addEventListener('DOMContentLoaded', () => {
            emailInput.focus();
        });
        
        sendBtn.addEventListener('click', async function(){
            msg.textContent=''; 
            msg.className='msg';
            
            const email = emailInput.value.trim();
            if (!email) { msg.textContent='Please enter a valid email'; msg.className='msg error'; return; }
            
            // Basic email format check
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                msg.textContent = 'Please enter a correctly formatted email address.'; msg.className = 'msg error'; playBeep(300,200,'square'); return;
            }
            
            sendBtn.disabled = true;
            try {
                // Assuming the backend API is reset_api.php
                const fd = new FormData(); fd.append('email', email);
                const r = await fetch('reset_api.php', { method:'POST', body: fd }); 
                const j = await r.json();
                
                if (j.status === 'success') { 
                    // Success message often confirms the action without confirming the email exists for security
                    msg.textContent='If the email is registered, a reset link has been sent.'; 
                    msg.className='msg ok'; 
                    playBeep(920,160,'sine');
                    // Optionally disable the input after success to prevent immediate resubmission
                    emailInput.disabled = true;
                    sendBtn.disabled = true;
                }
                else if (j.status === 'wait') { 
                    msg.textContent='Please wait ' + (j.seconds||60) + 's before requesting again'; 
                    msg.className='msg error'; 
                    playBeep(300,200,'square');
                }
                else { 
                    msg.textContent = j.message || 'Unable to send reset link'; 
                    msg.className='msg error'; 
                    playBeep(300,200,'square');
                }
            } catch(e){ 
                msg.textContent='Network error. Check your connection.'; 
                msg.className='msg error'; 
                playBeep(300,200,'square');
            }
            finally { 
                // Only re-enable the button if the request was *not* a success (or a wait error)
                if (msg.className !== 'msg ok') {
                    sendBtn.disabled = false; 
                }
            }
        });
    </script>
</body>
</html>
