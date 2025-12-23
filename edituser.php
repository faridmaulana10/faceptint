<?php
session_start();
include "db.php";

// Cek login admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== "admin") {
    header("Location: index.php");
    exit();
}

// =============== AMBIL ID USER ===============
if (!isset($_GET['id'])) {
    die("ID user tidak ditemukan!");
}

$id = intval($_GET['id']);

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User tidak ditemukan!");
}

// =============== UPDATE DATA USER ===============
if (isset($_POST['update_user'])) {

    $username = $_POST['username'];
    $nip = $_POST['nip'];
    $alamat = $_POST['alamat'];

    // Jika password tidak diisi → password lama dipakai
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmtUpdate = $conn->prepare("UPDATE users SET username=?, nip=?, alamat_sekolah=?, password=? WHERE id=?");
        $stmtUpdate->bind_param("ssssi", $username, $nip, $alamat, $password, $id);
    } else {
        $stmtUpdate = $conn->prepare("UPDATE users SET username=?, nip=?, alamat_sekolah=? WHERE id=?");
        $stmtUpdate->bind_param("sssi", $username, $nip, $alamat, $id);
    }

    $stmtUpdate->execute();
    header("Location: admindashboard.php?success_user_edit=1");
    exit();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User</title>
<link rel="stylesheet" href="css/edituser.css">

</head>
<body>

<div class="edit-box">
    <h2>Edit User</h2>

    <form method="post">

        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>

        <label>NIP</label>
        <input type="text" name="nip" value="<?= htmlspecialchars($user['nip']); ?>">

        <label>Alamat Sekolah</label>
        <input type="text" name="alamat" value="<?= htmlspecialchars($user['alamat_sekolah']); ?>">

        <label>Password Baru (kosongkan jika tidak diganti)</label>
        <input type="password" name="password" placeholder="Password baru">

        <button type="submit" name="update_user">Simpan Perubahan</button>
    </form>

    <a class="back-btn" href="admindashboard.php">← Kembali ke Dashboard</a>
</div>

</body>
</html>
