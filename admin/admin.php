<?php
require_once __DIR__ . '/includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ComicVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        body { background-color: #151515; color: #ffffff; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; transition: 0.3s; }
        ul { list-style: none; }
        .dashboard-container { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .sidebar { background-color: #111; border-right: 1px solid #333; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; }
        .logo-container { height: 60px; display: flex; align-items: center; padding: 0 20px; background-color: #ec1d24; color: white; font-weight: 900; font-size: 22px; letter-spacing: 1px; }
        .sidebar-menu { padding: 20px 0; flex: 1; }
        .sidebar-menu li { margin-bottom: 5px; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 25px; color: #999; font-weight: 600; font-size: 14px; border-left: 4px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: #202020; color: white; border-left: 4px solid #ec1d24; }
        .sidebar-menu i { margin-right: 15px; width: 20px; text-align: center; }
        .sidebar-footer { padding: 20px; border-top: 1px solid #333; }
        .sidebar-footer a { color: #777; font-size: 12px; }
        .sidebar-footer a:hover { color: #ec1d24; }
        .main-content { background-color: #151515; padding: 0; overflow-y: auto; }
        .top-bar { height: 60px; background-color: #202020; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; position: sticky; top: 0; z-index: 100; }
        .search-box { background: #111; border: 1px solid #444; border-radius: 4px; padding: 5px 15px; display: flex; align-items: center; width: 300px; }
        .search-box input { background: transparent; border: none; color: white; width: 100%; padding: 5px; outline: none; }
        .search-box i { color: #777; }
        .admin-profile { display: flex; align-items: center; gap: 15px; }
        .admin-info { text-align: right; font-size: 12px; }
        .admin-avatar { width: 35px; height: 35px; background: #ec1d24; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .content-wrapper { padding: 30px; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .dashboard-title h2 { font-size: 24px; font-weight: 800; text-transform: uppercase; margin-bottom: 5px; }
        .dashboard-title p { color: #777; font-size: 14px; }
        .time-selector select { background: #202020; color: white; border: 1px solid #444; padding: 10px 20px; border-radius: 4px; outline: none; cursor: pointer; font-weight: 700; }
        .time-selector select:hover { border-color: #ec1d24; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #202020; padding: 25px; border-radius: 6px; border-left: 4px solid #333; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); border-left-color: #ec1d24; }
        .stat-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .stat-head h4 { color: #999; font-size: 12px; text-transform: uppercase; font-weight: 700; }
        .stat-head i { color: #ec1d24; font-size: 18px; background: rgba(236, 29, 36, 0.1); padding: 8px; border-radius: 4px; }
        .stat-value { font-size: 28px; font-weight: 800; margin-bottom: 5px; }
        .stat-delta { font-size: 12px; font-weight: 600; }
        .up { color: #00a652; }
        .down { color: #ec1d24; }
        .charts-container { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px; }
        .chart-card { background: #202020; padding: 25px; border-radius: 6px; border: 1px solid #333; }
        .chart-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .chart-header h3 { font-size: 16px; font-weight: 700; text-transform: uppercase; }
        .status-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .user-status-list { list-style: none; }
        .user-status-list li { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #333; font-size: 13px; }
        .dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .online { background-color: #00a652; }
        .offline { background-color: #555; }
        @media (max-width: 1024px) { .charts-container { grid-template-columns: 1fr; } .status-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .dashboard-container { grid-template-columns: 1fr; } .sidebar { display: none; } }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            function loadStats(){
                fetch('api/stats.php?t='+Date.now()).then(function(r){return r.json()}).then(function(d){
                    if(d && d.status==='success'){
                        var viewsEl=document.getElementById('stat-views');
                        var usersEl=document.getElementById('stat-users');
                        var uploadsEl=document.getElementById('stat-uploads');
                        var revEl=document.getElementById('stat-rev');
                        if(viewsEl) viewsEl.innerText=d.views;
                        if(usersEl) usersEl.innerText=(d.active_users||0).toString();
                        if(uploadsEl) uploadsEl.innerText=(d.chapters||0).toString();
                        if(revEl) revEl.innerText='$'+(d.earnings||0);
                        if(window.contentChart && d.distribution){
                            contentChart.data.datasets[0].data=[d.distribution.comic||0,d.distribution.manga||0,d.distribution.webtoon||0];
                            contentChart.update();
                        }
                    }
                }).catch(function(){ });
            }
            function loadTopSeries(){
                var list=document.querySelector('.user-status-list');
                if(!list) return;
                fetch('../get_stories_api.php?t='+Date.now()).then(function(r){return r.json()}).then(function(d){
                    if(!d||d.status!=='success') return;
                    var stories=(d.stories||[]).slice();
                    stories.sort(function(a,b){ return (parseInt(b.views||0)||0) - (parseInt(a.views||0)||0); });
                    var top=stories.slice(0,5);
                    var html=top.map(function(s,idx){ var rank=idx+1; var vt=new Intl.NumberFormat().format(parseInt(s.views||0)||0); return '<li><span><span style="color:#ec1d24; font-weight:bold;">#'+rank+'</span> '+(s.title||s.folder||'Unknown')+'</span><span>'+vt+' Views</span></li>'; }).join('');
                    list.innerHTML=html || '<li><span>No data</span><span>0</span></li>';
                }).catch(function(){});
            }
            loadStats();
            loadTopSeries();
            setInterval(loadStats, 10000);
            setInterval(loadTopSeries, 15000);
        });
    </script>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="logo-container">CV ADMIN</div>
        <ul class="sidebar-menu">
            <li><a href="admin.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="managemanga.php"><i class="fas fa-book-open"></i> Manage Manga</a></li>
            <li><a href="managecomic.php"><i class="fas fa-mask"></i> Manage Comics</a></li>
            <li><a href="managewebtoon.php"><i class="fas fa-scroll"></i> Manage Webtoons</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
        <div class="sidebar-footer"><a href="#"><i class="fas fa-sign-out-alt"></i> Logout System</a></div>
    </aside>
    <main class="main-content">
        <div class="top-bar">
            <div class="search-box"><i class="fas fa-search"></i><input type="text" placeholder="Search analytics, users..."></div>
            <div class="admin-profile"><div class="admin-info"><div style="font-weight:700;">Admin User</div><div style="color:#777;">Super Admin</div></div><div class="admin-avatar">AD</div></div>
        </div>
        <div class="content-wrapper">
            <div class="dashboard-header">
                <div class="dashboard-title"><h2>Analytics Overview</h2><p>Monitor performance across Manga, Comics, and Webtoons.</p></div>
                <div class="time-selector"><select id="timeframeSelect" onchange="updateDashboard(this.value)"><option value="1">Last 24 Hours</option><option value="7" selected>Last 7 Days</option><option value="30">Last 30 Days</option><option value="90">Last 90 Days</option><option value="365">Last 365 Days</option><option value="all">Lifetime</option></select></div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-head"><h4>Total Views</h4><i class="fas fa-eye"></i></div><div class="stat-value" id="stat-views">0</div><div class="stat-delta up"><i class="fas fa-arrow-up"></i> Updated</div></div>
                <div class="stat-card"><div class="stat-head"><h4>Active Users</h4><i class="fas fa-users"></i></div><div class="stat-value" id="stat-users">0</div><div class="stat-delta up"><i class="fas fa-arrow-up"></i> Updated</div></div>
                <div class="stat-card"><div class="stat-head"><h4>Content Uploads</h4><i class="fas fa-upload"></i></div><div class="stat-value" id="stat-uploads">0</div><div class="stat-delta down"><i class="fas fa-arrow-down"></i> Updated</div></div>
                <div class="stat-card"><div class="stat-head"><h4>Revenue</h4><i class="fas fa-dollar-sign"></i></div><div class="stat-value" id="stat-rev">$0</div><div class="stat-delta up"><i class="fas fa-arrow-up"></i> Updated</div></div>
            </div>
            <div class="charts-container">
                <div class="chart-card"><div class="chart-header"><h3>Traffic & Views Trend</h3><button style="background:none; border:none; color:#555; cursor:pointer;"><i class="fas fa-ellipsis-h"></i></button></div><div style="height: 300px;"><canvas id="trafficChart"></canvas></div></div>
                <div class="chart-card"><div class="chart-header"><h3>Content Distribution</h3></div><div style="height: 250px; display:flex; justify-content:center;"><canvas id="contentChart"></canvas></div><div style="margin-top:15px; text-align:center; font-size:12px; color:#888;"><span style="margin:0 5px;"><i class="fas fa-circle" style="color:#ec1d24"></i> Comics</span><span style="margin:0 5px;"><i class="fas fa-circle" style="color:#ffffff"></i> Manga</span><span style="margin:0 5px;"><i class="fas fa-circle" style="color:#555555"></i> Webtoons</span></div></div>
            </div>
            <div class="status-grid">
                <div class="chart-card"><div class="chart-header"><h3>User Activity</h3></div><div style="height: 200px;"><canvas id="onlineChart"></canvas></div></div>
                <div class="chart-card"><div class="chart-header"><h3>Top Performing Series</h3></div><ul class="user-status-list"><li><span><span style="color:#ec1d24; font-weight:bold;">#1</span> Jujutsu Kaisen</span><span>450k Views</span></li><li><span><span style="color:#ec1d24; font-weight:bold;">#2</span> Solo Leveling</span><span>380k Views</span></li><li><span><span style="color:#ec1d24; font-weight:bold;">#3</span> Spider-Man</span><span>210k Views</span></li><li><span><span style="color:#ec1d24; font-weight:bold;">#4</span> Lore Olympus</span><span>180k Views</span></li></ul></div>
                <div class="chart-card"><div class="chart-header"><h3>Server Status</h3></div><div style="text-align:center; padding:20px;"><div style="font-size:40px; color:#00a652; margin-bottom:10px;"><i class="fas fa-check-circle"></i></div><h4 style="margin-bottom:5px;">All Systems Operational</h4><p style="font-size:12px; color:#777;">Last check: 2 mins ago</p><div style="margin-top:20px; border-top:1px solid #333; padding-top:10px; display:flex; justify-content:space-between; font-size:12px;"><span>Database Latency</span><span style="color:#00a652">12ms</span></div></div></div>
            </div>
        </div>
    </main>
</div>
<script>
    Chart.defaults.color = '#888';
    Chart.defaults.borderColor = '#333';
    var trafficCtx = document.getElementById('trafficChart').getContext('2d');
    var trafficChart = new Chart(trafficCtx, { type: 'line', data: { labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], datasets: [{ label: 'Page Views', data: [1200,1900,3000,5000,2300,3400,4500], borderColor: '#ec1d24', backgroundColor: 'rgba(236, 29, 36, 0.1)', borderWidth: 2, fill: true, tension: 0.4 }, { label: 'New Users', data: [500,700,1200,1500,900,1100,1800], borderColor: '#ffffff', borderWidth: 1, borderDash: [5,5], tension: 0.4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { usePointStyle: true } } }, scales: { y: { beginAtZero: true, grid: { color: '#222' } }, x: { grid: { display: false } } } } });
    var contentCtx = document.getElementById('contentChart').getContext('2d');
    window.contentChart = new Chart(contentCtx, { type: 'doughnut', data: { labels: ['Comics','Manga','Webtoons'], datasets: [{ data: [300,500,200], backgroundColor: ['#ec1d24','#ffffff','#333333'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '70%' } });
    var onlineCtx = document.getElementById('onlineChart').getContext('2d');
    var onlineChart = new Chart(onlineCtx, { type: 'bar', data: { labels: ['Online','Offline','Away'], datasets: [{ label: 'Users', data: [1200,8000,300], backgroundColor: ['#00a652','#333333','#ffcc00'], borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { grid: { display: false } } } } });
    function updateDashboard(period){ var mult=1; if(period=='1') mult=0.1; if(period=='30') mult=4; if(period=='90') mult=12; if(period=='365') mult=50; if(period=='all') mult=100; var newData=Array.from({length:7},function(){return Math.floor(Math.random()*5000*mult)}); trafficChart.data.datasets[0].data=newData; trafficChart.update(); contentChart.data.datasets[0].data=[Math.floor(Math.random()*100),Math.floor(Math.random()*100),Math.floor(Math.random()*100)]; contentChart.update(); }
</script>
</body>
</html>
