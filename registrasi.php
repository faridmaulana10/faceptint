<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db.php';

$message = ""; // Pastikan variabel selalu terdefinisi
$registrasi_sukses = false; // Flag untuk menampilkan tombol kembali

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $nip = $_POST['nip'];
    $alamat = $_POST['alamat_sekolah'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        $message = "Username sudah digunakan!";
    } else {
        $sql = "INSERT INTO users (nama, nip, alamat_sekolah, username, password)
                VALUES ('$nama', '$nip', '$alamat', '$username', '$password')";
        if (mysqli_query($conn, $sql)) {
            $message = "Registrasi berhasil! Silakan login.";
            $registrasi_sukses = true; // Tandai sukses
        } else {
            $message = "Gagal menyimpan: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi User</title>
<style>
/* === CSS Registrasi Modern & Tengah Semua === */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #6a11cb, #2575fc);
}

.registrasi-container {
    width: 400px;
    max-width: 90%;
    padding: 40px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.registrasi-container h2 {
    color: #fff;
    margin-bottom: 25px;
    font-size: 28px;
}

.registrasi-container form {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.registrasi-container input {
    width: 90%;
    padding: 12px 15px;
    margin-bottom: 20px;
    border: 1px solid rgba(255,255,255,0.4);
    border-radius: 10px;
    background: rgba(255,255,255,0.2);
    color: #fff;
    font-size: 16px;
    text-align: center;
}

.registrasi-container input::placeholder {
    color: #eee;
    text-align: center;
}

.registrasi-btn, .kembali-btn {
    width: 90%;
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    color: #fff;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.registrasi-btn:hover, .kembali-btn:hover {
    background: linear-gradient(135deg, #2575fc, #6a11cb);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.msg {
    margin-bottom: 15px;
    color: #ffdddd;
    font-weight: bold;
    font-size: 15px;
    text-align: center;
}
</style>
</head>
<body>

<div class="registrasi-container">
    <h2>Form Registrasi User</h2>

    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if(!$registrasi_sukses): ?>
        <form method="POST" action="registrasi.php">
            <input type="text" name="nama" placeholder="Nama Lengkap" required>
            <input type="text" name="nip" placeholder="NIP" required>
            <input type="text" name="alamat_sekolah" placeholder="Alamat Sekolah" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="registrasi-btn">Registrasi</button>
        </form>
    <?php else: ?>
        <a href="index.php"><button class="kembali-btn">Kembali ke Login</button></a>
    <?php endif; ?>
</div>

</body>
</html>
