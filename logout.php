<?php
require __DIR__ . '/../config/init.php';
logout();
session_start();
flash('Anda berhasil logout.');
redirect('login.php');
