<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ComicVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* --- RESET --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #0f0f0f; color: #fff; height: 100vh; display: flex; overflow: hidden; }

        /* --- SPLIT LAYOUT --- */
        .split-screen { display: flex; width: 100%; height: 100%; }
        
        /* Left Side (Art - Desktop) */
        .left-pane {
            flex: 1;
            /* High quality comic/manga style background */
            background: url('https://images.unsplash.com/photo-1612036782180-6f0b6cd846fe?q=80&w=2070&auto=format&fit=crop') center/cover;
            position: relative;
            display: flex; flex-direction: column; justify-content: flex-end; padding: 60px;
        }
        /* Red gradient overlay for style */
        .left-pane::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to top, #0f0f0f 10%, rgba(236, 29, 36, 0.3) 100%);
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
        .form-header { margin-bottom: 40px; }
        .logo { color: #ec1d24; font-weight: 900; font-size: 28px; letter-spacing: 1px; display: block; margin-bottom: 20px; text-decoration: none;}
        .form-header h2 { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .form-header p { color: #666; }

        .input-group { position: relative; margin-bottom: 20px; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #555; transition: 0.3s; }
        
        .form-input {
            width: 100%; padding: 15px 15px 15px 45px;
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

        .form-footer { margin-top: 30px; text-align: center; font-size: 14px; color: #666; }
        .form-footer a { color: white; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .form-footer a:hover { color: #ec1d24; }

        /* Alerts */
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 25px; font-size: 14px; text-align: center; }
        .alert-error { background: rgba(236, 29, 36, 0.1); border: 1px solid rgba(236, 29, 36, 0.3); color: #ff4d4d; }
        .alert-success { background: rgba(0, 166, 82, 0.1); border: 1px solid rgba(0, 166, 82, 0.3); color: #00a652; }

        /* --- MOBILE RESPONSIVE --- */
        @media (max-width: 900px) {
            .left-pane { display: none; } /* Hide separate art pane */
            
            .right-pane { 
                flex: 1; 
                padding: 40px;
                /* Apply art to background */
                background: url('https://images.unsplash.com/photo-1612036782180-6f0b6cd846fe?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;
                position: relative;
                z-index: 1;
            }
            
            /* Dark Overlay for readability */
            .right-pane::before {
                content: '';
                position: absolute;
                top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(15, 15, 15, 0.85); /* 85% opacity black */
                z-index: -1;
            }
            
            .form-header p { color: #bbb; }
        }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div class="split-screen">
        <div class="left-pane">
            <div class="art-content">
                <h1>Welcome to<br>ComicVerse</h1>
                <p>Your ultimate destination for unlimited digital comics, manga, and webtoons.</p>
            </div>
        </div>

        <div class="right-pane">
            <a href="index.php" class="logo">CV</a>
            
            <?php $isCreator = (isset($_GET['role']) && $_GET['role'] === 'creator'); ?>
            <div class="form-header">
                <h2><?php echo $isCreator ? 'Creator Sign In' : 'Sign In'; ?></h2>
                <p><?php echo $isCreator ? 'Enter your creator credentials to access the studio.' : 'Enter your details to continue reading.'; ?></p>
            </div>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Account created! Please login.
                </div>
            <?php endif; ?>

            <form action="auth.php" method="POST">
                <?php $action = $isCreator ? 'creator_login' : 'login'; ?>
                <input type="hidden" name="action" value="<?php echo $action; ?>">

                <div class="input-group">
                    <input type="email" name="email" class="form-input" placeholder="Email Address" required>
                    <i class="fas fa-envelope"></i>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" class="form-input" placeholder="Password" required>
                    <i class="fas fa-lock"></i>
                </div>

                <div style="display:flex; justify-content:space-between; margin-bottom:25px; font-size:13px; color:#888;">
                    <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                        <input type="checkbox" style="accent-color:#ec1d24;"> Remember me
                    </label>
                    <a href="forgot_password.php" style="color:#888; text-decoration:none;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-submit">Log In</button>
            </form>

            <div class="form-footer">
                Don't have an account? <a href="signup.php">Create one for free</a>
            </div>
        </div>
    </div>

</body>
</html>
