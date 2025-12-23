<?php
session_start();
include "db.php";

// --- Cek login user ---
if(!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'user' || !isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ================== PROSES SIMPAN LAPORAN ==================
$pesan_laporan = "";
$pesan_class = "";
if(isset($_POST['kirim_laporan'])){
    $nama   = $user['username'];
    $alamat = $user['alamat_sekolah'];
    $isi    = trim($_POST['isi_laporan']);

    if($isi != ""){
        $stmt = $conn->prepare("
            INSERT INTO laporan (user_id, nama_pelapor, alamat_sekolah, isi_laporan)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $user_id, $nama, $alamat, $isi);
        $stmt->execute();

        $pesan_laporan = "✅ Laporan berhasil dikirim!";
        $pesan_class = "success";
    } else {
        $pesan_laporan = "❌ Isi laporan tidak boleh kosong!";
        $pesan_class = "error";
    }
}

// Ambil data devices
$devices = [];
$res = $conn->query("SELECT * FROM devices ORDER BY id ASC");
while($row = $res->fetch_assoc()) $devices[] = $row;

// Hitung status device
$online=$offline=$inactive=0;
foreach($devices as $d){
    $s=intval($d['State']??2);
    if($s===1) $online++;
    elseif($s===0) $inactive++;
    else $offline++;
}

// Proses logout
if(isset($_POST['logout'])){
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard</title>

<!-- CSS -->
<link rel="stylesheet" href="css/userdashboard.css">

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Marker Cluster -->
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2>User Panel</h2>
        <p><?= htmlspecialchars($user['username']) ?></p>
    </div>
    
    <nav>
        <button onclick="showSection('profile', this)" class="active">
            <span>👤 Profil</span>
        </button>
        <button onclick="showSection('map', this)">
            <span>🗺️ Peta & Status</span>
        </button>
    </nav>
    
    <form method="post" class="sidebar-logout">
        <button type="submit" name="logout">🚪 Logout</button>
    </form>
</div>

<!-- MAIN CONTAINER -->
<div class="container">

    <!-- ============== PROFIL SECTION ============== -->
    <div id="profile" class="section active">
        <div class="content-header">
            <h1>Profil Pengguna</h1>
        </div>

        <!-- Data Profil -->
        <div class="card">
            <p>
                <span class="user-info">Username:</span>
                <span class="user-value"><?= htmlspecialchars($user['username'] ?? '-') ?></span>
            </p>
            <p>
                <span class="user-info">NIP:</span>
                <span class="user-value"><?= htmlspecialchars($user['nip'] ?? '-') ?></span>
            </p>
            <p>
                <span class="user-info">Alamat Sekolah:</span>
                <span class="user-value"><?= htmlspecialchars($user['alamat_sekolah'] ?? '-') ?></span>
            </p>
        </div>

        <h2 class="section-title">📝 Form Laporan</h2>

        <!-- Pesan Laporan -->
        <?php if($pesan_laporan): ?>
            <div class="pesan-laporan <?= $pesan_class ?>">
                <?= $pesan_laporan ?>
            </div>
        <?php endif; ?>

        <!-- Form Laporan -->
        <div class="laporan-card">
            <form method="post">
                <p>
                    <strong>Nama Pelapor:</strong>
                    <input type="text"
                           value="<?= htmlspecialchars($user['username']) ?>"
                           readonly>
                </p>

                <p>
                    <strong>Alamat Sekolah:</strong>
                    <input type="text"
                           value="<?= htmlspecialchars($user['alamat_sekolah']) ?>"
                           readonly>
                </p>

                <p>
                    <strong>Isi Laporan:</strong>
                    <textarea name="isi_laporan"
                              rows="6"
                              required
                              placeholder="Tuliskan laporan Anda di sini..."></textarea>
                </p>

                <button type="submit" name="kirim_laporan" class="btn-laporan">
                    📤 Kirim Laporan
                </button>
            </form>
        </div>
    </div>

    <!-- ============== PETA & STATUS SECTION ============== -->
    <div id="map" class="section">
        <div class="content-header">
            <h1>Peta & Status Monitoring</h1>
        </div>
        
        <canvas id="statusChart"></canvas>
        <div id="mapid"></div>
    </div>

</div>

<script>
function showSection(id, btn){
    // Hide all sections
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    
    // Show selected section
    document.getElementById(id).classList.add('active');

    // Update active button
    document.querySelectorAll('nav button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Resize map when map section is shown
    if(id === 'map'){
        setTimeout(() => {
            if(typeof map !== 'undefined') {
                map.invalidateSize();
            }
        }, 300);
    }
}

// Chart status
const statusData = {
    labels:["Online","Offline","Tidak Dipakai"],
    datasets:[{
        data:[<?= $online ?>,<?= $offline ?>,<?= $inactive ?>],
        backgroundColor:["#22c55e","#ef4444","#9ca3af"]
    }]
};

const chartConfig = { 
    type:'pie', 
    data: statusData, 
    options:{
        responsive: true,
        maintainAspectRatio: true,
        plugins:{
            legend:{
                position:'bottom',
                labels: {
                    font: {
                        size: 14,
                        weight: 'bold'
                    },
                    padding: 15
                }
            }
        }
    } 
};

new Chart(document.getElementById('statusChart'), chartConfig);

// Map device
const map = L.map('mapid').setView([-6.7,111.4],12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© OpenStreetMap contributors',
    maxZoom: 18
}).addTo(map);

// Marker Cluster
const markers = L.markerClusterGroup({
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true
});

<?php foreach($devices as $d):
$lat=floatval($d['latitude']??0);
$lng=floatval($d['longitude']??0);
$alias=addslashes($d['Alias']);
$ip=$d['IPAddress']??'-';
$state=intval($d['State']??2);
$color=$state==1?'green':($state==0?'gray':'red');
$label=$state==1?'Online':($state==0?'Tidak Dipakai':'Offline');
?>
markers.addLayer(L.marker([<?= $lat ?>,<?= $lng ?>],{
    icon:L.divIcon({
        className:'hollow-marker',
        html:`<div style="width:24px;height:24px;border-radius:50%;border:4px solid <?= $color ?>;background:white;box-shadow:0 2px 8px rgba(0,0,0,0.3);"></div>`,
        iconSize:[24,24],
        iconAnchor:[12,12]
    })
}).bindPopup(`<div style="padding:5px;"><strong style="font-size:16px;"><?= $alias ?></strong><br><span style="color:<?= $color ?>;font-weight:bold;">Status: <?= $label ?></span><br>IP: <?= $ip ?></div>`));
<?php endforeach; ?>

map.addLayer(markers);

// Resize map on window resize
window.addEventListener('resize', () => {
    if(typeof map !== 'undefined') {
        map.invalidateSize();
    }
});
</script>
</body>
</html>