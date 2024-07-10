<?php
header('Content-Type: application/json');

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sensor_data";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get max/min values
$sql = "SELECT MAX(temperature) as maxTemperature, MIN(temperature) as minTemperature,
               MAX(humidity) as maxHumidity, MIN(humidity) as minHumidity,
               MAX(pressure) as maxPressure, MIN(pressure) as minPressure,
               MAX(altitude) as maxAltitude, MIN(altitude) as minAltitude
        FROM readings_project";

$result = $conn->query($sql);

$maxMinValues = array();
if ($result->num_rows > 0) {
    $maxMinValues = $result->fetch_assoc();
}

echo json_encode($maxMinValues);

$conn->close();
?>
