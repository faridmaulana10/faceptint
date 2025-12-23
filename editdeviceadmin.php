<?php
session_start();
include "db.php";

// --- Proteksi Halaman ---
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== "admin") {
    header("Location: index.php");
    exit();
}

// --- Ambil ID Device ---
$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    die("ID device tidak valid!");
}

// --- Ambil Data Lama ---
$stmt = $conn->prepare("SELECT * FROM devices WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();

if (!$device) {
    die("Device tidak ditemukan!");
}

// --- Proses Update ---
if (isset($_POST['update_device'])) {

    $alias = $_POST['alias'];
    $ip = $_POST['ip'];
    $lat = floatval($_POST['latitude']);     // FIX
    $lng = floatval($_POST['longitude']);    // FIX
    $state = intval($_POST['state']);        // FIX

    $update = $conn->prepare(
        "UPDATE devices 
         SET Alias=?, IPAddress=?, latitude=?, longitude=?, State=? 
         WHERE id=?"
    );

    // FIX PALING PENTING
    $update->bind_param("ssddii", $alias, $ip, $lat, $lng, $state, $id);
    $update->execute();

    header("Location: admindashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Device</title>
<link rel="stylesheet" href="css/editdeviceadmin.css">
</head>
<body>

<div class="box">
    <h2>Edit Device</h2>

    <form method="POST">
        <input type="hidden" name="id" value="<?= $device['id'] ?>">

        <label>Alias</label>
        <input type="text" name="alias" value="<?= htmlspecialchars($device['Alias']) ?>" required>

        <label>IP Address</label>
        <input type="text" name="ip" value="<?= htmlspecialchars($device['IPAddress']) ?>" required>

        <label>Latitude</label>
        <input type="text" name="latitude" value="<?= $device['latitude'] ?>" required>

        <label>Longitude</label>
        <input type="text" name="longitude" value="<?= $device['longitude'] ?>" required>

        <label>Status</label>
        <select name="state">
            <option value="1" <?= $device['State']==1?'selected':'' ?>>Online</option>
            <option value="0" <?= $device['State']==0?'selected':'' ?>>Tidak Dipakai</option>
            <option value="2" <?= $device['State']==2?'selected':'' ?>>Offline</option>
        </select>

        <button type="submit" name="update_device">Simpan Perubahan</button>
        <a href="admindashboard.php">Kembali ke Dashboard</a>
    </form>
</div>

</body>
</html>
