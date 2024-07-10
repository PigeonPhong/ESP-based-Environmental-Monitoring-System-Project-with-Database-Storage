<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "sensor_data"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch sensor data
$sql = "SELECT * FROM readings_project ORDER BY timestamp DESC LIMIT 50"; // Example query to get latest 50 readings
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Array to hold sensor data
    $sensorData = array();

    // Fetch data from result set
    while ($row = $result->fetch_assoc()) {
        $entry = array(
            "timestamp" => $row["timestamp"],
            "temperature" => $row["temperature"],
            "humidity" => $row["humidity"],
            "pressure" => $row["pressure"],
            "altitude" => $row["altitude"]
        );
        // Add entry to sensorData array
        $sensorData[] = $entry;
    }

    // Encode sensor data array to JSON format
    echo json_encode($sensorData);
} else {
    echo "0 results";
}

// Close connection
$conn->close();
?>
