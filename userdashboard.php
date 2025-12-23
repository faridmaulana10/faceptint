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

        $pesan_laporan = "<p style='color:green;'>Laporan berhasil dikirim.</p>";
    } else {
        $pesan_laporan = "<p style='color:red;'>Isi laporan tidak boleh kosong.</p>";
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
<div class="container">
<nav>
<button onclick="showSection('profile')" class="active">Profil</button>
    <button onclick="showSection('map')">Peta & Status</button>
</nav>

<!-- Profil -->
<div id="profile" class="section active">
    <h2>Profil User</h2>

    <!-- Data Profil -->
    <div class="card">
        <p><strong class="user-info">Username:</strong>
            <span class="user-value"><?= htmlspecialchars($user['username'] ?? '-') ?></span>
        </p>
        <p><strong class="user-info">NIP:</strong>
            <span class="user-value"><?= htmlspecialchars($user['nip'] ?? '-') ?></span>
        </p>
        <p><strong class="user-info">Alamat Sekolah:</strong>
            <span class="user-value"><?= htmlspecialchars($user['alamat_sekolah'] ?? '-') ?></span>
        </p>
    </div>

    <!-- Form Laporan -->
    <h3 style="margin-top:20px;">Laporan Pengguna</h3>

    <?= $pesan_laporan ?>

    <form method="post" class="card">
        <p>
            <strong>Nama Pelapor:</strong><br>
            <input type="text"
                   value="<?= htmlspecialchars($user['username']) ?>"
                   readonly>
        </p>

        <p>
            <strong>Alamat Sekolah:</strong><br>
            <input type="text"
                   value="<?= htmlspecialchars($user['alamat_sekolah']) ?>"
                   readonly>
        </p>

        <p>
            <strong>Isi Laporan:</strong><br>
            <textarea name="isi_laporan"
                      rows="5"
                      required
                      placeholder="Tuliskan laporan Anda..."></textarea>
        </p>

        <button type="submit" name="kirim_laporan" class="logout-button">
            Kirim Laporan
        </button>
    </form>
</div>

<!-- Peta & Status -->
<div id="map" class="section">
    <h2>Status Alat Faceprint</h2>
    <canvas id="statusChart"></canvas>
    <div id="mapid"></div>
</div>

<!-- Logout -->
<div class="logout-wrapper">
    <form method="post">
        <button type="submit" name="logout" class="logout-button">Logout</button>
    </form>
</div>
</div>

<script>
function showSection(id){
    document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelectorAll('nav button').forEach(b=>b.classList.remove('active'));
    document.querySelector(`nav button[onclick="showSection('${id}')"]`).classList.add('active');

    if(id === 'map'){
        setTimeout(()=> map.invalidateSize(), 200);
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
new Chart(document.getElementById('statusChart'), { type:'pie', data:statusData, options:{plugins:{legend:{position:'bottom'}}} });

// Map device
const map = L.map('mapid').setView([-6.7,111.4],12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© OpenStreetMap contributors'
}).addTo(map);

// Marker Cluster
const markers = L.markerClusterGroup();

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
        html:`<div style="width:22px;height:22px;border-radius:50%;border:4px solid <?= $color ?>;background:white;"></div>`,
        iconSize:[22,22],
        iconAnchor:[11,11]
    })
}).bindPopup(`<strong><?= $alias ?></strong><br>Status: <span style="color:<?= $color ?>"><?= $label ?></span><br>IP: <?= $ip ?>`));
<?php endforeach; ?>

map.addLayer(markers);

// Resize map on window resize
window.addEventListener('resize', ()=>map.invalidateSize());
</script>
</body>
</html>
