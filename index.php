<?php

include "db.php";

if(isset($_GET['history'])){

    header('Content-Type: application/json');

$result = $conn->query("
    SELECT url, status, http_code, response_time, checked_at
    FROM checks
    WHERE checked_at >= NOW() - INTERVAL 1 DAY
    ORDER BY id DESC
    LIMIT 50
");

    $data = [];

    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

if(isset($_GET['trending'])){

    header('Content-Type: application/json');

    $result = $conn->query("
        SELECT 
            url,
            COUNT(*) AS down_count,
            MAX(checked_at) AS last_report
        FROM checks
        WHERE status = 'DOWN'
        AND checked_at >= NOW() - INTERVAL 1 HOUR
        GROUP BY url
        ORDER BY down_count DESC
        LIMIT 10
    ");

    $data = [];

    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

if(isset($_POST['ajax'])){

    header('Content-Type: application/json');

    $url = trim($_POST['url'] ?? '');

    if(!$url){
        echo json_encode(['status'=>'ERROR']);
        exit;
    }

    if(!preg_match('/^https?:\/\//', $url)){
        $url = 'https://' . $url;
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_NOBODY => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);

    $start = microtime(true);
    curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $time = round((microtime(true) - $start) * 1000);

    curl_close($ch);

    $status = ($httpCode >= 200 && $httpCode < 400) ? "UP" : "DOWN";

    // SAVE TO DATABASE (GLOBAL HISTORY)
    $stmt = $conn->prepare("
        INSERT INTO checks (url, status, http_code, response_time)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param("ssii", $url, $status, $httpCode, $time);
    $stmt->execute();

    echo json_encode([
        'status' => $status,
        'url' => $url,
        'code' => $httpCode,
        'time' => $time
    ]);

    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>DownOrUp</title>

<style>
    .dashboard{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:20px;
    margin-top:30px;
}

/* panel card look */
.panel{
    background:#1e293b;
    border-radius:16px;
    padding:15px;
    border:1px solid #1e293b;
}

/* make scroll inside each column */
.panel .history{
    max-height:420px;
    overflow-y:auto;
}

/* 📱 MOBILE */
@media(max-width:768px){
    .dashboard{
        grid-template-columns:1fr;
    }
}
.header{
    position: sticky;
    top: 0;
    z-index: 1000;
    background:#0f172a;
    border-bottom:1px solid #1e293b;
    padding:15px 25px;

    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
}

/* Logo */
.logo{
    font-size:20px;
    font-weight:bold;
    color:#22c55e;
    text-decoration:none;
    white-space:nowrap;
}

.logo:hover{
    opacity:0.8;
}

/* Nav */
.nav{
    display:flex;
    gap:18px;
    flex-wrap:wrap;
}

.nav a{
    color:#94a3b8;
    text-decoration:none;
    font-size:14px;
    white-space:nowrap;
}

.nav a:hover{
    color:#22c55e;
}

/* 📱 MOBILE */
@media(max-width:600px){

    .header{
        flex-direction:column;
        align-items:flex-start;
    }

    .nav{
        width:100%;
        justify-content:flex-start;
        gap:12px;
        flex-wrap:wrap;
    }

    .nav a{
        font-size:13px;
    }

    .logo{
        font-size:18px;
    }
}
    .spinner{
    width:18px;
    height:18px;
    border:3px solid rgba(255,255,255,.3);
    border-top-color:white;
    border-radius:50%;
    display:none;
    animation:spin .7s linear infinite;
}

button{
    border:none;
    padding:18px 28px;
    border-radius:16px;
    background:#22c55e;
    color:white;
    cursor:pointer;
    font-weight:bold;
    font-size:15px;

    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
}

button:disabled{
    opacity:.7;
    cursor:not-allowed;
}

@keyframes spin{
    to{
        transform:rotate(360deg);
    }
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial,sans-serif;
}

body{
    background:#0f172a;
    color:white;
    min-height:100vh;
    padding:20px;
}

.container{
    max-width:700px;
    margin:auto;
    padding-top:60px;
}

h1{
    text-align:center;
    font-size:60px;
    color:#22c55e;
    margin-bottom:10px;
}

.subtitle{
    text-align:center;
    color:#94a3b8;
    margin-bottom:40px;
}

.search-box{
    display:flex;
    gap:10px;
    margin-bottom:25px;
}

input{
    flex:1;
    padding:18px;
    border:none;
    border-radius:16px;
    background:#1e293b;
    color:white;
    font-size:16px;
    outline:none;
}

button{
    border:none;
    padding:18px 28px;
    border-radius:16px;
    background:#22c55e;
    color:white;
    cursor:pointer;
    font-weight:bold;
    font-size:15px;
}

button:hover{
    opacity:.9;
}

.loading{
    display:none;
    text-align:center;
    color:#94a3b8;
    margin-bottom:20px;
}

.result{
    display:none;
    background:#1e293b;
    border-radius:24px;
    padding:35px;
    text-align:center;
    margin-bottom:35px;
}

.status{
    font-size:75px;
    font-weight:bold;
}

.up{
    color:#22c55e;
}

.down{
    color:#ef4444;
}

.details{
    margin-top:15px;
    color:#cbd5e1;
    line-height:1.8;
}

.history-title{
    margin-bottom:15px;
    font-size:22px;
}

.history{
    display:flex;
    flex-direction:column;
    gap:12px;
}

.history-item{
    background:#1e293b;
    padding:18px;
    border-radius:16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
}

.history-left{
    display:flex;
    flex-direction:column;
    gap:5px;
}

.history-url{
    font-weight:bold;
    word-break:break-all;
}

.history-time{
    color:#94a3b8;
    font-size:13px;
}

.badge{
    padding:8px 14px;
    border-radius:999px;
    font-weight:bold;
    font-size:13px;
}

.badge-up{
    background:#14532d;
    color:#4ade80;
}

.badge-down{
    background:#7f1d1d;
    color:#f87171;
}

.empty{
    color:#94a3b8;
    text-align:center;
    padding:20px;
}

@media(max-width:600px){

    h1{
        font-size:42px;
    }

    .search-box{
        flex-direction:column;
    }

    button{
        width:100%;
    }

    .status{
        font-size:55px;
    }

}
   .history {
    max-height: 320px;   /* controls visible height */
    overflow-y: auto;    /* enables vertical scroll */
    padding-right: 8px;  /* space for scrollbar */
} 
.history::-webkit-scrollbar {
    width: 6px;
}

.history::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 10px;
}

.history::-webkit-scrollbar-thumb:hover {
    background: #475569;
}
.footer{
    margin-top:50px;
    padding:25px;
    text-align:center;
    color:#94a3b8;
    border-top:1px solid #1e293b;
    font-size:14px;
}

.footer a{
    color:#22c55e;
    text-decoration:none;
    font-weight:bold;
}

.footer a:hover{
    text-decoration:underline;
}

.footer-content{
    margin-bottom:6px;
}

.footer-tagline{
    font-size:13px;
    color:#64748b;
    margin-bottom:10px;
}
</style>
</head>
<body>
    <header class="header">

    <a href="index.php" class="logo">
        DownOrUp
    </a>

    <nav class="nav">
        <a href="#history">History</a>
        <a href="#trending">Trending</a>
        <a href="https://github.com/lorencelaudenio/downorup" target="_blank">
            GitHub
        </a>
    </nav>

</header>

<div class="container">

    <h1>DownOrUp</h1>

    <div class="subtitle">
        Check if a website is online or down.
    </div>

    <div class="search-box">

        <input
            type="text"
            id="websiteInput"
            placeholder="Enter website URL..."
        >

<button id="checkBtn" onclick="checkWebsite()">
    <span id="btnText">Check</span>

    <span id="spinner" class="spinner"></span>
</button>

    </div>

    <div class="loading" id="loading">
        Checking website...
    </div>

    <div class="result" id="result">

        <div class="status" id="statusText"></div>

        <div class="details" id="details"></div>

    </div>

    <div class="dashboard">

    <div class="panel">

        <h2 class="history-title" id="history-title">
            Last 24 Hours
        </h2>

        <div class="history" id="history"></div>

    </div>

    <div class="panel">

        <h2 class="history-title" id="trending-title">
            🔥 Trending Down Websites
        </h2>

        <div class="history" id="trending"></div>

    </div>

</div>

<script>

loadHistory();

async function checkWebsite(){
    const checkBtn = document.getElementById('checkBtn');
const spinner = document.getElementById('spinner');
const btnText = document.getElementById('btnText');

checkBtn.disabled = true;
spinner.style.display = 'block';
btnText.innerHTML = 'Checking';

    const input = document.getElementById('websiteInput');
    const loading = document.getElementById('loading');
    const result = document.getElementById('result');
    const statusText = document.getElementById('statusText');
    const details = document.getElementById('details');

    let url = input.value.trim();

    if(!url){
        alert('Enter a website URL');
        return;
    }

    loading.style.display = 'block';
    result.style.display = 'none';

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('url', url);

    try{

        const response = await fetch('',{
            method:'POST',
            body:formData
        });

        const data = await response.json();
        checkBtn.disabled = false;
spinner.style.display = 'none';
btnText.innerHTML = 'Check';

        loading.style.display = 'none';
        result.style.display = 'block';

        statusText.innerHTML = data.status;
        statusText.className = 'status ' + data.status.toLowerCase();

        if(data.status === 'UP'){

            details.innerHTML = `
                Website: ${data.url}<br>
                HTTP Status: ${data.code}<br>
                Response Time: ${data.time}ms
            `;

        }else{

            details.innerHTML = `
                Website: ${data.url}<br>
                HTTP Status: ${data.code}
            `;
        }

        saveHistory(data);

    }catch(error){
        checkBtn.disabled = false;
spinner.style.display = 'none';
btnText.innerHTML = 'Check';

        loading.style.display = 'none';

        result.style.display = 'block';

        statusText.innerHTML = 'DOWN';
        statusText.className = 'status down';

        details.innerHTML = `
            Website: ${url}<br>
            Could not connect
        `;
    }
}

function saveHistory(data){

    let history = JSON.parse(localStorage.getItem('downorupHistory')) || [];

    history.unshift({
        url:data.url,
        status:data.status,
        time:new Date().toLocaleString()
    });

    history = history.slice(0,10);

    localStorage.setItem('downorupHistory', JSON.stringify(history));

    loadHistory();
}

function loadHistory(){

    fetch('?history=1')
    .then(res => res.json())
    .then(data => {

        const container = document.getElementById('history');

        if(data.length === 0){
            container.innerHTML = `<div class="empty">No history yet</div>`;
            return;
        }

        container.innerHTML = data.map(item => `

            <div class="history-item">

                <div class="history-left">

                    <div class="history-url">
                        ${item.url}
                    </div>

                    <div class="history-time">
                        ${item.checked_at}
                    </div>

                </div>

                <div class="badge ${item.status === 'UP' ? 'badge-up' : 'badge-down'}">
                    ${item.status}
                </div>

            </div>

        `).join('');

    });

}
    
function loadTrending(){

    fetch('?trending=1')
    .then(res => res.json())
    .then(data => {

        const container = document.getElementById('trending');

        if(!data || data.length === 0){
            container.innerHTML = `<div class="empty">No trending issues right now</div>`;
            return;
        }

        container.innerHTML = data.map(item => `

            <div class="history-item">

                <div class="history-left">

                    <div class="history-url">
                        ${item.url}
                    </div>

                    <div class="history-time">
                        Last seen: ${item.last_report}
                    </div>

                </div>

                <div class="badge badge-down">
                    🔥 ${item.down_count} reports
                </div>

            </div>

        `).join('');

    })
    .catch(err => {
        console.error(err);
        document.getElementById('trending').innerHTML =
            `<div class="empty">Failed to load trending data</div>`;
    });

}
    
    loadTrending();
    
    document.getElementById('websiteInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault(); // stops form reload if any
        checkWebsite();      // same function as button click
    }
});

</script>
<footer class="footer">

    <div class="footer-content">
        © <?php echo date("Y"); ?> DownOrUp. All rights reserved.
    </div>

    <div class="footer-tagline">
        Real-time Website Status Checker • Built with PHP + MySQL ❤️
    </div>

    <div class="footer-link">
        <a href="https://github.com/lorencelaudenio/downorup" target="_blank">
            View on GitHub
        </a>
    </div>

</footer>
</body>
</html>