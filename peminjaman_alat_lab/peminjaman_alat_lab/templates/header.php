<?php
require_once __DIR__ . "/../config.php";

// Cek jika user belum login, redirect ke halaman login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Peminjaman</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <link rel="stylesheet" href="assets/style.css"> 
    <style>body{font-family: 'Inter', sans-serif;}</style>
</head>
<body class="bg-gray-100 text-gray-800">
    <div id="main-app">
        <nav class="bg-white shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <i class="fas fa-flask text-indigo-600 text-2xl"></i>
                        <span class="font-bold ml-2 text-xl">LabApp</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium">Halo, <?php echo htmlspecialchars($_SESSION["nama"]); ?></span>
                        <a href="dashboard.php?page=edit_profil" class="text-sm font-medium text-indigo-600 hover:text-indigo-900" title="Edit Profil">
                           Edit Profil
                        </a>
                        <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-white bg-red-500 hover:bg-red-600">Logout</a>
                    </div>
                </div>
            </div>
        </nav>
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">