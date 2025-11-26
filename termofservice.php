<?php
// termsofservices.php
require_once __DIR__ . '/includes/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ComicVerse | Terms of Service</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* --- COPY OF STYLES FROM index.php --- */
        :root {
            --primary: #ec1d24;
            --primary-glow: rgba(236, 29, 36, 0.6);
            --bg-dark: #121212;
            --bg-card: #1e1e1e;
            --text-main: #ffffff;
            --text-dim: #aaaaaa;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, Helvetica, sans-serif; }
        body { 
            background-color: var(--bg-dark); 
            color: var(--text-main); 
            overflow-x: hidden; 
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(236, 29, 36, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(0, 120, 255, 0.05) 0%, transparent 40%);
        }
        a { text-decoration: none; color: white; transition: 0.3s; }
        ul { list-style: none; }
        
        /* --- NAVBAR STYLES (Copied for consistency) --- */
        .navbar { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 0 5%; height: 60px; 
            background-color: rgba(18, 18, 18, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.05); 
            position: sticky; top: 0; z-index: 1000; 
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        }
        .logo { 
            font-size: 24px; font-weight: 900; letter-spacing: 1px; color: #fff; 
            background-color: var(--primary); padding: 0 15px; height: 100%; 
            display: flex; align-items: center; 
            clip-path: polygon(0 0, 100% 0, 90% 100%, 0% 100%);
        }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { 
            font-size: 14px; font-weight: 700; text-transform: uppercase; color: #ccc; position: relative;
        }
        .nav-links a:hover { color: white; text-shadow: 0 0 10px rgba(255,255,255,0.5); }
        .nav-links a::after {
            content: ''; position: absolute; width: 0; height: 2px; bottom: -5px; left: 0;
            background-color: var(--primary); transition: width 0.3s; box-shadow: 0 0 10px var(--primary);
        }
        .nav-links a:hover::after { width: 100%; }
        .auth-btn { font-weight: 800; font-size: 13px; cursor: pointer; text-transform: uppercase; }
        .btn-login { 
            color: var(--primary); border: 1px solid var(--primary); padding: 8px 20px; border-radius: 4px; 
            transition: all 0.3s ease;
        }
        .btn-login:hover { 
            background: var(--primary); color: white; 
            box-shadow: 0 0 15px var(--primary-glow);
        }
        .user-menu { display: flex; align-items: center; gap: 15px; font-size: 13px; }
        .user-dropdown { position: relative; }
        
        .mobile-menu-btn { display:none; background:none; border:none; color:#fff; font-size:22px; width:44px; height:44px; cursor:pointer; }
        .drawer-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:none; display:none; z-index:999; }
        .mobile-drawer { position:fixed; top:0; right:0; width:80%; height:100%; background:#1a1a1a; border-left:1px solid #333; z-index:1000; transform: translateX(100%); transition: transform .2s ease-out; padding:20px; display:flex; flex-direction:column; gap:16px; will-change: transform; -webkit-overflow-scrolling: touch; }
        .mobile-drawer.open { transform: translateX(0); }
        .drawer-overlay.open { display:block; }
        .drawer-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid #2a2a2a; margin-bottom: 10px; }
        .drawer-close-btn { color: #fff; font-size: 24px; cursor: pointer; background: none; border: none; }
        .mobile-drawer a { color:#ccc; font-weight:700; text-transform:uppercase; padding:10px; border-bottom:1px solid #2a2a2a; }

        /* --- FOOTER STYLES (Copied for consistency) --- */
        footer { 
            background-color: #050505; 
            padding: 70px 5% 30px; 
            margin-top: 80px; 
            position: relative; 
            border-top: 1px solid #222;
        }
        footer::before {
            content: ''; position: absolute; top: -1px; left: 0; width: 100%; height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            box-shadow: 0 0 10px var(--primary);
        }
        .footer-content { 
            display: flex; 
            flex-wrap: wrap; 
            justify-content: space-between; 
            align-items: flex-start;
            gap: 40px; 
            margin-bottom: 50px;
        }
        .footer-brand { flex: 1; min-width: 250px; }
        .footer-logo { 
            font-size: 32px; font-weight: 900; color: #fff; 
            background: var(--primary); width: 60px; height: 60px; 
            display: flex; justify-content: center; align-items: center; 
            margin-bottom: 20px;
            box-shadow: 0 0 15px var(--primary-glow);
            clip-path: polygon(0 0, 100% 0, 90% 100%, 0% 100%);
        }
        .footer-desc { color: #888; font-size: 14px; line-height: 1.6; max-width: 300px; }
        .footer-links-group { display: flex; gap: 60px; flex-wrap: wrap; }
        .footer-column h4 { 
            font-size: 14px; font-weight: 800; color: #fff; 
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; 
            position: relative; display: inline-block;
        }
        .footer-column h4::after {
            content: ''; position: absolute; right: -10px; bottom: 5px; 
            width: 4px; height: 4px; background: var(--primary); box-shadow: 0 0 5px var(--primary);
        }
        .footer-column ul li { margin-bottom: 12px; }
        .footer-column ul li a { 
            color: #999; font-size: 14px; transition: 0.3s; position: relative; left: 0;
        }
        .footer-column ul li a:hover { 
            color: #fff; left: 5px; text-shadow: 0 0 10px rgba(255,255,255,0.3);
        }
        .social-icons { display: flex; gap: 15px; margin-top: 20px; }
        .social-btn {
            width: 40px; height: 40px; border-radius: 50%; background: #222;
            display: flex; justify-content: center; align-items: center;
            color: #fff; transition: 0.3s; border: 1px solid #333;
        }
        .social-btn:hover { 
            background: var(--primary); border-color: var(--primary); 
            transform: translateY(-3px); box-shadow: 0 0 15px var(--primary-glow);
        }
        .copyright { 
            text-align: center; color: #444; font-size: 12px; 
            padding-top: 30px; border-top: 1px solid #1a1a1a; 
        }
        .copyright a { color: #666; font-weight: 700; }
        .copyright a:hover { color: var(--primary); }

        /* --- TERMS OF SERVICE SPECIFIC STYLES --- */
        .content-container {
            padding: 40px 5%;
            max-width: 900px;
            margin: 0 auto;
        }
        .content-container h1 {
            font-size: 36px;
            font-weight: 900;
            color: var(--primary);
            border-bottom: 2px solid #222;
            padding-bottom: 15px;
            margin-bottom: 20px;
            text-shadow: 0 0 8px rgba(236, 29, 36, 0.3);
        }
        .content-container h2 {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-top: 40px;
            margin-bottom: 15px;
            border-left: 3px solid var(--primary);
            padding-left: 10px;
        }
        .content-container p {
            font-size: 15px;
            line-height: 1.8;
            color: #ccc;
            margin-bottom: 15px;
        }
        .content-container ol, .content-container ul {
            margin-left: 25px;
            color: #ccc;
            margin-bottom: 20px;
        }
        .content-container li {
            font-size: 15px;
            margin-bottom: 8px;
            list-style: disc;
        }
        .last-updated {
            display: block;
            color: var(--text-dim);
            font-style: italic;
            margin-bottom: 30px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .content-container h1 { font-size: 28px; }
            .content-container h2 { font-size: 20px; }
        }

    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">CV</a> 
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="comic.php">Comics</a>
            <a href="manga.php">Manga</a>
            <a href="webtoon.php">Movies</a>
            <a href="help_center.php">Help Center</a>
        </div>
        
        <?php if(isset($_SESSION['username'])): ?>
            <div class="user-menu user-dropdown">
                <span class="user-name" style="cursor:pointer;">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="fas fa-caret-down"></i></span>
                <div class="dropdown" style="display:none; position:absolute; top:calc(100% + 8px); right:0; background:#1a1a1a; border:1px solid #333; border-radius:6px; min-width:160px; box-shadow:0 6px 20px rgba(0,0,0,0.5); z-index:2001;">
                    <a href="profile.php" style="display:block; padding:10px 12px; color:#ccc;">Profile</a>
                    <?php if(($_SESSION['role'] ?? '') === 'creator'): ?>
                        <a href="mystories.php" style="display:block; padding:10px 12px; color:#ccc;">Dashboard</a>
                    <?php endif; ?>
                    <a href="auth.php?action=logout" style="display:block; padding:10px 12px; color:#ccc;">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="auth-btn btn-login">LOG IN</a>
        <?php endif; ?>

        <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
    </nav>
    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-header">
            <span style="font-size: 20px; font-weight: 900; color: var(--primary);">Menu</span>
            <button class="drawer-close-btn" id="drawerCloseBtn">&times;</button>
        </div>
        <a href="index.php">Home</a>
        <a href="comic.php">Comics</a>
        <a href="manga.php">Manga</a>
        <a href="webtoon.php">Movies</a>
        <a href="help_center.php">Help Center</a>
        <?php if(isset($_SESSION['username'])): ?>
            <a href="profile.php">Profile</a>
            <?php if(($_SESSION['role'] ?? '') === 'creator'): ?>
                <a href="mystories.php">Dashboard</a>
            <?php endif; ?>
            <a href="auth.php?action=logout">Logout</a>
        <?php else: ?>
            <a href="login.php">Log In</a>
        <?php endif; ?>
    </div>

    <div class="content-container">
        <h1>Terms of Service</h1>
        <span class="last-updated">Last Updated: November 25, 2025</span>

        <h2>1. Acceptance of Terms</h2>
        <p>By accessing or using the ComicVerse service, you agree to be bound by these Terms of Service ("Terms") and all terms incorporated by reference. If you do not agree to all of these Terms, do not use the ComicVerse service.</p>

        <h2>2. User Accounts and Eligibility</h2>
        <p>To access certain features of the Service, you must register for an account. You must be at least 13 years old to use the Service. By registering, you agree to:</p>
        <ul>
            <li>Provide accurate and complete information.</li>
            <li>Maintain the security of your password.</li>
            <li>Accept all risks of unauthorized access to your account.</li>
        </ul>

        <h2>3. Content Rights and Ownership</h2>
        <h3>3.1. ComicVerse Content</h3>
        <p>All content provided by ComicVerse, including text, graphics, logos, and software, is the property of ComicVerse or its licensors and protected by intellectual property laws.</p>
        <h3>3.2. User-Generated Content (UGC)</h3>
        <p>You retain all ownership rights to the creative content you submit to the Service. By posting UGC, you grant ComicVerse a non-exclusive, worldwide, royalty-free license to use, reproduce, modify, adapt, publish, and display such content solely for the purpose of operating and promoting the Service.</p>

        <h2>4. Prohibited Conduct</h2>
        <p>You agree not to use the Service to:</p>
        <ol>
            <li>Violate any applicable law or regulation.</li>
            <li>Post or transmit any content that is defamatory, obscene, pornographic, or harassing.</li>
            <li>Infringe upon the intellectual property rights of others.</li>
            <li>Attempt to gain unauthorized access to the Service or other user accounts.</li>
            <li>Interfere with or disrupt the operation of the Service.</li>
        </ol>

        <h2>5. Termination</h2>
        <p>ComicVerse reserves the right, without notice and at its sole discretion, to terminate your license to use the Service and to block or prevent your future access to the Service if you violate these Terms.</p>

        <h2>6. Disclaimers</h2>
        <p>THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, OR NON-INFRINGEMENT.</p>

        <h2>7. Contact Information</h2>
        <p>If you have any questions about these Terms, please contact us through the <a href="help_center.php" style="color: var(--primary);">Help Center</a> or via email at legal@comicverse.com.</p>
    </div>


    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <div class="footer-logo">CV</div>
                <p class="footer-desc">ComicVerse is your ultimate destination for unlimited comics, manga, and webtoons. Dive into a universe of stories and start creating today!</p>
            </div>
            <div class="footer-links-group">
                <div class="footer-column">
                    <h4>Explore</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="comic.php">Comics</a></li>
                        <li><a href="manga.php">Manga</a></li>
                        <li><a href="webtoon.php">Movies</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="help_center.php">Help Center</a></li>
                        <li><a href="termofservice.php" style="color:var(--primary);">Terms of Service</a></li>
                        <li><a href="privacy_policy.php">Privacy Policy</a></li>
                        <li><a href="contact_us.php">Contact Us</a></li>
                    </ul>
                </div>
            </div>
            <div class="social-icons">
                <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-btn"><i class="fab fa-discord"></i></a>
            </div>
        </div>
        <div class="copyright">
            &copy; 2024 ComicVerse. All rights reserved. | Developed by <a href="uc.html">Unknown Creators</a>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Dropdown menu toggle
            const userMenu = document.querySelector('.user-dropdown .user-name');
            const dropdown = document.querySelector('.user-dropdown .dropdown');
            if (userMenu && dropdown) {
                userMenu.addEventListener('click', () => {
                    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                });
                document.addEventListener('click', (event) => {
                    if (!userMenu.contains(event.target) && !dropdown.contains(event.target)) {
                        dropdown.style.display = 'none';
                    }
                });
            }

            // Mobile menu drawer
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const drawerCloseBtn = document.getElementById('drawerCloseBtn');
            const mobileDrawer = document.getElementById('mobileDrawer');
            const drawerOverlay = document.getElementById('drawerOverlay');
            
            function toggleDrawer() {
                mobileDrawer.classList.toggle('open');
                drawerOverlay.classList.toggle('open');
            }

            if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleDrawer);
            if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', toggleDrawer);
            if (drawerOverlay) drawerOverlay.addEventListener('click', toggleDrawer);
        });
    </script>
</body>
</html>
