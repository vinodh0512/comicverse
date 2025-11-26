<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Studio | Bulk Upload</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        /* --- VARIABLES --- */
        :root {
            --primary: #ec1d24;
            --bg-body: #0a0a0a;
            --bg-card: #141414;
            --bg-input: #1f1f1f;
            --border: #333;
            --text-main: #ffffff;
            --text-muted: #888;
            --success: #00a652;
        }

        /* --- BASE STYLES --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { background-color: var(--bg-body); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; background-image: radial-gradient(circle at 10% 10%, rgba(30,30,30,1) 0%, rgba(10,10,10,1) 60%); }
        a { text-decoration: none; color: white; transition: 0.3s; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 70px; background-color: rgba(20, 20, 20, 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 1000; }
        .logo { font-size: 24px; font-weight: 900; letter-spacing: -1px; color: #fff; display: flex; align-items: center; gap: 10px; }
        .logo span { background: var(--primary); padding: 2px 8px; border-radius: 4px; font-size: 18px; }
        
        /* --- LAYOUT & FORM --- */
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; width: 100%; padding-bottom: 100px; }
        .header-section { margin-bottom: 30px; }
        .upload-card { background-color: var(--bg-card); border-radius: 12px; border: 1px solid var(--border); padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px; position: relative; }
        
        label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; }
        input, select { background-color: var(--bg-input); border: 1px solid var(--border); padding: 16px; color: white; border-radius: 8px; outline: none; font-size: 14px; width: 100%; transition: 0.2s; }
        input:focus, select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(236, 29, 36, 0.15); background-color: #252525; }

        /* --- BULK DROP ZONE (The Key Component) --- */
        .bulk-drop-zone { 
            border: 3px dashed var(--primary); 
            background: linear-gradient(180deg, #1a1a1a 0%, #111 100%);
            border-radius: 12px; 
            padding: 60px; 
            text-align: center; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            box-shadow: 0 0 20px rgba(236, 29, 36, 0.3);
        }
        .bulk-drop-zone.dragover { 
            background: rgba(236, 29, 36, 0.1); 
            transform: scale(1.01);
        }
        .drop-icon-circle { width: 60px; height: 60px; background: #222; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; border: 1px solid #333; }
        .bulk-drop-zone i { font-size: 28px; color: var(--primary); }
        .bulk-drop-zone h3 { font-size: 20px; margin-bottom: 8px; color: var(--primary); text-transform: uppercase;}
        .bulk-drop-zone p { color: #ccc; font-size: 15px; }
        #folderInput { display: none; }

        /* --- PREVIEW & PROGRESS --- */
        .preview-list { margin-top: 20px; text-align: left; background: #111; padding: 15px; border-radius: 8px; }
        .preview-list p { color: var(--text-muted); font-size: 13px; }
        .progress-section { display: none; margin-top: 20px; background: #252525; padding: 15px; border-radius: 6px; border: 1px solid #333; }
        .progress-header { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; font-weight: 700; }
        .progress-track { width: 100%; height: 10px; background: #111; border-radius: 5px; overflow: hidden; }
        .progress-bar { width: 0%; height: 100%; background: var(--primary); transition: width 0.2s linear; }

        .btn-submit { 
            background-color: var(--primary); color: white; border: none; 
            padding: 16px 40px; font-weight: 800; text-transform: uppercase; 
            cursor: pointer; font-size: 16px; border-radius: 6px; transition: 0.3s; 
            width: 100%; margin-top: 40px;
        }
        .btn-submit:disabled { background-color: #444; cursor: not-allowed; }

        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo"><span>CV</span> Creator Studio</div>
    </nav>

    <div class="container">
        <div class="header-section">
            <h1>Bulk Story Uploader</h1>
            <p style="color: var(--primary);">Automate multi-chapter file system imports.</p>
        </div>
        
        <div class="upload-card">
            <form id="uploadForm">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Series Name</label>
                        <!-- This field is used to name the top-level series folder -->
                        <input type="text" id="seriesNameInput" placeholder="e.g., The Silent Ronin" required>
                    </div>
                    <div class="form-group">
                        <label>Content Type</label>
                        <select id="contentType" required>
                            <option value="comic">Comic (Western)</option>
                            <option value="manga">Manga (Japanese)</option>
                            <option value="webtoon">Webtoon (Vertical)</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Chapter Sizes (comma-separated)</label>
                        <input type="text" id="chapterSizesInput" placeholder="e.g., 74,64,57">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Series Cover (optional)</label>
                        <input type="file" id="seriesCoverInput" accept="image/*">
                        <button type="button" id="uploadSeriesCoverBtn" style="margin-top:10px; padding:10px 12px; background:#ec1d24; color:#fff; border:none; border-radius:6px; cursor:pointer;">Upload Cover</button>
                    </div>
                </div>

                <div id="seriesPicker" style="margin-top:20px; display:none;">
                    <div id="seriesGrid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:16px;"></div>
                </div>

                <div class="form-group">
                    <label>Select Root Folder (Must contain Chapter subfolders)</label>
                    <div class="bulk-drop-zone" id="bulk-drop-zone" onclick="document.getElementById('folderInput').click()">
                        <div class="drop-icon-circle"><i class="fas fa-folder-open"></i></div>
                        <h3>Upload Series Folder</h3>
                        <p>Example: Select a folder named 'Vignesh' containing 'Chapter\_1', 'Chapter\_2', etc.</p>
                        
                        <!-- The key attribute for folder upload -->
                        <input type="file" id="folderInput" webkitdirectory directory multiple accept="image/*">
                    </div>
                </div>

                <div class="preview-list" id="previewList" style="display: none;">
                    <p id="fileCountMessage">0 files loaded.</p>
                    <p style="color:#ffcc00; margin-top: 5px;">* Pages will be automatically compressed for web use.</p>
                </div>

                <div class="progress-section" id="progressSection">
                    <div class="progress-header">
                        <span id="progressStatus">Awaiting Upload...</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>
                </div>

                <button type="button" class="btn-submit" onclick="startBulkUpload()" id="submitButton" disabled>
                    <i class="fas fa-upload"></i> Process & Publish Series
                </button>
            </form>
        </div>
    </div>

    <script>
        // --- STATE & CONFIG ---
        let selectedFiles = [];
        
        // --- EVENT LISTENERS ---
        document.getElementById('folderInput').addEventListener('change', handleFolderSelection);
        loadSeriesSuggestions();
        document.getElementById('uploadSeriesCoverBtn').addEventListener('click', uploadSeriesCover);
        
        function handleFolderSelection(event) {
            selectedFiles = Array.from(event.target.files);
            const previewList = document.getElementById('previewList');
            const fileCountMessage = document.getElementById('fileCountMessage');
            const submitButton = document.getElementById('submitButton');
            
            if (selectedFiles.length > 0) {
                // 1. Determine the root folder name from the first file's path
                const firstFilePath = selectedFiles[0].webkitRelativePath;
                // Get the top-level folder name (e.g., 'vignesh')
                const rootFolderName = firstFilePath.split('/')[0];
                
                document.getElementById('seriesNameInput').value = rootFolderName;

                // 2. Validation: Check if files are inside at least one chapter subfolder
                // A valid path should look like: [rootFolder, ChapterFolder, ImageFile] -> length >= 3
                const hasSubfolders = selectedFiles.some(f => f.webkitRelativePath.split('/').length >= 3);
                
                if (hasSubfolders) {
                    previewList.style.display = 'block';
                    fileCountMessage.innerText = `${selectedFiles.length} images detected across chapters.`;
                    submitButton.disabled = false;
                } else {
                    fileCountMessage.innerText = 'Error: Files must be grouped in Chapter subfolders (e.g., /SeriesName/Chapter\_1/Image.jpg).';
                    previewList.style.display = 'block';
                    submitButton.disabled = true;
                }
                document.getElementById('bulk-drop-zone').classList.remove('dragover');
            } else {
                previewList.style.display = 'none';
                submitButton.disabled = true;
            }
        }
                async function loadSeriesSuggestions() {
            try {
                const res = await fetch('get_series.php?t=' + Date.now());
                const list = await res.json();
                if (!Array.isArray(list) || list.length === 0) return;
                document.getElementById('seriesPicker').style.display = 'block';
                const grid = document.getElementById('seriesGrid');
                grid.innerHTML = '';
                list.forEach(item => {
                    const card = document.createElement('div');
                    card.style.border = '1px solid #333';
                    card.style.borderRadius = '8px';
                    card.style.background = '#1a1a1a';
                    card.style.cursor = 'pointer';
                    card.innerHTML = `
                        <div style="width:100%; aspect-ratio:2/3; background:#111; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                            ${item.thumbnail ? `<img src="${item.thumbnail}" style="width:100%; height:100%; object-fit:cover;">` : `<span style="color:#666;">No Cover</span>`}
                        </div>
                        <div style="padding:10px; display:flex; justify-content:space-between; align-items:center;">
                            <div style="font-size:13px; font-weight:700;">${item.name}</div>
                            <div style="font-size:11px; color:#888;">${item.type}</div>
                        </div>
                    `;
                    card.onclick = () => {
                        document.getElementById('seriesNameInput').value = item.value;
                        document.getElementById('contentType').value = item.type;
                    };
                    grid.appendChild(card);
                });
            } catch (e) {}
        }
        // Drag Over Handling
        document.getElementById('bulk-drop-zone').addEventListener('dragover', (e) => { e.preventDefault(); e.currentTarget.classList.add('dragover'); });
        document.getElementById('bulk-drop-zone').addEventListener('dragleave', (e) => { e.preventDefault(); e.currentTarget.classList.remove('dragover'); });
        
        // --- BULK UPLOAD LOGIC ---

        async function startBulkUpload() {
            if (selectedFiles.length === 0 || !document.getElementById('seriesNameInput').value) {
                alert("Please select a folder and ensure the series name is filled.");
                return;
            }

            const seriesName = document.getElementById('seriesNameInput').value;
            const contentType = document.getElementById('contentType').value;

            const submitButton = document.getElementById('submitButton');
            const progressSection = document.getElementById('progressSection');
            const progressBar = document.getElementById('progressBar');
            const progressStatus = document.getElementById('progressStatus');

            submitButton.disabled = true;
            progressSection.style.display = 'block';

            try {
                // 1) Stage files into temp_uploads via sequential uploader (with limited concurrency)
                const total = selectedFiles.length;
                let uploaded = 0;
                const concurrency = 4; // tune based on server capacity

                progressStatus.innerText = `Uploading files: 0/${total}`;
                progressBar.style.width = '0%';

                const queue = selectedFiles.slice();
                async function worker() {
                    while (queue.length > 0) {
                        const file = queue.shift();
                        const fd = new FormData();
                        fd.append('file', file);
                        fd.append('relativePath', file.webkitRelativePath);
                        const res = await fetch('sequential_uploader.php', { method: 'POST', body: fd });
                        const json = await res.json();
                        if (json.status !== 'success') throw new Error(json.message || 'Staging failed');
                        uploaded++;
                        const pct = Math.round((uploaded / total) * 100);
                        progressBar.style.width = pct + '%';
                        progressStatus.innerText = `Uploading files: ${uploaded}/${total}`;
                    }
                }

                const workers = Array.from({ length: Math.min(concurrency, total) }, () => worker());
                 await Promise.all(workers);
                    async function uploadSeriesCover() {
                    const seriesName = document.getElementById('seriesNameInput').value.trim();
                    const contentType = document.getElementById('contentType').value;
                    const fileInput = document.getElementById('seriesCoverInput');
                    if (!seriesName || !fileInput.files || fileInput.files.length === 0) { return; }
                    const fd = new FormData();
                    fd.append('seriesName', seriesName);
                    fd.append('type', contentType);
                    fd.append('cover', fileInput.files[0]);
                    const res = await fetch('creator/series_cover.php', { method: 'POST', body: fd });
                    const json = await res.json();
                    if (json.status === 'success') {
                        alert('Series cover uploaded');
                        loadSeriesSuggestions();
                    } else {
                        alert('Cover upload failed: ' + (json.message || 'Error'));
                    }
                }
                // 2) Final processing: move staged files to Book_data and generate metadata
                progressStatus.innerText = 'Processing chapters...';
                const finalFD = new FormData();
                finalFD.append('seriesName', seriesName);
                finalFD.append('type', contentType);
                finalFD.append('compress', 'on');
                const sizes = (document.getElementById('chapterSizesInput').value || '').trim();
                if (sizes) finalFD.append('chapterSizes', sizes);

                const response = await fetch('bulk_uploader.php', { method: 'POST', body: finalFD });
                const finalResult = await response.json();

                if (finalResult.status === 'success') {
                    progressBar.style.width = '100%';
                    progressBar.style.backgroundColor = 'var(--success)';
                    progressStatus.innerText = `Success! ${finalResult.chapters.length} chapters published.`;
                    alert(`Upload Complete: ${finalResult.message}`);
                    window.location.reload();
                } else {
                    throw new Error(finalResult.message || 'Bulk processing failed');
                }

            } catch (error) {
                console.error(error);
                progressStatus.innerText = 'ERROR: ' + (error.message || 'Unknown Server Error.');
                progressBar.style.backgroundColor = 'red';
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-upload"></i> Process & Publish Series';
            }
        }
    </script>
</body>
</html>
