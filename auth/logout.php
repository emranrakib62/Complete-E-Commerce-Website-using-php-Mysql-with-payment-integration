<?php
require_once __DIR__ . '/../includes/functions.php';
Auth::logout();
session_start();
set_flash('success', 'You have been logged out.');
redirect('index.php');
