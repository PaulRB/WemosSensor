<!DOCTYPE html>
<html>
<body>

<?php 
// Sensor Graph Script
// PaulRB
// Nov 2015

$sensor = $_GET["sensor"];
$view = $_GET["view"];
$servername = "www.hostmame.co.uk";
$username = "hostuserid";
$password = "hostpassword";
$dbname = "databasename";
$colours = array("magenta", "blue", "red", "brown", "cyan", "black");

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo "Sorry, the website is experiencing problems: " . $conn->connect_error();
    exit;
}

// Get sensor description and min & max values to be plotted
$sql = "SELECT   i.sensor_units,
                 MIN(s.sensor_time) AS min_time, MAX(s.sensor_time) AS max_time,
                 MIN(s.sensor_value) AS min_value, MAX(s.sensor_value) AS max_value
        FROM     granary_sensors s
        JOIN     sensor_info i ON i.sensor_name = s.sensor_name
        WHERE    s.sensor_name like '$sensor%'
        AND      s.sensor_time > NOW() - INTERVAL $view DAY
        GROUP BY i.sensor_units;";

if (!$result = $conn->query($sql)) {
    echo "Sorry, the website is experiencing problems.\n";
    exit;
}

$reading = $result->fetch_assoc();
$min_value = $reading['min_value'];
$max_value = $reading['max_value'];
$min_time = strtotime($reading['min_time']);
$max_time = strtotime($reading['max_time']);
$units = $reading['sensor_units'];

echo "<h1>Sensor Graphs ";
echo "<a href='http://$servername/graph.php?sensor=$sensor&view=1'>24 hrs</a> ";
echo "<a href='http://$servername/graph.php?sensor=$sensor&view=7'>7 days</a> ";
$sensor_type = substr($sensor,0,1);
echo "<a href='http://$servername/graph.php?sensor=$sensor_type&view=$view'>compare</a> ";
echo "<a href='http://$servername/sensors.php'>index</a> ";
echo "</h1>";

// Choose scale for vertial axis (sensor reading)
if ($max_value - $min_value > 100) $value_scale = 10;
elseif ($max_value - $min_value > 30) $value_scale = 5;
elseif ($max_value - $min_value > 10) $value_scale = 2;
elseif ($max_value - $min_value > 5) $value_scale = 1;
elseif ($max_value - $min_value > 3) $value_scale = 0.5;
elseif ($max_value - $min_value > 1) $value_scale = 0.2;
elseif ($max_value - $min_value > 0.5) $value_scale = 0.1;
elseif ($max_value - $min_value > 0.3) $value_scale = 0.05;
elseif ($max_value - $min_value > 0.1) $value_scale = 0.02;
else $value_scale = 0.01;

$min_value = $value_scale * floor(($min_value - $value_scale/2) / $value_scale);
$max_value = $value_scale * ceil(($max_value + $value_scale/2) / $value_scale);

// Choose scale for horizontal axis (time)
if ($view < 7) $time_scale = 2 * 60 * 60;
elseif ($view < 14) $time_scale = 12 * 60 * 60;
elseif ($view < 21) $time_scale = 24 * 60 * 60;
elseif ($view < 35) $time_scale = 48 * 60 * 60;
else $time_scale = 7 * 24 * 60 * 60;

echo "<svg width=100% viewBox='0 0 1000 500' >\n";

// Draw graph
echo "<rect x=0 y=0 width=1000 height=500 stroke=green fill=none stroke-width=2 />\n";

// draw vertical axis (sensor values)
for ($y = $min_value; $y < $max_value; $y +=$value_scale) {
    $yv = round(500 - 500 * ($y - $min_value) / ($max_value - $min_value), 0);
    echo "<line stroke=green stroke-dasharray='5,5' x1=0 y1=$yv x2=1000 y2=$yv />\n";
    echo "<text fill=green x=0 y=$yv >$y$units</text>\n";
}

// Draw horizonal axis (time)
for ($x = ceil($min_time / $time_scale) * $time_scale; $x < $max_time; $x += $time_scale) {
    $xv = round(1000 * ($x - $min_time) / ($max_time - $min_time), 0);
    echo "<line stroke=green stroke-dasharray='5,5' x1=$xv y1=0 x2=$xv y2=500 />\n";
    echo "<text fill=green x=$xv y=500 >";
    if ($x % 86400 != 0) echo date("H:i", $x);
    elseif ($view < 21) echo date("D d", $x);
    else echo date("d M", $x);
    echo "</text>\n";
}

// Get all values to be plotted
$sql = "SELECT   s.sensor_name, i.sensor_desc, s.sensor_time, s.sensor_value
        FROM     granary_sensors s
        JOIN     sensor_info i ON i.sensor_name = s.sensor_name
        WHERE    s.sensor_name like '$sensor%'
        AND      s.sensor_time > NOW() - INTERVAL $view DAY
        ORDER BY s.sensor_name, i.sensor_desc, s.sensor_time;";

if (!$result = $conn->query($sql)) {
    echo "Sorry, the website is experiencing problems.\n";
    exit;
}

// Draw data values
$current_sensor_name = "";
$current_sensor_colour = -1;
while ($reading = $result->fetch_assoc()) {
    $yv = round(500 - 500 * ($reading['sensor_value'] - $min_value) / ($max_value - $min_value), 0);
    $xv = round(1000 * (strtotime($reading['sensor_time']) - $min_time) / ($max_time - $min_time), 0);
    if ($reading['sensor_name'] != $current_sensor_name) {
        if ($current_sensor_name != "") echo "' />\n";
        $current_sensor_name = $reading['sensor_name'];
        $current_sensor_colour += 1;
        $colour = $colours[$current_sensor_colour];
        echo "<text fill=$colour x=$xv y=$yv >" . $reading['sensor_desc'] . "</text>\n";
        echo "<polyline fill=none stroke=$colour points='";
    }
    echo  "$xv $yv ";
}
echo "' />\n";

echo "</svg>";

$result->free();
$conn->close();

?>

</body>
</html>
