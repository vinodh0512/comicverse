<?php
require_once __DIR__ . '/includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | ComicVerse Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .main-content { background-color: #151515; overflow-y: auto; }
        .top-bar { height: 60px; background-color: #202020; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; position: sticky; top: 0; z-index: 100; }
        .admin-profile { display: flex; align-items: center; gap: 15px; }
        .admin-info { text-align: right; font-size: 12px; }
        .admin-avatar { width: 35px; height: 35px; background: #ec1d24; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .content-wrapper { padding: 30px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h2 { font-size: 24px; font-weight: 800; text-transform: uppercase; margin-bottom: 5px; }
        .page-title p { color: #777; font-size: 14px; }
        .controls-bar { display: flex; gap: 15px; background: #202020; padding: 15px; border-radius: 6px; border: 1px solid #333; margin-bottom: 20px; flex-wrap: wrap; }
        .search-input { flex: 1; background: #151515; border: 1px solid #444; padding: 10px 15px; color: white; border-radius: 4px; outline: none; }
        .search-input:focus { border-color: #ec1d24; }
        .filter-select { background: #151515; border: 1px solid #444; color: #ccc; padding: 0 15px; border-radius: 4px; outline: none; cursor: pointer; }
        .btn-add { background-color: #ec1d24; color: white; padding: 10px 25px; border-radius: 4px; font-weight: 700; text-transform: uppercase; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .btn-add:hover { background-color: #ff333b; }
        .table-container { background: #202020; border-radius: 6px; border: 1px solid #333; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 15px 20px; background-color: #1a1a1a; color: #888; font-weight: 700; text-transform: uppercase; font-size: 12px; border-bottom: 1px solid #333; }
        td { padding: 15px 20px; border-bottom: 1px solid #333; vertical-align: middle; }
        tr:hover { background-color: #252525; }
        tr:last-child td { border-bottom: none; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-thumb { width: 40px; height: 40px; border-radius: 50%; background: #333; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #ec1d24; border: 1px solid #444; }
        .user-name { font-weight: 700; display: block; margin-bottom: 3px; color: #fff; }
        .user-email { font-size: 12px; color: #888; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .role-admin { background: #ec1d24; color: white; }
        .role-creator { background: #0078ff; color: white; }
        .role-reader { background: #333; color: #ccc; border: 1px solid #444; }
        .plan-free { color: #888; }
        .plan-pro { color: #ffcc00; font-weight: 800; text-shadow: 0 0 10px rgba(255, 204, 0, 0.2); }
        .status-active { color: #00a652; }
        .status-banned { color: #ec1d24; }
        .status-pending { color: #ff9900; }
        .action-buttons { display: flex; gap: 10px; }
        .btn-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 4px; border: 1px solid #444; color: #ccc; cursor: pointer; transition: 0.2s; background: #151515; }
        .btn-icon:hover { border-color: #ec1d24; color: #ec1d24; }
        .btn-icon.ban:hover { border-color: #ff333b; color: #ff333b; background: rgba(255, 51, 59, 0.1); }
        .pagination { display: flex; justify-content: flex-end; padding: 20px; gap: 5px; }
        .page-btn { background: #151515; border: 1px solid #333; color: #888; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .page-btn.active, .page-btn:hover { background: #ec1d24; color: white; border-color: #ec1d24; }
        @media (max-width: 768px) { .dashboard-container { grid-template-columns: 1fr; } .sidebar { display: none; } .controls-bar { flex-direction: column; } th:nth-child(4), td:nth-child(4), th:nth-child(6), td:nth-child(6) { display: none; } }
    </style>
    <script>
        var allUsers=[]; var filteredUsers=[];
        function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
        function renderUsers(list){ var tbody=document.getElementById('usersBody'); if(!tbody) return; tbody.innerHTML=''; if(!list.length){ tbody.innerHTML='<tr><td colspan="7" style="text-align:center;color:#888;">No users found</td></tr>'; return; } list.forEach(function(u,idx){ var tr=document.createElement('tr'); var roleClass=(u.role==='admin')?'role-admin':(u.role==='creator'?'role-creator':(u.role==='creator_pending'?'role-creator':'role-reader')); var planText=u.plan==='pro'?'<span class="plan-pro"><i class="fas fa-crown"></i> PRO</span>':'<span class="plan-free">Free</span>'; var statusText=(u.status==='banned')?('<span class="status-banned"><i class="fas fa-times-circle" style="font-size:8px;margin-right:5px;"></i> Banned</span>'+(u.ban_until?'<div style="font-size:11px;color:#ff6666;">Until: '+esc(u.ban_until)+'</div>':'')+(u.ban_reason?'<div style="font-size:11px;color:#ff6666;">Reason: '+esc(u.ban_reason)+'</div>':'')):'<span class="status-active"><i class="fas fa-circle" style="font-size:8px;margin-right:5px;"></i> Active</span>'; var actions='<div class="action-buttons"><div class="btn-icon" title="Edit User"><i class="fas fa-pen"></i></div><div class="btn-icon" title="View Profile"><i class="fas fa-eye"></i></div>'+(u.status==='banned'?'<div class="btn-icon" title="Unban User" data-uid="'+(u.id||'')+'" data-email="'+(u.email||'')+'"><i class="fas fa-user-check"></i></div>':'<div class="btn-icon ban" title="Ban User" data-uid="'+(u.id||'')+'" data-email="'+(u.email||'')+'"><i class="fas fa-ban"></i></div>')+(u.role!=='creator'?'<div class="btn-icon" title="Approve Creator" data-uid="'+(u.id||'')+'" data-email="'+(u.email||'')+'"><i class="fas fa-user-check"></i></div>':'')+((u.role==='creator_pending')?'<div class="btn-icon" title="Reject Application" data-uid="'+(u.id||'')+'" data-email="'+(u.email||'')+'"><i class="fas fa-user-times"></i></div>':'')+((u.role==='creator')?'<div class="btn-icon" title="Remove From Creator" data-uid="'+(u.id||'')+'" data-email="'+(u.email||'')+'"><i class="fas fa-user-slash"></i></div>':'')+'</div>'; tr.innerHTML='<td>'+(idx+1)+'</td>'+
            '<td><div class="user-info"><div class="user-thumb">'+(u.username?u.username.substring(0,2).toUpperCase():'UU')+'</div><div><a href="#" class="user-name">'+(u.username||'')+'</a><span class="user-email">'+(u.email||'')+'</span></div></div></td>'+
            '<td><span class="badge '+roleClass+'">'+(u.role||'')+'</span></td>'+
            '<td>'+planText+'</td>'+
            '<td>'+statusText+'</td>'+
            '<td style="color:#ccc;">'+(u.joined_at||'')+'</td>'+
            '<td>'+actions+'</td>';
            tbody.appendChild(tr);
        }); }
        function applyFilters(){ var q=document.getElementById('searchInput').value.toLowerCase(); var role=document.getElementById('roleFilter').value; var plan=document.getElementById('planFilter').value; var status=document.getElementById('statusFilter').value; filteredUsers=allUsers.filter(function(u){ var ok=true; if(q){ ok=ok&&((u.username||'').toLowerCase().includes(q)||(u.email||'').toLowerCase().includes(q)); } if(role!=='all'){ ok=ok&&((u.role||'')===role); } if(plan!=='all'){ ok=ok&&((u.plan||'')===plan); } if(status!=='all'){ ok=ok&&((u.status||'')===status); } return ok; }); renderUsers(filteredUsers); }
        document.addEventListener('DOMContentLoaded', function(){ var spinner=document.getElementById('tableSpinner'); function setCounters(){ var t=document.getElementById('totalUsers'); var c=document.getElementById('totalCreators'); var p=document.getElementById('premiumUsers'); if(t) t.innerText=(allUsers||[]).length; if(c) c.innerText=(allUsers||[]).filter(function(u){return (u.role||'')==='creator'}).length; if(p) p.innerText=(allUsers||[]).filter(function(u){return (u.plan||'')==='pro'}).length; } if(spinner) spinner.style.display='block'; fetch('api/users.php?t='+Date.now()).then(function(r){return r.json()}).then(function(d){ if(spinner) spinner.style.display='none'; if(d && d.status==='success'){ allUsers=d.users||[]; filteredUsers=allUsers; renderUsers(filteredUsers); setCounters(); } }).catch(function(){ if(spinner) spinner.style.display='none'; }); document.getElementById('searchInput').addEventListener('input', applyFilters); document.getElementById('roleFilter').addEventListener('change', applyFilters); document.getElementById('planFilter').addEventListener('change', applyFilters); document.getElementById('statusFilter').addEventListener('change', applyFilters); document.body.addEventListener('click', function(e){ var abtn=e.target.closest('[title="Approve Creator"]'); if(abtn){ var uid=abtn.getAttribute('data-uid'); var email=abtn.getAttribute('data-email'); var fd=new FormData(); fd.append('user_id', uid); fd.append('email', email); fetch('api/approve_creator.php', {method:'POST', body: fd}).then(function(r){return r.json()}).then(function(j){ if(j && j.status==='success'){ fetch('api/users.php?t='+Date.now()).then(function(r){return r.json()}).then(function(d){ if(d && d.status==='success'){ allUsers=d.users||[]; filteredUsers=allUsers; renderUsers(filteredUsers); setCounters(); } }); } }); return; } var rbtn=e.target.closest('[title="Reject Application"]'); if(rbtn){ var uid=rbtn.getAttribute('data-uid'); var email=rbtn.getAttribute('data-email'); var reason=window.prompt('Reason for rejection (optional):',''); var fd2=new FormData(); fd2.append('user_id', uid); fd2.append('email', email); if(reason!==null) fd2.append('reason', reason); fetch('api/reject_creator.php', {method:'POST', body: fd2}).then(function(r){return r.json()}).then(function(j){ if(j && j.status==='success'){ fetch('api/users.php?t='+Date.now()).then(function(r){return r.json()}).then(function(d){ if(d && d.status==='success'){ allUsers=d.users||[]; filteredUsers=allUsers; renderUsers(filteredUsers); setCounters(); } }); } }); return; } var dbtn=e.target.closest('[title="Remove From Creator"]'); if(!dbtn) return; var uid=dbtn.getAttribute('data-uid'); var email=dbtn.getAttribute('data-email'); if(!window.confirm('Remove this user from Creator role?')) return; var fd3=new FormData(); fd3.append('user_id', uid); fd3.append('email', email); fetch('api/remove_creator.php', {method:'POST', body: fd3}).then(function(r){return r.json()}).then(function(j){ if(j && j.status==='success'){ fetch('api/users.php?t='+Date.now()).then(function(r){return r.json()}).then(function(d){ if(d && d.status==='success'){ allUsers=d.users||[]; filteredUsers=allUsers; renderUsers(filteredUsers); setCounters(); } }); } }); }); });
    </script>
    <script>
        document.addEventListener('click', function(e){
            var vbtn = e.target.closest('[title="View Profile"]');
            if (!vbtn) return;
            var tr = vbtn.closest('tr');
            var actionsCell = tr ? tr.children[6] : null;
            var srcBtn = actionsCell ? actionsCell.querySelector('[data-uid][data-email]') : null;
            var uid = srcBtn ? (srcBtn.getAttribute('data-uid')||'') : '';
            var email = srcBtn ? (srcBtn.getAttribute('data-email')||'') : '';
            var url = 'application_view.php?user_id=' + encodeURIComponent(uid) + '&email=' + encodeURIComponent(email);
            window.location.href = url;
        });
    </script>
    <script>
        (function(){
            var tbody=document.getElementById('usersBody');
            if(!tbody) return;
            function addViewButtons(){
                var rows=tbody.querySelectorAll('tr');
                rows.forEach(function(tr){
                    if (tr.getAttribute('data-view-bound')==='1') return;
                    tr.setAttribute('data-view-bound','1');
                    var roleCell=tr.children[2]; var actionsCell=tr.children[6];
                    if (!roleCell || !actionsCell) return;
                    if ((roleCell.textContent||'').trim()==='creator_pending'){
                        var targetBtn = actionsCell.querySelector('[title="Reject Application"],[title="Approve Creator"]');
                        var uid = targetBtn ? (targetBtn.getAttribute('data-uid')||'') : '';
                        var email = targetBtn ? (targetBtn.getAttribute('data-email')||'') : '';
                        var btn=document.createElement('div');
                        btn.className='btn-icon'; btn.title='View Application';
                        btn.innerHTML='<i class="fas fa-file-alt"></i>';
                        btn.addEventListener('click', function(){
                            var url = 'application_view.php?user_id=' + encodeURIComponent(uid) + '&email=' + encodeURIComponent(email);
                            window.location.href = url;
                        });
                        var wrap = actionsCell.querySelector('.action-buttons')||actionsCell;
                        wrap.appendChild(btn);
                    }
                });
            }
            var obs = new MutationObserver(function(){ addViewButtons(); });
            obs.observe(tbody, { childList:true });
            addViewButtons();
        })();
    </script>
    <script>
        (function(){
            var banModal, banReason, banDuration, banConfirm, banCancel; var rejModal, rejReason, rejConfirm, rejCancel; var currentUid='', currentEmail='';
            function ensureModal(){
                banModal = document.getElementById('banModal');
                banReason = document.getElementById('banReason');
                banDuration = document.getElementById('banDuration');
                banConfirm = document.getElementById('banConfirm');
                banCancel = document.getElementById('banCancel');
                rejModal = document.getElementById('rejectModal');
                rejReason = document.getElementById('rejectReason');
                rejConfirm = document.getElementById('rejectConfirm');
                rejCancel = document.getElementById('rejectCancel');
            }
            document.addEventListener('click', function(e){
                var bbtn = e.target.closest('[title="Ban User"]');
                if (bbtn){
                    e.preventDefault();
                    ensureModal();
                    currentUid = bbtn.getAttribute('data-uid')||'';
                    currentEmail = bbtn.getAttribute('data-email')||'';
                    if (banReason) banReason.value='';
                    if (banDuration) banDuration.value='60';
                    if (banModal) banModal.style.display='flex';
                    return;
                }
                var ubtn = e.target.closest('[title="Unban User"]');
                if (ubtn){
                    var uid = ubtn.getAttribute('data-uid')||'';
                    var email = ubtn.getAttribute('data-email')||'';
                    var fd = new FormData(); fd.append('user_id', uid); fd.append('email', email);
                    fetch('api/unban_user.php', {method:'POST', body: fd}).then(function(r){return r.json()}).then(function(j){
                        if (j && j.status==='success'){
                            fetch('api/users.php?t='+Date.now()).then(function(r){return r.json()}).then(function(d){ if(d && d.status==='success'){ allUsers=d.users||[]; filteredUsers=allUsers; renderUsers(filteredUsers); var t=document.getElementById('totalUsers'); var c=document.getElementById('totalCreators'); var p=document.getElementById('premiumUsers'); if(t) t.innerText=(allUsers||[]).length; if(c) c.innerText=(allUsers||[]).filter(function(u){return (u.role||'')==='creator'}).length; if(p) p.innerText=(allUsers||[]).filter(function(u){return (u.plan||'')==='pro'}).length; }});
                        }
                    });
                    return;
                }
                var rbtn = e.target.closest('[title="Reject Application"]');
                if (rbtn){
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    ensureModal();
                    currentUid = rbtn.getAttribute('data-uid')||'';
                    currentEmail = rbtn.getAttribute('data-email')||'';
                    if (rejReason) rejReason.value='';
                    if (rejModal) rejModal.style.display='flex';
                    return;
                }
            });
            document.addEventListener('click', function(e){
                ensureModal();
                var node=e.target;
                if (node===banCancel){ if (banModal) banModal.style.display='none'; }
                if (node===banConfirm){
                    if (!currentUid && !currentEmail) return;
                    var reason = banReason ? banReason.value.trim() : '';
                    var mins = parseInt((banDuration ? banDuration.value : '60'))||60;
                    var fd = new FormData(); fd.append('user_id', currentUid); fd.append('email', currentEmail); fd.append('reason', reason); fd.append('duration_minutes', String(mins));
                    fetch('api/ban_user.php', {method:'POST', body: fd}).then(function(r){return r.json()}).then(function(j){
                        if (j && j.status==='success'){
                            if (banModal) banModal.style.display='none';
                            fetch('api/users.php?t='+Date.now()).then(function(r){return r.json()}).then(function(d){ if(d && d.status==='success'){ allUsers=d.users||[]; filteredUsers=allUsers; renderUsers(filteredUsers); var t=document.getElementById('totalUsers'); var c=document.getElementById('totalCreators'); var p=document.getElementById('premiumUsers'); if(t) t.innerText=(allUsers||[]).length; if(c) c.innerText=(allUsers||[]).filter(function(u){return (u.role||'')==='creator'}).length; if(p) p.innerText=(allUsers||[]).filter(function(u){return (u.plan||'')==='pro'}).length; }});
                        }
                    });
                }
                if (node===rejCancel){ if (rejModal) rejModal.style.display='none'; }
                if (node===rejConfirm){
                    if (!currentUid && !currentEmail) return;
                    var reasonR = rejReason ? rejReason.value.trim() : '';
                    var fdR = new FormData(); fdR.append('user_id', currentUid); fdR.append('email', currentEmail); if (reasonR) fdR.append('reason', reasonR);
                    fetch('api/reject_creator.php', {method:'POST', body: fdR}).then(function(r){return r.json()}).then(function(j){
                        if (j && j.status==='success'){
                            if (rejModal) rejModal.style.display='none';
                            fetch('api/users.php?t='+Date.now()).then(function(r){return r.json()}).then(function(d){ if(d && d.status==='success'){ allUsers=d.users||[]; filteredUsers=allUsers; renderUsers(filteredUsers); var t=document.getElementById('totalUsers'); var c=document.getElementById('totalCreators'); var p=document.getElementById('premiumUsers'); if(t) t.innerText=(allUsers||[]).length; if(c) c.innerText=(allUsers||[]).filter(function(u){return (u.role||'')==='creator'}).length; if(p) p.innerText=(allUsers||[]).filter(function(u){return (u.plan||'')==='pro'}).length; }});
                        }
                    });
                }
            });
        })();
    </script>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="logo-container">CV ADMIN</div>
        <ul class="sidebar-menu">
            <li><a href="admin.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="managemanga.php"><i class="fas fa-book-open"></i> Manage Manga</a></li>
            <li><a href="managecomic.php"><i class="fas fa-mask"></i> Manage Comics</a></li>
            <li><a href="managewebtoon.php"><i class="fas fa-scroll"></i> Manage Webtoons</a></li>
            <li><a href="#" class="active"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
        <div class="sidebar-footer"><a href="#"><i class="fas fa-sign-out-alt"></i> Logout System</a></div>
    </aside>
    <main class="main-content">
        <div class="top-bar"><div style="color:#888; font-size:14px;">Admin / <span style="color:white;">User Management</span></div><div class="admin-profile"><div class="admin-info"><div style="font-weight:700;">Admin User</div><div style="color:#777;">Super Admin</div></div><div class="admin-avatar">AD</div></div></div>
        <div class="content-wrapper">
            <div class="page-header"><div class="page-title"><h2>All Users</h2><p>Manage user accounts, roles, and subscription status.</p></div><div style="display:flex; gap:20px;"><div style="text-align:right;"><div style="font-size:20px; font-weight:800;" id="totalUsers">0</div><div style="font-size:11px; color:#777; text-transform:uppercase;">Total Users</div></div><div style="text-align:right;"><div style="font-size:20px; font-weight:800; color:#00a652;" id="totalCreators">0</div><div style="font-size:11px; color:#777; text-transform:uppercase;">Creators</div></div><div style="text-align:right;"><div style="font-size:20px; font-weight:800; color:#ffcc00;" id="premiumUsers">0</div><div style="font-size:11px; color:#777; text-transform:uppercase;">Premium</div></div></div></div>
            <div class="controls-bar">
                <input type="text" class="search-input" id="searchInput" placeholder="Search by username or email...">
                <select class="filter-select" id="roleFilter"><option value="all">All Roles</option><option value="reader">Reader</option><option value="creator">Creator</option><option value="admin">Admin</option></select>
                <select class="filter-select" id="planFilter"><option value="all">All Plans</option><option value="free">Free Tier</option><option value="pro">Pro Plan</option></select>
                <select class="filter-select" id="statusFilter"><option value="all">Status</option><option value="active">Active</option><option value="banned">Banned</option></select>
                <button class="btn-add"><i class="fas fa-user-plus"></i> Add User</button>
            </div>
            <div class="table-container">
                <div id="tableSpinner" style="padding:20px; text-align:center; color:#888; display:none;"><i class="fas fa-circle-notch fa-spin"></i> Loading users...</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>User Profile</th>
                            <th>Role</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersBody"></tbody>
                </table>
                <div class="pagination"><button class="page-btn">Prev</button><button class="page-btn active">1</button><button class="page-btn">2</button><button class="page-btn">3</button><button class="page-btn">4</button><button class="page-btn">Next</button></div>
</div>
</div>
</main>
</div>
<div id="banModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.7); z-index:9999;">
  <div style="background:#202020; border:1px solid #333; border-radius:8px; width:90%; max-width:420px; padding:20px; color:#fff;">
    <div style="font-weight:800; font-size:18px; margin-bottom:10px;">Ban User</div>
    <div style="font-size:13px; color:#bbb; margin-bottom:15px;">Enter a reason and duration. The user cannot log in until the ban expires.</div>
    <label style="display:block; font-size:12px; color:#aaa; margin-bottom:6px;">Reason</label>
    <input id="banReason" type="text" style="width:100%; padding:10px; background:#151515; border:1px solid #444; border-radius:6px; color:#fff; margin-bottom:12px;" placeholder="Policy violation">
    <label style="display:block; font-size:12px; color:#aaa; margin-bottom:6px;">Duration (minutes)</label>
    <input id="banDuration" type="number" min="1" value="60" style="width:100%; padding:10px; background:#151515; border:1px solid #444; border-radius:6px; color:#fff;">
    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
      <button id="banCancel" style="background:#333; color:#fff; border:1px solid #444; padding:10px 16px; border-radius:6px; cursor:pointer;">Cancel</button>
      <button id="banConfirm" style="background:#ec1d24; color:#fff; border:1px solid #ec1d24; padding:10px 16px; border-radius:6px; cursor:pointer; font-weight:800;">Ban</button>
    </div>
  </div>
</div>
<div id="rejectModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.7); z-index:9999;">
  <div style="background:#202020; border:1px solid #333; border-radius:8px; width:90%; max-width:420px; padding:20px; color:#fff;">
    <div style="font-weight:800; font-size:18px; margin-bottom:10px;">Reject Creator Application</div>
    <div style="font-size:13px; color:#bbb; margin-bottom:15px;">Provide a reason. The application will be marked as rejected.</div>
    <label style="display:block; font-size:12px; color:#aaa; margin-bottom:6px;">Reason</label>
    <input id="rejectReason" type="text" style="width:100%; padding:10px; background:#151515; border:1px solid #444; border-radius:6px; color:#fff; margin-bottom:12px;" placeholder="Does not meet requirements">
    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
      <button id="rejectCancel" style="background:#333; color:#fff; border:1px solid #444; padding:10px 16px; border-radius:6px; cursor:pointer;">Cancel</button>
      <button id="rejectConfirm" style="background:#ec1d24; color:#fff; border:1px solid #ec1d24; padding:10px 16px; border-radius:6px; cursor:pointer; font-weight:800;">Reject</button>
    </div>
  </div>
</div>
</body>
</html>
