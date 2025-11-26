<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | ComicVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* --- RESET --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #0f0f0f; color: #fff; height: 100vh; display: flex; overflow: hidden; }

        /* --- SPLIT LAYOUT --- */
        .split-screen { display: flex; width: 100%; height: 100%; }
        
        /* Left Side (Desktop Art) */
        .left-pane {
            flex: 1;
            background: url('https://images.unsplash.com/photo-1601645191163-3fc0d5d64e35?q=80&w=2000&auto=format&fit=crop') center/cover;
            position: relative;
            display: flex; flex-direction: column; justify-content: flex-end; padding: 60px;
        }
        .left-pane::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to top, #0f0f0f 10%, rgba(0, 120, 255, 0.3) 100%); /* Blue tint for signup */
        }
        .art-content { position: relative; z-index: 2; animation: fadeInUp 0.8s ease; }
        .art-content h1 { font-size: 48px; font-weight: 900; text-transform: uppercase; line-height: 1; margin-bottom: 15px; }
        .art-content p { font-size: 18px; color: #ddd; max-width: 400px; }

        /* Right Side (Form) */
        .right-pane {
            flex: 0 0 500px;
            background: #0f0f0f;
            display: flex; flex-direction: column; justify-content: center; padding: 60px;
            position: relative;
            box-shadow: -10px 0 50px rgba(0,0,0,0.5);
        }

        /* --- FORM STYLES --- */
        .form-header { margin-bottom: 30px; }
        .logo { color: #ec1d24; font-weight: 900; font-size: 28px; letter-spacing: 1px; display: block; margin-bottom: 20px; text-decoration: none;}
        .form-header h2 { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .form-header p { color: #666; }

        .input-group { position: relative; margin-bottom: 15px; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #555; transition: 0.3s; }
        
        .form-input {
            width: 100%; padding: 14px 14px 14px 45px;
            background: #1a1a1a; border: 1px solid #333; border-radius: 8px;
            color: white; font-size: 15px; outline: none; transition: 0.3s;
        }
        .form-input:focus { border-color: #ec1d24; background: #222; }
        .form-input:focus + i { color: #ec1d24; }

        .btn-submit {
            width: 100%; padding: 15px; background: #ec1d24; color: white;
            border: none; border-radius: 8px; font-weight: 700; font-size: 16px;
            cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;
            margin-top: 10px;
        }
        .btn-submit:hover { background: #ff333b; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(236, 29, 36, 0.3); }

        .form-footer { margin-top: 25px; text-align: center; font-size: 14px; color: #666; }
        .form-footer a { color: white; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .form-footer a:hover { color: #ec1d24; }

        .alert-error { background: rgba(236, 29, 36, 0.1); border: 1px solid rgba(236, 29, 36, 0.3); color: #ff4d4d; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; text-align: center; }

        /* --- MOBILE RESPONSIVE WITH LIGHTING --- */
        @media (max-width: 900px) {
            .left-pane { display: none; }
            
            .right-pane { 
                flex: 1; 
                padding: 40px;
                /* Add Background Image */
                background: url('https://images.unsplash.com/photo-1601645191163-3fc0d5d64e35?q=80&w=2000&auto=format&fit=crop') center/cover no-repeat;
                position: relative;
                z-index: 1;
            }

            /* The Lighting / Overlay Effect */
            .right-pane::before {
                content: '';
                position: absolute;
                top: 0; left: 0; width: 100%; height: 100%;
                /* Linear gradient: Darker at top (95%), slightly lighter at bottom (85%) for depth */
                background: linear-gradient(to bottom, rgba(15, 15, 15, 0.95) 0%, rgba(15, 15, 15, 0.85) 100%);
                z-index: -1;
            }

            .form-header p { color: #ccc; } /* Lighten text for visibility on overlay */
        }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div class="split-screen">
        <div class="left-pane">
            <div class="art-content">
                <h1>Join the<br>Multiverse</h1>
                <p>Create your free account and start building your personal comic library today.</p>
            </div>
        </div>

        <div class="right-pane">
            <a href="index.php" class="logo">CV</a>
            
            <div class="form-header">
                <h2>Create Account</h2>
                <p>It's free and takes less than a minute.</p>
            </div>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="auth.php" method="POST">
                <input type="hidden" name="action" value="signup">

                <div class="input-group">
                    <input type="text" name="username" class="form-input" placeholder="Username" required>
                    <i class="fas fa-user"></i>
                </div>

                <div class="input-group">
                    <input type="email" name="email" class="form-input" placeholder="Email Address" required>
                    <i class="fas fa-envelope"></i>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" class="form-input" placeholder="Password" required>
                    <i class="fas fa-lock"></i>
                </div>

                <div class="input-group">
                    <input type="password" name="confirm_password" class="form-input" placeholder="Confirm Password" required>
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit" class="btn-submit">Sign Up</button>
            </form>

            <div class="form-footer">
                Already have an account? <a href="login.php">Log In</a>
            </div>
        </div>
    </div>

</body>
</html>