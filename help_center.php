<?php
// help_center.php
require_once __DIR__ . '/includes/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ComicVerse | Help Center</title>
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


        /* --- HELP CENTER SPECIFIC STYLES --- */
        .help-hero {
            background-color: #1a1a1a;
            padding: 80px 5%;
            text-align: center;
            border-bottom: 3px solid var(--primary);
            margin-bottom: 40px;
        }
        .help-hero h1 {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 15px;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(236, 29, 36, 0.4);
        }
        .help-hero p {
            font-size: 18px;
            color: #ccc;
            margin-bottom: 30px;
        }
        .help-search-box {
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }
        .help-search-box input {
            flex-grow: 1;
            padding: 15px 20px;
            border: none;
            background: transparent;
            color: white;
            font-size: 16px;
            outline: none;
        }
        .help-search-box button {
            background: var(--primary);
            border: none;
            color: white;
            padding: 15px 25px;
            cursor: pointer;
            font-size: 18px;
            transition: background 0.3s;
        }
        .help-search-box button:hover {
            background: #ff333b;
        }

        /* FAQ Grid */
        .faq-container {
            padding: 0 5%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .faq-card {
            background-color: var(--bg-card);
            border: 1px solid #222;
            border-radius: 8px;
            padding: 25px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        .faq-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 20px rgba(236, 29, 36, 0.2);
        }
        .faq-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: var(--primary);
        }
        .faq-card p {
            color: #ccc;
            line-height: 1.6;
            font-size: 14px;
        }
        .faq-card a {
            color: var(--primary);
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }
        .faq-card a:hover {
            color: white;
            text-decoration: underline;
        }

        /* Accordion Section */
        .accordion-section {
            margin-top: 60px;
        }
        .accordion-item {
            border-bottom: 1px solid #222;
            margin-bottom: 15px;
        }
        .accordion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            color: white;
            transition: color 0.3s;
        }
        .accordion-header:hover {
            color: var(--primary);
        }
        .accordion-header i {
            transition: transform 0.3s;
        }
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-out, padding 0.4s ease-out;
            padding: 0 15px;
            color: #ccc;
            font-size: 15px;
            line-height: 1.6;
        }
        .accordion-item.active .accordion-content {
            max-height: 300px; /* Sufficiently large to show content */
            padding-bottom: 15px;
            padding-top: 5px;
        }
        .accordion-item.active .accordion-header i {
            transform: rotate(180deg);
            color: var(--primary);
        }
        .section-title {
            text-align: center;
            font-size: 32px;
            font-weight: 900;
            color: white;
            text-transform: uppercase;
            margin-bottom: 40px;
        }


        @media (max-width: 768px) {
            .help-hero h1 { font-size: 36px; }
            .help-search-box { flex-direction: column; border-radius: 4px; }
            .help-search-box input { padding: 10px 15px; }
            .help-search-box button { padding: 10px 15px; }
            .faq-grid { grid-template-columns: 1fr; }
            .accordion-header { font-size: 16px; }
        }
        /* Mobile menu styles */
        .drawer-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid #2a2a2a; margin-bottom: 10px; }
        .drawer-close-btn { color: #fff; font-size: 24px; cursor: pointer; background: none; border: none; }
        .mobile-drawer a { color:#ccc; font-weight:700; text-transform:uppercase; padding:10px; border-bottom:1px solid #2a2a2a; }
        @media (max-width: 768px) { 
            .nav-links { display: none; } 
            .mobile-menu-btn { display:block; } 
            .btn-logout { display: none; } 
            .btn-login { display: none; } 
            .user-menu { margin-left: 10px; display:flex; gap:10px; } 
            .user-menu a { display:none; }
            .user-name { color:#fff; font-weight:700; } 
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
            <a href="help_center.php" style="color:var(--primary);">Help Center</a>
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
        <a href="help_center.php" style="color:var(--primary);">Help Center</a>
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

    <section class="help-hero">
        <h1>How Can We Help?</h1>
        <p>Search for guides, troubleshooting, and answers to your questions.</p>
        <div class="help-search-box">
            <input type="text" id="helpSearchInput" placeholder="Enter keywords, e.g., 'reading issues' or 'uploading story'">
            <button onclick="performHelpSearch()"><i class="fas fa-search"></i></button>
        </div>
    </section>

    <div class="faq-container">
        
        <h2 class="section-title">Quick Links</h2>
        <div class="faq-grid">
            <div class="faq-card">
                <h3>Account & Profile</h3>
                <p>Manage your subscription, update your personal information, change your password, and view reading history.</p>
                <a href="#">Go to Account Settings <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="faq-card">
                <h3>Reading Troubleshooting</h3>
                <p>Fix common issues like blank pages, slow loading, or chapter synchronization problems across different devices.</p>
                <a href="#">View Reading Guide <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="faq-card">
                <h3>Creator Tools</h3>
                <p>Get started with the Creator Dashboard, learn how to upload content, manage chapters, and track your views.</p>
                <a href="#">Access Creator Handbook <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="accordion-section">
            <h2 class="section-title">Frequently Asked Questions</h2>
            
            <div class="accordion-item">
                <div class="accordion-header">
                    How do I reset my password?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="accordion-content">
                    <p>To reset your password, navigate to the **Login** page and click the "**Forgot Password?**" link. Enter the email address associated with your ComicVerse account, and we will send you a link to securely reset your password.</p>
                </div>
            </div>

            <div class="accordion-item">
                <div class="accordion-header">
                    What is the difference between Comics, Manga, and Movies?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="accordion-content">
                    <p>On ComicVerse, we categorize based primarily on reading format and origin:</p>
                    <ul>
                        <li>**Comics:** Typically feature a left-to-right reading direction and are often full-color.</li>
                        <li>**Manga:** Use a right-to-left reading direction and are traditionally black and white.</li>
                        <li>**Movies:** Video content; in our app this category replaces Webtoons labeling.</li>
                    </ul>
                </div>
            </div>

            <div class="accordion-item">
                <div class="accordion-header">
                    How do I upload my own story as a Creator?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="accordion-content">
                    <p>If you have a Creator account, log in and go to your **Creator Dashboard** (accessible from the user menu). From there, select "**New Story**" and follow the steps to upload your cover, metadata, and chapter files. Ensure your images meet the required dimensions for optimal display.</p>
                </div>
            </div>

            <div class="accordion-item">
                <div class="accordion-header">
                    Why are my chapters loading slowly?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="accordion-content">
                    <p>Slow loading can be due to a few factors: a slow **internet connection**, a **large image file size** (on the creator's end), or a full browser **cache**. Try clearing your browser cache and refreshing the page. If the issue persists across multiple series, check your network speed. If it's isolated to one chapter, the file size may be too large.</p>
                </div>
            </div>

        </div> </div> <footer>
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


            // Accordion Logic
            const accordionHeaders = document.querySelectorAll('.accordion-header');
            accordionHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const item = header.closest('.accordion-item');
                    const wasActive = item.classList.contains('active');

                    // Close all other active items
                    document.querySelectorAll('.accordion-item.active').forEach(activeItem => {
                        if (activeItem !== item) {
                            activeItem.classList.remove('active');
                        }
                    });

                    // Toggle the clicked item
                    if (!wasActive) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });
            });
        });

        function performHelpSearch() {
            const query = document.getElementById('helpSearchInput').value;
            if (query.trim()) {
                // In a real application, this would send the user to a search results page
                // within the help documentation. For now, we'll simulate a search page redirection.
                alert(`Searching Help Center for: "${query}"...`);
                // window.location.href = `help_search_results.php?q=${encodeURIComponent(query)}`;
            }
        }
    </script>
</body>
</html>
