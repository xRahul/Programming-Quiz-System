<?php

declare(strict_types=1);

/**
 * Shared document opener for every HTML page.
 * The includer must set $page_title before requiring this file.
 */
$page_title = isset($page_title) ? (string) $page_title : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($page_title); ?></title>
