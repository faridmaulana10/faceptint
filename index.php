<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($role === "admin") {
        if ($username === "admin" && $password === "admin") {
            $_SESSION['logged_in'] = true;
            $_SESSION['role'] = "admin";
            $_SESSION['username'] = $username;
            header("Location: admindashboard.php");
            exit();
        } else {
            $error = "Login Admin salah!";
        }
    }

    if ($role === "user") {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['role'] = "user";
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $row['id']; // wajib ada
            header("Location: userdashboard.php");
            exit();
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Faceprint</title>
<style>
/* ===== CSS Login Box ===== */
* {margin:0; padding:0; box-sizing:border-box; font-family:sans-serif;}
body {
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:url('https://smjtimes.com/wp-content/uploads/2022/05/dinkominfo-rembang.jpg') no-repeat center center fixed;
    background-size:cover;
}
.login-box {
    width:380px;
    padding:40px;
    border-radius:20px;
    background:rgba(0,0,0,0.5);
    backdrop-filter:blur(10px);
    box-shadow:0 8px 30px rgba(0,0,0,0.3);
    text-align:center;
}
.login-box h2 {
    color:#f3e305;
    margin-bottom:25px;
    font-size:26px;
}
.error {
    color:#e01515;
    font-size:14px;
    margin-bottom:15px;
}
.login-box select,
.login-box input,
.login-box button,
.login-box .reg-btn {
    width:100%;
    padding:12px 15px;
    margin:8px 0;
    border-radius:10px;
    border:none;
    font-size:16px;
    text-align:center;
}
.login-box select,
.login-box input {
    background:rgba(255,255,255,0.2);
    color:#f3e305;
}
.login-box input::placeholder {
    color:#f3e305;
}
.login-box button {
    background:linear-gradient(135deg,#6a11cb,#2575fc);
    color:#f3e305;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s ease;
}
.login-box button:hover {
    background:linear-gradient(135deg,#2575fc,#6a11cb);
    box-shadow:0 5px 15px rgba(0,0,0,0.3);
}
.login-box .reg-btn {
    display:block;
    text-decoration:none;
    background:rgba(255,255,255,0.3);
    color:#f3e305;
    font-weight:bold;
    margin-top:10px;
    transition:0.3s ease;
}
.login-box .reg-btn:hover {
    background:rgba(255,255,255,0.5);
}
</style>
</head>
<body>
<div class="login-box">
    <h2>Login Faceprint</h2>
    <?php if($error) echo "<div class='error'>$error</div>"; ?>
    <form method="POST">
        <select name="role" required>
            <option value="">-- Pilih Login --</option>
            <option value="admin">Login sebagai Admin</option>
            <option value="user">Login sebagai User</option>
        </select>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Masuk</button>
        <a href="registrasi.php" class="reg-btn">Registrasi User Baru</a>
    </form>
</div>
</body>
</html>
