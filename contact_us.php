<?php
// contact_us.php
require_once __DIR__ . '/includes/session.php';

// In a real application, this section would handle form submission:
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $name = htmlspecialchars($_POST['name'] ?? '');
//     $email = htmlspecialchars($_POST['email'] ?? '');
//     $subject = htmlspecialchars($_POST['subject'] ?? '');
//     $message = htmlspecialchars($_POST['message'] ?? '');
//
//     // Simple validation and email sending logic would go here
//     // Example: mail('support@comicverse.com', $subject, "From: $name ($email)\n\n$message");
//
//     $success_message = "Thank you! Your message has been received and we will respond shortly.";
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ComicVerse | Contact Us</title>
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

        /* --- CONTACT US SPECIFIC STYLES --- */
        .contact-section {
            padding: 40px 5%;
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }
        .contact-info {
            flex: 1;
            min-width: 300px;
        }
        .contact-form-container {
            flex: 2;
            background-color: var(--bg-card);
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #222;
        }
        .contact-section h1 {
            font-size: 36px;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 20px;
            text-shadow: 0 0 8px rgba(236, 29, 36, 0.3);
        }
        .contact-info p {
            font-size: 16px;
            line-height: 1.6;
            color: #ccc;
            margin-bottom: 20px;
        }
        .contact-detail {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 15px;
            color: #ddd;
        }
        .contact-detail i {
            color: var(--primary);
            margin-right: 15px;
            font-size: 20px;
            width: 25px;
            text-align: center;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: white;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            background: #222;
            border: 1px solid #333;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 5px rgba(236, 29, 36, 0.5);
        }
        textarea.form-control {
            resize: vertical;
            min-height: 150px;
        }
        .submit-btn {
            background-color: var(--primary);
            color: white;
            padding: 12px 25px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .submit-btn:hover {
            background-color: #ff333b;
            box-shadow: 0 0 15px var(--primary-glow);
        }
        .message-success {
            padding: 15px;
            background-color: rgba(0, 166, 82, 0.2);
            border: 1px solid #00a652;
            color: #00a652;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .contact-section { flex-direction: column; gap: 30px; }
            .contact-section h1 { font-size: 28px; }
            .contact-form-container { padding: 20px; }
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

    <div class="contact-section">
        <div class="contact-info">
            <h1>Get in Touch 📩</h1>
            <p>Whether you have a technical issue, a business inquiry, or just want to say hello, we're here to help! Please fill out the form, or reach us directly using the details below.</p>
            
            <div class="contact-detail">
                <i class="fas fa-envelope"></i>
                <span>**Support:** support@comicverse.com</span>
            </div>
            <div class="contact-detail">
                <i class="fas fa-briefcase"></i>
                <span>**Business:** partnership@comicverse.com</span>
            </div>
            <div class="contact-detail">
                <i class="fas fa-phone"></i>
                <span>+1 (800) 555-0199</span>
            </div>
            <div class="contact-detail">
                <i class="fas fa-map-marker-alt"></i>
                <span>ComicVerse HQ, 101 Creative Lane, Metropolis, 10001</span>
            </div>
            
            <p style="margin-top: 30px; font-style: italic; color: var(--text-dim);">**Note:** For the quickest resolution to common issues, please check our <a href="help_center.php" style="color: var(--primary);">Help Center</a> first!</p>
        </div>

        <div class="contact-form-container">
            <?php if (!empty($success_message)): ?>
                <div class="message-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <form action="contact_us.php" method="POST">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Your Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <select id="subject" name="subject" class="form-control" required>
                        <option value="">-- Select Subject --</option>
                        <option value="Technical Support">Technical Support / Bug Report</option>
                        <option value="Billing & Account">Billing / Account Inquiry</option>
                        <option value="Creator Support">Creator Support / Upload Issue</option>
                        <option value="General Feedback">General Feedback</option>
                        <option value="Partnership Inquiry">Partnership Inquiry</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" class="form-control" required></textarea>
                </div>
                <button type="submit" class="submit-btn">Send Message <i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
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
                        <li><a href="termofservice.php">Terms of Service</a></li>
                        <li><a href="privacy_policy.php">Privacy Policy</a></li>
                        <li><a href="contact_us.php" style="color:var(--primary);">Contact Us</a></li>
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
