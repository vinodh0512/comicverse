<?php
// upload_form.php (Unified Content Uploader)
session_start();

// --- STRICT CREATOR ACCESS CONTROL ---
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'creator') {
    header("Location: ../login.php?role=creator");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Studio | Unified Upload</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        /* CSS styles preserved */
        :root {
            --primary: #ec1d24;
            --primary-dark: #b91218;
            --primary-glow: rgba(236, 29, 36, 0.5);
            --bg-body: #0a0a0a;
            --bg-card: #141414;
            --bg-input: #1f1f1f;
            --border: #333;
            --text-main: #ffffff;
            --text-muted: #888;
            --success: #00a652;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; background-image: radial-gradient(circle at 10% 10%, rgba(30,30,30,1) 0%, rgba(10,10,10,1) 60%); }
        a { text-decoration: none; color: white; transition: 0.3s; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 70px; background-color: rgba(20, 20, 20, 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 1000; }
        .logo { font-size: 24px; font-weight: 900; letter-spacing: -1px; color: #fff; display: flex; align-items: center; gap: 10px; }
        .logo span { background: var(--primary); padding: 2px 8px; border-radius: 4px; font-size: 18px; }
        .nav-links { display: flex; gap: 40px; }
        .nav-links a { font-size: 14px; font-weight: 600; color: var(--text-muted); padding: 24px 0; border-bottom: 2px solid transparent; }
        .nav-links a.active, .nav-links a:hover { color: white; border-bottom-color: var(--primary); }
        .user-pill { background: var(--bg-input); padding: 8px 16px; border-radius: 50px; border: 1px solid var(--border); display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; }
        .logout-btn { color: var(--text-muted); cursor: pointer; transition: 0.2s; }
        .logout-btn:hover { color: var(--primary); }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; width: 100%; padding-bottom: 100px; }
        .header-section { margin-bottom: 30px; }
        .header-section h1 { font-size: 28px; font-weight: 800; margin-bottom: 5px; }
        .header-section p { color: var(--text-muted); font-size: 14px; }
        .upload-card { background-color: var(--bg-card); border-radius: 12px; border: 1px solid var(--border); padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px; position: relative; }
        label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; }
        input, select, textarea { background-color: var(--bg-input); border: 1px solid var(--border); padding: 16px; color: white; border-radius: 8px; outline: none; font-size: 14px; width: 100%; transition: 0.2s; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(236, 29, 36, 0.15); background-color: #252525; }
        .single-url-input { min-height: 50px; } 
        #newSeriesContainer { margin-top: 15px; display: none; animation: slideDown 0.3s ease; }
        .thumb-upload-wrapper { display: flex; gap: 25px; align-items: center; padding: 20px; background: #1a1a1a; border-radius: 10px; border: 1px dashed var(--border); }
        .thumb-zone { width: 120px; height: 180px; background-color: #222; border-radius: 6px; cursor: pointer; overflow: hidden; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: 0.3s; border: 1px solid #333; }
        .thumb-zone:hover { border-color: var(--primary); box-shadow: 0 0 15px rgba(236, 29, 36, 0.2); }
        .thumb-zone img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .thumb-placeholder { text-align: center; color: #555; }
        .thumb-placeholder i { font-size: 24px; margin-bottom: 5px; }
        .thumb-placeholder span { font-size: 10px; font-weight: 700; text-transform: uppercase; }
        #thumbInput { display: none; }
        .thumb-details h4 { font-size: 16px; font-weight: 600; margin-bottom: 5px; color: #fff; }
        .thumb-details p { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
        .sticky-progress { position: fixed; bottom: 0; left: 0; width: 100%; background: #1a1a1a; border-top: 1px solid var(--primary); padding: 15px 40px; z-index: 2000; display: none; align-items: center; justify-content: space-between; box-shadow: 0 -10px 30px rgba(0,0,0,0.5); }
        .progress-info { display: flex; align-items: center; gap: 20px; flex: 1; }
        .progress-meta h4 { font-size: 14px; margin-bottom: 2px; color: #fff; }
        .progress-meta span { font-size: 12px; color: var(--text-muted); }
        .progress-bar-wrapper { flex: 2; height: 8px; background: #333; border-radius: 4px; overflow: hidden; margin: 0 40px; }
        .progress-fill { height: 100%; width: 0%; background: var(--primary); transition: width 0.3s ease; box-shadow: 0 0 10px var(--primary); }
        .btn-submit { background-color: var(--primary); color: white; border: none; padding: 16px 40px; font-weight: 800; text-transform: uppercase; cursor: pointer; font-size: 16px; border-radius: 6px; transition: 0.3s; width: 100%; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-submit:hover { background-color: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 5px 20px var(--primary-glow); }
        .btn-submit:disabled { background-color: #444; cursor: not-allowed; transform: none; box-shadow: none; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) { .navbar { padding: 0 20px; } .nav-links { display: none; } .form-grid { grid-template-columns: 1fr; } .thumb-upload-wrapper { flex-direction: column; text-align: center; } .sticky-progress { padding: 10px 20px; flex-direction: column; gap: 10px; align-items: stretch; } .progress-bar-wrapper { margin: 10px 0; } }
        
        /* New Styles for toggling input fields */
        #pageArchiveInput, #pageUrlsInput { display: none; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo"><span>CV</span></div> 
        <div class="nav-links">
            <a href="mystories.php">Dashboard</a>
            <a href="#" class="active">Upload</a>
        </div>
        <div class="user-pill">
            <i class="fas fa-user-circle"></i>
            <a href="../profile.php" style="color:white; text-decoration:none; font-weight:600;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Creator'); ?></a>
            <a href="../auth.php?action=logout_creator" class="logout-btn" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="container">
        <div class="header-section">
            <h1 id="mainTitle">Upload New Content</h1>
            <p id="mainSubtitle">Publish a new comic, manga chapter, or video title to the platform.</p>
        </div>
        
        <div class="upload-card">
            <form id="uploadForm">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Collection / Series / Studio Name</label>
                        <select id="seriesSelect" required>
                            <option value="" disabled selected>Loading...</option>
                        </select>
                        <div id="newSeriesContainer">
                            <input type="text" id="newSeriesName" placeholder="Enter New Collection/Series Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Content Type</label>
                        <select id="contentType" required>
                            <option value="manga">Manga (Japanese)</option>
                            <option value="comic">Comic (Western)</option>
                            <option value="webtoon">Webtoon (Vertical)</option>
                            <option value="movie">Movie/Video</option>
                            <option value="series">Series Episode</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid" id="contentFields">
                    <div class="form-group">
                        <label id="titleLabel">Chapter / Movie Title</label>
                        <input type="text" id="chapterTitle" placeholder="e.g. The Battle Begins" required>
                    </div>
                    <div class="form-group">
                        <label id="numberLabel">Chapter Number / Release Year</label>
                        <input type="number" id="chapterNum" placeholder="e.g. 105 or 2025">
                    </div>
                </div>

                <div class="form-group">
                    <label id="coverLabel">Cover Art (Local File Upload)</label>
                    <div class="thumb-upload-wrapper">
                        <div class="thumb-zone" id="thumbZone" onclick="document.getElementById('thumbInput').click()">
                            <div class="thumb-placeholder" id="thumbPlaceholder">
                                <i class="fas fa-plus"></i>
                                <span>Upload Poster</span>
                            </div>
                            <img id="thumbPreviewImg" src="" alt="Cover Preview">
                            <input type="file" id="thumbInput" accept="image/*" required>
                        </div>
                        <div class="thumb-details">
                            <h4 id="thumbTitle">Content Poster / Thumbnail</h4>
                            <p>Select the poster image from your computer.<br>Recommended: Aspect ratio based on content type.</p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Release Schedule</label>
                    <select id="releaseType" required>
                        <option value="immediate">Publish Immediately</option>
                        <option value="scheduled">Schedule for Later</option>
                    </select>
                    <div id="scheduleOptions" style="display: none; margin-top: 15px;">
                        <div class="form-grid" style="margin-bottom: 0;">
                            <input type="date" id="scheduleDate">
                            <input type="time" id="scheduleTime">
                        </div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 40px;">
                    <label id="sourceLabel">Source URL (Changes based on Content Type)</label>
                    
                    <input type="url" id="pageArchiveInput" class="single-url-input" placeholder="e.g. https://stream.host.com/video_file.mp4">
                    
                    <textarea id="pageUrlsInput" placeholder="Paste one image URL per line, in order."></textarea>
                </div>

                <button type="button" class="btn-submit" onclick="startUpload()" style="margin-top: 40px;">
                    Publish Content
                </button>
            </form>
        </div>
    </div>

    <div class="sticky-progress" id="progressSection">
        <div class="progress-info">
            <i class="fas fa-circle-notch fa-spin" style="color:var(--primary); font-size: 20px;"></i>
            <div class="progress-meta">
                <h4 id="progressStatus">Ready to Submit</h4>
                <span id="progressPercent">0%</span>
            </div>
        </div>
        <div class="progress-bar-wrapper">
            <div class="progress-fill" id="progressBar"></div>
        </div>
    </div>

<script>
    const STATE = { coverFile: null };

    document.addEventListener('DOMContentLoaded', () => {
        loadSeriesList();
        setupInputs();
        // Set initial state on load (usually Manga/Comic mode)
        updateSourceInputs(document.getElementById('contentType').value);
    });
    
    function updateSourceInputs(contentType) {
        const isVideo = contentType === 'movie' || contentType === 'series';
        
        document.getElementById('pageArchiveInput').style.display = isVideo ? 'block' : 'none';
        document.getElementById('pageUrlsInput').style.display = isVideo ? 'none' : 'block';
        
        // Update labels and titles
        document.getElementById('mainTitle').innerText = `Upload New ${isVideo ? 'Movie/Series' : 'Comic/Manga'}`;
        document.getElementById('mainSubtitle').innerText = `Publish a new ${contentType} to the platform.`;
        document.getElementById('titleLabel').innerText = isVideo ? 'Movie/Episode Title' : 'Chapter Title';
        document.getElementById('numberLabel').innerText = isVideo ? 'Release Year / ID' : 'Chapter Number';
        
        if (isVideo) {
            document.getElementById('sourceLabel').innerText = 'Video Source URL (MP4, HLS, or Streaming Link)';
            document.getElementById('pageArchiveInput').required = true;
            document.getElementById('pageUrlsInput').required = false;
        } else {
            document.getElementById('sourceLabel').innerText = 'Page URLs (One per line, in order)';
            document.getElementById('pageArchiveInput').required = false;
            document.getElementById('pageUrlsInput').required = true;
        }
    }

    async function loadSeriesList() {
        const select = document.getElementById('seriesSelect');
        try {
            const res = await fetch('get_studios.php?t=' + Date.now());
            const list = await res.json();
            
            let html = '<option value="" disabled selected>Select Collection/Series/Studio...</option>';
            if (Array.isArray(list) && list.length > 0) {
                list.forEach(s => {
                    html += `<option value="${s.value}" data-type="${s.type || 'manga'}">${s.name}</option>`;
                });
            }
            html += `<option value="new" style="color:#ec1d24; font-weight:bold;">+ Create New Collection/Series</option>`;
            select.innerHTML = html;
            
            if (list.length === 0) {
                 document.getElementById('newSeriesContainer').style.display = 'block';
                 document.getElementById('newSeriesName').required = true;
            }
        } catch (err) { 
            console.error("Collection loader error:", err);
            select.innerHTML = '<option value="new" style="color:#ec1d24; font-weight:bold;" selected>+ Create New Collection/Series</option>';
            document.getElementById('newSeriesContainer').style.display = 'block';
            document.getElementById('newSeriesName').required = true;
        }
    }

    function setupInputs() {
        // Source input toggling based on content type
        document.getElementById('contentType').addEventListener('change', function() {
            updateSourceInputs(this.value);
        });

        // Series selection toggle for new name input
        document.getElementById('seriesSelect').addEventListener('change', function() {
            const val = this.value;
            const newContainer = document.getElementById('newSeriesContainer');
            const newSeriesNameInput = document.getElementById('newSeriesName');
            
            if (val === 'new') {
                newContainer.style.display = 'block';
                newSeriesNameInput.required = true;
            } else {
                newContainer.style.display = 'none';
                newSeriesNameInput.required = false;
                
                const selectedOption = this.options[this.selectedIndex];
                const type = selectedOption.getAttribute('data-type');
                if (type) {
                    document.getElementById('contentType').value = type;
                    updateSourceInputs(type);
                }
            }
        });

        document.getElementById('releaseType').addEventListener('change', function() {
            document.getElementById('scheduleOptions').style.display = (this.value === 'scheduled') ? 'block' : 'none';
        });

        document.getElementById('thumbInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                STATE.coverFile = file;
                const img = document.getElementById('thumbPreviewImg');
                img.src = URL.createObjectURL(file);
                img.style.display = 'block';
                document.getElementById('thumbPlaceholder').style.display = 'none';
                document.querySelector('.thumb-zone').style.borderStyle = 'solid';
            }
        });
    }

    async function startUpload() {
        // --- 1. Validation & Data Collection ---
        const contentType = document.getElementById('contentType').value;
        const movieTitle = document.getElementById('chapterTitle').value.trim();
        const isVideo = contentType === 'movie' || contentType === 'series';

        let contentSource;
        if (isVideo) {
            contentSource = document.getElementById('pageArchiveInput').value.trim();
            if (!contentSource || !(contentSource.startsWith('http://') || contentSource.startsWith('https://'))) {
                alert('Please enter a valid Video Source URL.'); return;
            }
        } else {
            contentSource = document.getElementById('pageUrlsInput').value.trim();
            // Validate image URLs (must be multiple lines or non-empty)
            const pages = contentSource.split('\n').filter(url => url.trim().length > 0);
            if (pages.length === 0) {
                alert('Please paste at least one valid image URL for the pages.'); return;
            }
            // Re-stringify for POST to backend if it's the image list
            contentSource = JSON.stringify(pages);
        }
        
        // Determine series name
        const seriesSelect = document.getElementById('seriesSelect');
        const seriesVal = seriesSelect.value;
        let seriesName;
        if (seriesVal === 'new') {
             seriesName = document.getElementById('newSeriesName').value.trim();
             if (!seriesName) { alert('Please enter a name for the new Collection/Studio.'); return; }
        } else if (seriesSelect.selectedIndex > 0) {
             seriesName = seriesSelect.options[seriesSelect.selectedIndex].text;
        } else {
             alert('Please select or create a Collection/Studio.'); return;
        }
        
        if (!STATE.coverFile) { alert('Please select a local file for the Cover Art.'); return; }
        if (!movieTitle) { alert('Please enter the Content Title.'); return; }

        const btn = document.querySelector('.btn-submit');
        btn.disabled = true;
        
        // --- 2. Prepare Data ---
        const baseData = {
            series: seriesName,
            type: contentType,
            chapterNum: document.getElementById('chapterNum').value || (isVideo ? '0' : '1'),
            chapterTitle: movieTitle, 
            releaseType: document.getElementById('releaseType').value,
            scheduleDate: document.getElementById('scheduleDate').value,
            scheduleTime: document.getElementById('scheduleTime').value,
            // Send source based on type
            pageArchiveUrl: isVideo ? contentSource : '',
            pageUrls: isVideo ? '' : contentSource // JSON string of image URLs
        };

        // --- 3. Submit (Reuse single submit_archive action in backend) ---
        try {
            let finFD = new FormData();
            finFD.append('action', 'submit_archive'); 
            Object.keys(baseData).forEach(k => finFD.append(k, baseData[k]));
            finFD.append('coverFile', STATE.coverFile);
            
            const finReq = await fetch('uploader.php', { method: 'POST', body: finFD });
            const finRes = await safeJson(finReq);
            
            if(finRes.status !== 'success') throw new Error(finRes.message);

            alert("Upload Successfully Completed!");
            window.location.reload();

        } catch (err) {
            console.error(err);
            alert("Error: " + (err.message || 'Unknown network error.'));
            btn.disabled = false;
        }
    }

    async function safeJson(res) {
        const text = await res.text();
        const start = text.indexOf('{');
        const end = text.lastIndexOf('}');
        if (start !== -1 && end !== -1 && end > start) {
            try { return JSON.parse(text.substring(start, end+1)); } catch {}
        }
        throw new Error(text || 'Invalid server response');
    }
</script>
</body>
</html>