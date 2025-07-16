<?php
session_start();

$_SESSION = array();

session_destroy();

header("Location: /application-system/index.php");
exit();
