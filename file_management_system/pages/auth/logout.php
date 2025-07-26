<?php
require_once '../../includes/session.php';

$session->logout();
header('Location: login.php?msg=logged_out');
exit();
?>