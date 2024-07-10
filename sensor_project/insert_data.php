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

// Get data from POST request
$temperature = $_POST['temperature'];
$humidity = $_POST['humidity'];
$pressure = $_POST['pressure'];
$altitude = $_POST['altitude'];
$api_key = $_POST['api_key'];

// Validate the API key
$valid_api_key = "q1w2e3r4t5y6u7i8o9";
if ($api_key !== $valid_api_key) {
    die("Invalid API key");
}

// Determine the meanings for each sensor reading
$temp_mean = "";
if ($temperature < 15) {
    $temp_mean = "Cold (Consider using heating)";
} elseif ($temperature >= 15 && $temperature <= 25) {
    $temp_mean = "Comfortable (Ideal room temperature)";
} elseif ($temperature > 25 && $temperature <= 30) {
    $temp_mean = "Warm (Might be comfortable with light clothing or AC)";
} else {
    $temp_mean = "Hot (Consider using AC or cooling methods)";
}

$humid_mean = "";
if ($humidity < 30) {
    $humid_mean = "Very Dry (May cause dry skin or respiratory irritation) - Consider using a humidifier.";
} elseif ($humidity >= 30 && $humidity <= 50) {
    $humid_mean = "Ideal (Comfortable for most people)";
} elseif ($humidity > 50 && $humidity <= 70) {
    $humid_mean = "Moderately Humid (May feel slightly muggy) - AC can help remove excess moisture.";
} else {
    $humid_mean = "High Humidity (Can feel muggy and promote mold growth) - Dehumidifier or increased ventilation recommended.";
}

$press_mean = "";
$baseline_pressure = 1013; // Adjust this value based on your local average sea-level pressure
if ($pressure < $baseline_pressure - 4) {
    $press_mean = "Low Pressure (Approaching storms)";
} elseif ($pressure >= $baseline_pressure - 4 && $pressure <= $baseline_pressure + 4) {
    $press_mean = "Mid Pressure (Normal, no significant weather changes)";
} else {
    $press_mean = "High Pressure (Stable or improving weather conditions)";
}

$alt_mean = "";
if ($altitude >= 0 && $altitude <= 100) {
    $alt_mean = "Sea Level";
} elseif ($altitude > 100 && $altitude <= 500) {
    $alt_mean = "Low Land";
} elseif ($altitude > 500 && $altitude <= 1000) {
    $alt_mean = "Hills";
}else{
	$alt_mean = "Mountainous";
}

// SQL query to insert the sensor data and their meanings into the 'readings_project' table
$sql = "INSERT INTO readings_project (temperature, humidity, pressure, altitude, temp_mean, humid_mean, press_mean, alt_mean) VALUES ('$temperature', '$humidity', '$pressure', '$altitude', '$temp_mean', '$humid_mean', '$press_mean', '$alt_mean')";

// Execute the query and check if the insertion was successful
if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    // Display an error message if the insertion failed
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close the database connection
$conn->close();
?>
