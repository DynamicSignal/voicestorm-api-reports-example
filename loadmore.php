<?php

require_once "functions.php";
if (!empty($_POST) && isset($_POST["duration"]) && isset($_POST['rowCount']))
{
    $result = createRows($_POST["duration"], $_POST['rowCount']);
    echo $result;
}
else
{
    echo '';
}
?>

