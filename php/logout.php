<?php

require_once '../includes/header.php';

session_destroy();


header("Location: /application-system/index.php");
exit();
