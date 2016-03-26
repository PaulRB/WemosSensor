<?php
$sensor=explode(",", $_GET["sensor"]);
$value=explode(",", $_GET["value"]);
$servername = "www.hostname.co.uk";
$username = "hostuserid";
$password = "hostpassword";
$dbname = "databasename";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("INSERT INTO granary_sensors (sensor_name, sensor_value) VALUES (?, ?)");


for ($i = 0; $i < count($sensor); $i++) {

	$stmt->bind_param("sd", $sensor[$i], $value[$i]);
	$stmt->execute();
	echo "New record created successfully: ", $sensor[$i], "=", $value[$i], "\n";
}

$conn->close();
?> 

