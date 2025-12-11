<?php
/**
 * EVSU Event Management System
 * Common Header Include
 * File: includes/header.php
 */

// Prevent direct access
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
}

// Get page title from variable or use default
$pageTitle = isset($pageTitle) ? $pageTitle : 'EVSU Event Management System';

// Get custom CSS files array
$customCSS = isset($customCSS) ? $customCSS : [];

// Get custom JS files array
$customJS = isset($customJS) ? $customJS : [];

// Get body class if set
$bodyClass = isset($bodyClass) ? $bodyClass : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="EVSU Event Management System - Streamline event requests and volunteer coordination">
    <meta name="author" content="EVSU">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" 
          referrerpolicy="no-referrer">
    
    <!-- Global Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Page-specific CSS -->
    <?php foreach ($customCSS as $css): ?>
        <link rel="stylesheet" href="assets/css/<?= htmlspecialchars($css) ?>.css">
    <?php endforeach; ?>
    
    <!-- Additional head content -->
    <?php if (isset($additionalHead)): ?>
        <?= $additionalHead ?>
    <?php endif; ?>
</head>
<body<?= $bodyClass ? ' class="' . htmlspecialchars($bodyClass) . '"' : '' ?>></body>