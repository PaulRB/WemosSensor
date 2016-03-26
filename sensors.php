<!DOCTYPE html>
<html>
<body>

<h1>Granary Sensors Page</h1>

<table border=1>
<tr><th>Sensor</th><th>Value</th><th>Time</th></tr>

<?php 
$servername = "www.hostname.co.uk";
$username = "hostuserid";
$password = "hostpassword";
$dbname = "databasename";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo "Sorry, the website is experiencing problems: " . $conn->connect_error();
    exit;
}

$sql = "SELECT   s.sensor_time, TIMESTAMPDIFF(MINUTE, s.sensor_time, NOW()) as sensor_age,
                 s.sensor_name, s.sensor_value, i.sensor_desc, i.sensor_units
        FROM     granary_sensors s
        JOIN     (   SELECT   sensor_name, MAX(sensor_time) AS max_time
                     FROM     granary_sensors
                     GROUP BY sensor_name) AS m
        ON       m.sensor_name = s.sensor_name 
        AND      m.max_time = s.sensor_time
        JOIN     sensor_info i
        ON       i.sensor_name = s.sensor_name
        ORDER BY s.sensor_name;";

if (!$result = $conn->query($sql)) {
    echo "Sorry, the website is experiencing problems.";
    exit;
}

// List sensors, values and last updates with links to graphs
while ($reading = $result->fetch_assoc()) {
    echo "<tr><td><a href='http://$servername/graph.php?sensor=" . $reading['sensor_name'] . "&view=1'>" . $reading['sensor_desc'] . "</a></td>";
    echo "<td>" . $reading['sensor_value'] . $reading['sensor_units'] . "</td>";
    if ($reading['sensor_age'] > 20) echo "<td><font color='red'>" . $reading['sensor_time'] . "</font></td>";
    else echo "<td>" . $reading['sensor_time'] . "</td>";
    echo "</tr>\n";
}

$result->free();
$conn->close();
?>

</table>

</body>
</html>
