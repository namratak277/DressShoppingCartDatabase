<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="/assets/css/home.css">
</head>
<body>
<header class="navigation" style="margin-bottom:1.5rem">
    <a href="/">Home</a>
    
    <a href="/cart.php">Cart</a>
    <a href="/admin/login.php">Admin</a>
</header>
<main>
