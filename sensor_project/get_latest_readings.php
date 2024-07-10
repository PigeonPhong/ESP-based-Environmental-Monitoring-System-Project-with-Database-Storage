<?php
// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sensor_data";

// Create connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to select the 10 most recent readings from the 'readings' table
$sql = "SELECT * FROM readings_project ORDER BY timestamp DESC LIMIT 10";
$result = $conn->query($sql);

// Initialize an array to store the data
$data = array();
if ($result->num_rows > 0) {
    // Fetch each row and add it to the data array
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Encode the data array as a JSON string and output it
echo json_encode($data);

// Close the database connection
$conn->close();
?>
