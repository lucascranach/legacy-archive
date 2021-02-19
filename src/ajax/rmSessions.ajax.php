<?php
if(session_id() == '') {
  session_set_cookie_params(604800, '/');
  session_start();
}

require_once('../../FirePHPCore/fb.php');
$data = $_POST['data'];
unset($_SESSION['user'][$data]);
echo json_encode(true);
?>
