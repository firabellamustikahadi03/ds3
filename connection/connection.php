<?php
$con = mysqli_connect("localhost","root","","spdempstershafer");
mysqli_set_charset($con, "utf8mb4");

if(!$con){
	echo "Failed to connect to MySQL : ".mysqli_connect_error();
}
?>