<?php
session_start();
include "db.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== "admin") {
    header("Location: index.php");
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

/* ===================== ADD DEVICE ===================== */
if (isset($_POST['add_device'])) {
    $alias = $_POST['alias'];
    $ip = $_POST['ip'];
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    $state = $_POST['state'];

    $stmt = $conn->prepare("INSERT INTO devices (Alias, IPAddress, latitude, longitude, State) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssdi", $alias, $ip, $lat, $lng, $state);
    $stmt->execute();

    header("Location: admindashboard.php");
    exit();
}

/* ===================== DELETE DEVICE ===================== */
if (isset($_POST['delete_device'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM devices WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admindashboard.php");
    exit();
}

/* ===================== ADD USER ===================== */
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $nip = $_POST['nip'];
    $alamat = $_POST['alamat'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, nip, alamat_sekolah, password) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $username, $nip, $alamat, $password);
    $stmt->execute();

    header("Location: admindashboard.php");
    exit();
}

/* ===================== DELETE USER ===================== */
if (isset($_POST['delete_user'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admindashboard.php");
    exit();
}

/* ===================== PAGINATION DEVICES ===================== */
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$res_total = $conn->query("SELECT COUNT(*) AS total FROM devices");
$total_data = $res_total->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

/* ===================== LOAD PAGINATED DEVICES ===================== */
$devices = [];
$res = $conn->query("SELECT * FROM devices ORDER BY id ASC LIMIT $limit OFFSET $offset");
while ($row = $res->fetch_assoc()) $devices[] = $row;

/* ===================== LOAD ALL DEVICES FOR CHART & MAP ===================== */
$all_devices = [];
$res_all = $conn->query("SELECT * FROM devices ORDER BY id ASC");
while ($row = $res_all->fetch_assoc()) $all_devices[] = $row;

/* ===================== LOAD USERS ===================== */
$users = [];
$res2 = $conn->query("SELECT * FROM users ORDER BY id ASC");
while ($row = $res2->fetch_assoc()) $users[] = $row;

/* ===================== COUNT STATUS ===================== */
$online = $offline = $inactive = 0;
foreach ($all_devices as $d) {
    $s = intval($d['State']);
    if ($s == 1) $online++;
    elseif ($s == 0) $inactive++;
    else $offline++;
}
/* ===================== LOAD LAPORAN ===================== */
$laporan = [];
$res_lap = $conn->query("
    SELECT laporan.*, users.username 
    FROM laporan 
    LEFT JOIN users ON laporan.user_id = users.id
    ORDER BY laporan.created_at DESC
");
while($row = $res_lap->fetch_assoc()) $laporan[] = $row;

/* ===================== VERIFIKASI LAPORAN ===================== */
if(isset($_GET['verifikasi_laporan'])){
    $stmt = $conn->prepare("UPDATE laporan SET status='verified' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admindashboard.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Admin</title>
<link rel="stylesheet" href="css/style-fancy.css?v=3">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container">

<nav>
    <button onclick="showSection('map-section', this)" class="active">Peta & Status</button>
    <button onclick="showSection('device', this)">Device</button>
    <button onclick="showSection('user', this)">User</button>
    <button onclick="showSection('about', this)">Hasil Laporan</button>
</nav>

<!-- ============================ MAP ============================ -->
<div id="map-section" class="section active">
    <div id="map"></div>
    <canvas id="statusChart" height="150"></canvas>

    <!-- ===================== TABEL MONITORING ALAT ===================== -->
    <h3 style="margin-top:20px;">Monitoring Alat Faceprint</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Alias</th>
            <th>IP Address</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th>Status</th>
            <th>Keterangan</th>
        </tr>

        <?php foreach ($devices as $d): ?>
        <tr>
            <td><?= $d['id'] ?></td>
            <td><?= htmlspecialchars($d['Alias']) ?></td>
            <td><?= htmlspecialchars($d['IPAddress']) ?></td>
            <td><?= $d['latitude'] ?></td>
            <td><?= $d['longitude'] ?></td>
            <td>
                <span class="tag <?= $d['State']==1?'green':($d['State']==0?'gray':'red') ?>">
                    <?= $d['State']==1?'Online':($d['State']==0?'Tidak Dipakai':'Offline') ?>
                </span>
            </td>
            <td>
                <?php if($d['State']==1): ?>
                    <span style="color:green;font-weight:bold;">Beroperasi normal</span>
                <?php elseif($d['State']==2): ?>
                    <span style="color:red;font-weight:bold;">Tidak terhubung / Offline</span>
                <?php else: ?>
                    <span style="color:#555;font-weight:bold;">Perangkat tidak digunakan</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- ===================== PAGINATION ===================== -->
    <div>
        <?php if($page > 1): ?>
            <a href="?page=<?= $page-1 ?>" class="btn">Previous</a>
        <?php endif; ?>

        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?page=<?= $i ?>" class="btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>" class="btn">Next</a>
        <?php endif; ?>
    </div>

</div>

<!-- ============================ DEVICE ============================ -->
<div id="device" class="section">
    <h3>Device Faceprint</h3>

    <form method="post" class="form-inline">
        <input name="alias" placeholder="Alias" required>
        <input name="ip" placeholder="IP Address" required>
        <input name="latitude" placeholder="Latitude" required>
        <input name="longitude" placeholder="Longitude" required>
        <select name="state">
            <option value="1">Online</option>
            <option value="0">Tidak Dipakai</option>
            <option value="2">Offline</option>
        </select>
        <button type="submit" name="add_device" class="btn primary">Tambah</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Alias</th>
            <th>IP</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>

        <?php foreach ($all_devices as $d): ?>
        <tr>
            <td><?= $d['id'] ?></td>
            <td><?= htmlspecialchars($d['Alias']) ?></td>
            <td><?= htmlspecialchars($d['IPAddress']) ?></td>
            <td><?= $d['latitude'] ?></td>
            <td><?= $d['longitude'] ?></td>
            <td>
                <span class="tag <?= $d['State']==1?'green':($d['State']==0?'gray':'red') ?>">
                    <?= $d['State']==1?'Online':($d['State']==0?'Tidak Dipakai':'Offline') ?>
                </span>
            </td>
            <td class="aksi">
                <a href="editdeviceadmin.php?id=<?= $d['id'] ?>" class="btn edit">Edit</a>

                <form method="post" class="inline-form">
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <button type="submit" name="delete_device" class="btn delete"
                        onclick="return confirm('Hapus device ini?')">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- ============================ USER ============================ -->
<div id="user" class="section">
    <h3>User</h3>

    <form method="post" class="form-inline">
        <input name="username" placeholder="Username" required>
        <input name="nip" placeholder="NIP">
        <input name="alamat" placeholder="Alamat Sekolah">
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="add_user" class="btn primary">Tambah</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>NIP</th>
            <th>Alamat Sekolah</th>
            <th>Aksi</th>
        </tr>

        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['nip']) ?></td>
            <td><?= htmlspecialchars($u['alamat_sekolah']) ?></td>
            <td class="aksi">
                <a href="edituser.php?id=<?= $u['id'] ?>" class="btn edit">Edit</a>

                <form method="post" class="inline-form">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" name="delete_user" class="btn delete"
                        onclick="return confirm('Hapus user ini?')">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- ============================ HASIL / RIWAYAT LAPORAN ============================ -->
<div id="about" class="section">
    <h3>📋 Riwayat Laporan Pengguna</h3>

    <p>
        Halaman ini digunakan oleh administrator untuk melihat
        riwayat laporan yang dikirim oleh pengguna sekolah.
    </p>

    <table>
        <tr>
            <th>Nama Pelapor</th>
            <th>Alamat Sekolah</th>
            <th>Isi Laporan</th>
            <th>Tanggal</th>
        </tr>

        <?php if(count($laporan) == 0): ?>
            <tr>
                <td colspan="4" style="text-align:center;">
                    Belum ada laporan masuk
                </td>
            </tr>
        <?php endif; ?>

        <?php foreach ($laporan as $l): ?>
        <tr>
            <td><?= htmlspecialchars($l['username']) ?></td>
            <td><?= htmlspecialchars($l['alamat_sekolah']) ?></td>
            <td><?= nl2br(htmlspecialchars($l['isi_laporan'])) ?></td>
            <td><?= date('d-m-Y H:i', strtotime($l['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- TOMBOL LOGOUT DI TENGAH -->
<form method="post" class="logout-center">
    <button type="submit" name="logout" class="logout-button">Logout</button>
</form>

</div>

<script>
function showSection(id, btn){
    document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
    document.getElementById(id).classList.add('active');

    document.querySelectorAll('nav button').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
}

// CHART
new Chart(document.getElementById('statusChart'), {
    type:'pie',
    data:{
        labels:["Online","Offline","Tidak Dipakai"],
        datasets:[{
            data:[<?= $online ?>, <?= $offline ?>, <?= $inactive ?>],
            backgroundColor:["#16a34a","#dc2626","#6b7280"]
        }]
    }
});

// MAP
const map = L.map('map').setView([-6.7,111.4], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OSM'}).addTo(map);

<?php foreach ($all_devices as $d): ?>
L.marker([<?= $d['latitude'] ?>, <?= $d['longitude'] ?>]).addTo(map)
    .bindPopup("<b><?= addslashes($d['Alias']) ?></b><br>Status: <?= $d['State'] ?>");
<?php endforeach; ?>
</script>

</body>
</html>