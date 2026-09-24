<?php
require_once __DIR__ . '/../config/functions.php';
$_SESSION = [];
session_unset();
session_destroy();
redirect('/index.php');
