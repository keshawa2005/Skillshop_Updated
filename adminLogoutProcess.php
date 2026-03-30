<?php

session_start();

unset($_SESSION["admin_logged_in"]);
unset($_SESSION["admin_email"]);
unset($_SESSION["admin_fname"]);
unset($_SESSION["admin_lname"]);

header("Location: ../admin-login.php");
exit();
