<?php
declare(strict_types=1);
if (!isset($pageTitle)) {
    $pageTitle = 'MY NajotLink';
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> | MY NajotLink</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-auth="1">
<div class="app-bg"></div>
<div class="app-shell">
