<?php

session_start();

$_SESSION = [];
// Destroy the sessiondata on the server
session_destroy();

setcookie('skillshop_remember', '', time() - 3600, '/');
setcookie('skillshop_user_id', '', time() - 3600, '/');
setcookie('skillshop_user_email', '', time() - 3600, '/');

header('Location: ../index.php?logout=success');

?>