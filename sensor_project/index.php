<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Readings</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e8f5e9; /* Light Green */
            color: #4e342e; /* Dark Brown */
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h1 {
            color: #1b5e20; /* Dark Green */
            margin-top: 20px;
        }
        .sensor-display {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
            width: 80%;
        }
        .sensor-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            color: black;
            height: 100px;
            text-align: center;
            border-radius: 8px;
        }
        .temperature { background-color: #ffcc80; } /* Light Orange */
        .humidity { background-color: #64b5f6; } /* Light Blue */
        .pressure { background-color: #fff176; } /* Yellow */
        .altitude { background-color: #bcaaa4; } /* Light Brown */
        .meaning {
            font-size: 1em;
            color: #4e342e; /* Dark Brown */
        }
        table {
            border-collapse: collapse;
            width: 80%;
            max-width: 800px;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #4e342e; /* Dark Brown */
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #8d6e63; /* Brown */
            color: white;
        }
        tbody tr:nth-child(even) {
            background-color: #f1f8e9; /* Very Light Green */
        }
        tbody tr:nth-child(odd) {
            background-color: #c8e6c9; /* Light Green */
        }
        .statistics {
            width: 80%;
            max-width: 800px;
            margin-bottom: 20px;
        }
        .stat-box {
            margin: 10px 0;
        }
        .stat-title {
            font-weight: bold;
        }
        .navigation-button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #1b5e20; /* Dark Green */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        dialog {
            position: absolute;
			top: 50%;
			left: 50%;
			border: none;
            padding: 1rem;
            background: #ffeb3b; /* Yellow */
            color: #333;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            font-family: Arial, sans-serif;
            width: 300px;
            max-width: 90%;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Environmental Readings</h1>
    <!-- Display latest sensor readings in a 2x4 grid -->
    <div class="sensor-display">
        <div class="sensor-box temperature">
            <div>Temperature: <span id="temperature">0</span> °C</div>
        </div>
        <div class="sensor-box humidity">
            <div>Humidity: <span id="humidity">0</span> %</div>
        </div>
        <div class="sensor-box pressure">
            <div>Pressure: <span id="pressure">0</span> hPa</div>
        </div>
        <div class="sensor-box altitude">
            <div>Approximate Altitude: <span id="altitude">0</span> m</div>
        </div>
        <div class="sensor-box temperature">
            <div class="meaning" id="temp-mean"></div>
        </div>
        <div class="sensor-box humidity">
            <div class="meaning" id="humid-mean"></div>
        </div>
        <div class="sensor-box pressure">
            <div class="meaning" id="press-mean"></div>
        </div>
        <div class="sensor-box altitude">
            <div class="meaning" id="alt-mean"></div>
        </div>
    </div>
    <!-- Display the timestamp of the latest reading -->
    <h5>Update on: <span id="timestamp"></span></h5>

    <!-- Navigation button to the weather station page -->
    <button class="navigation-button" onclick="window.location.href='weather_station.php'">Go to Graphs</button>

    <!-- Heading for the recent data table -->
    <h2>10 Recent Data</h2>
    <!-- Table to display recent sensor readings -->
    <table>
        <thead>
            <tr>
                <th>Temperature (°C)</th>
                <th>Humidity (%)</th>
                <th>Pressure (hPa)</th>
                <th>Approximate Altitude (m)</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody id="readings-table">
        </tbody>
    </table>

    <!-- Dialog for notifications -->
    <dialog id="notificationDialog"></dialog>

    <script>
    // Function to fetch the latest sensor readings from the server
    function fetchLatestReadings() {
        $.ajax({
            url: 'get_latest_readings.php', // URL to get the latest readings
            method: 'GET',
            success: function(data) {
                var readings = JSON.parse(data); // Parse the JSON data received from the server
                var tableBody = $('#readings-table');
                tableBody.empty(); // Clear the current table contents

                if (readings.length > 0) {
                    // Display the latest reading in the sensor display grid
                    var latestReading = readings[0];
                    $('#temperature').text(latestReading.temperature);
                    $('#humidity').text(latestReading.humidity);
                    $('#pressure').text(latestReading.pressure);
                    $('#altitude').text(latestReading.altitude);
                    $('#timestamp').text(latestReading.timestamp);

                    // Set the meanings
                    $('#temp-mean').text(latestReading.temp_mean);
                    $('#humid-mean').text(latestReading.humid_mean);
                    $('#press-mean').text(latestReading.press_mean);
                    $('#alt-mean').text(latestReading.alt_mean);

                    // Check for new max/min values
                    checkForNewMaxMinValues(latestReading);
                    
                    // Populate the table with the recent readings
                    readings.forEach(function(reading) {
                        var row = '<tr>' +
                            '<td>' + reading.temperature + '</td>' +
                            '<td>' + reading.humidity + '</td>' +
                            '<td>' + reading.pressure + '</td>' +
                            '<td>' + reading.altitude + '</td>' +
                            '<td>' + reading.timestamp + '</td>' +
                            '</tr>';
                        tableBody.append(row);
                    });
                }
            }
        });
    }

    function checkForNewMaxMinValues(latestReading) {
        // Fetch max/min values from the server
        fetch('get_max_min.php')
            .then(response => response.json())
            .then(data => {
                let maxTemperature = parseFloat(data.maxTemperature);
                let minTemperature = parseFloat(data.minTemperature);
                let maxHumidity = parseFloat(data.maxHumidity);
                let minHumidity = parseFloat(data.minHumidity);
                let maxPressure = parseFloat(data.maxPressure);
                let minPressure = parseFloat(data.minPressure);
                let maxAltitude = parseFloat(data.maxAltitude);
                let minAltitude = parseFloat(data.minAltitude);

                // Get current sensor values from the displayed data
                let currentTemperature = parseFloat(latestReading.temperature);
                let currentHumidity = parseFloat(latestReading.humidity);
                let currentPressure = parseFloat(latestReading.pressure);
                let currentAltitude = parseFloat(latestReading.altitude);

                // Check for new max/min values
                if (currentTemperature >= maxTemperature || currentTemperature <= minTemperature) {
                    showNotification('Temperature');
                }
                if (currentHumidity >= maxHumidity || currentHumidity <= minHumidity) {
                    showNotification('Humidity');
                }
                if (currentPressure >= maxPressure || currentPressure <= minPressure) {
                    showNotification('Pressure');
                }
                if (currentAltitude >= maxAltitude || currentAltitude <= minAltitude) {
                    showNotification('Altitude');
                }
            })
            .catch(error => console.error('Error fetching max/min values:', error));
    }

    function showNotification(sensor) {
        const dialog = document.getElementById('notificationDialog');
        dialog.innerHTML = 'New max or min value detected for ' + sensor + '!';
        dialog.show();
        setTimeout(() => dialog.close(), 3000); // Close dialog after 3 seconds
    }

    // Check for new max/min values every 5 seconds
    setInterval(fetchLatestReadings, 1000); // Fetch new readings every 1 second
    setInterval(checkForNewMaxMinValues, 5000); // Check for new max/min values every 5 seconds
    </script>
</body>
</html>
