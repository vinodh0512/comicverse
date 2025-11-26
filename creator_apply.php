<?php
require_once __DIR__ . '/includes/session.php';
require_login();
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Creator Application | ComicVerse</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif}
    body{background:#0b0f15;color:#e0e6f1;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .card{background:#141822;border:1px solid #2c3340;border-radius:12px;max-width:720px;width:100%;padding:24px;box-shadow:0 10px 20px rgba(0,0,0,.6)}
    .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
    .logo{font-weight:900;color:#ec1d24}
    .title{font-size:24px;font-weight:800}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .full{grid-column:1/-1}
    label{font-size:12px;color:#9ab4cc;margin-bottom:6px;display:block}
    input,textarea{width:100%;padding:12px;background:#1c212c;border:1px solid #2c3340;border-radius:8px;color:#e0e6f1;outline:none}
    input:focus,textarea:focus{border-color:#00bcd4;box-shadow:0 0 6px rgba(0,188,212,.5)}
    .row{display:flex;gap:10px;justify-content:flex-end;margin-top:16px}
    .btn{padding:12px 18px;border:none;border-radius:8px;cursor:pointer;font-weight:800}
    .primary{background:#ec1d24;color:#fff}
    .secondary{background:#1c212c;color:#e0e6f1;border:1px solid #2c3340}
    .msg{margin-top:10px;font-size:13px}
  </style>
</head>
<body>
  <div class="card">
    <div class="header"><div class="logo">CV</div><div class="title">Creator Application</div></div>
    <p style="color:#9ab4cc;margin-bottom:16px">Provide a few details to help us review your creator application.</p>
    <form id="applyForm" method="POST" action="auth.php">
      <input type="hidden" name="action" value="apply_creator">
      <div class="grid">
        <div class="full">
          <label>Display Name</label>
          <input type="text" name="display_name" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>
        <div>
          <label>Email</label>
          <input type="email" name="email_view" value="<?php echo htmlspecialchars($email ?: ($_SESSION['username'] ?? '') . '@reader.com'); ?>" disabled>
        </div>
        <div>
          <label>Portfolio URL</label>
          <input type="url" name="portfolio" placeholder="https://your-site.com" required>
        </div>
        <div class="full">
          <label>Genres / Categories</label>
          <input type="text" name="genres" placeholder="e.g., Action, Fantasy" required>
        </div>
        <div class="full">
          <label>Sample Links</label>
          <input type="text" name="samples" placeholder="Comma-separated links to sample chapters or images" required>
        </div>
        <div class="full">
          <label>Bio</label>
          <textarea name="bio" rows="4" placeholder="Tell us about your work and experience" required></textarea>
        </div>
        <div class="full">
          <label>Social / Contact</label>
          <input type="text" name="social" placeholder="Instagram/Twitter/Discord or contact info">
        </div>
      </div>
      <div class="row">
        <a class="btn secondary" href="profile.php">Cancel</a>
        <button class="btn primary" type="submit">Submit Application</button>
      </div>
      <div class="msg" id="msg"></div>
    </form>
  </div>
  <script>
    document.getElementById('applyForm').addEventListener('submit', function(){
      var msg=document.getElementById('msg'); if(msg){ msg.textContent='Submitting...'; msg.style.color='#9ab4cc'; }
    });
  </script>
</body>
</html>
