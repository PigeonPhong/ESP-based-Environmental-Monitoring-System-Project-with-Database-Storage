<!DOCTYPE HTML>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://code.highcharts.com/highcharts.js"></script>
  <style>
    body {
      min-width: 310px;
      max-width: 800px;
      margin: 0 auto;
      background-color: #e8f5e9; /* Light Green */
      color: #4e342e; /* Dark Brown */
      font-family: Arial, sans-serif;
    }
    h2 {
      font-family: Arial;
      font-size: 2.5rem;
      text-align: center;
      color: #1b5e20; /* Dark Green */
    }
    .container {
      margin-top: 20px;
    }
    .stats {
      margin-top: 10px;
      font-size: 1rem;
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
  <h2>Weather Station</h2>
  <div id="chart-temperature" class="container"></div>
  <div class="stats" id="stats-temperature">Temperature: <span id="current-temperature">0</span> °C</div>
  <div id="chart-humidity" class="container"></div>
  <div class="stats" id="stats-humidity">Humidity: <span id="current-humidity">0</span> %</div>
  <div id="chart-pressure" class="container"></div>
  <div class="stats" id="stats-pressure">Pressure: <span id="current-pressure">0</span> hPa</div>
  <div id="chart-altitude" class="container"></div>
  <div class="stats" id="stats-altitude">Altitude: <span id="current-altitude">0</span> m</div>
  <button class="navigation-button" onclick="window.location.href='index.php'">Back to Sensor Readings</button>

  <!-- Dialog for notifications -->
  <dialog id="notificationDialog"></dialog>

  <script>
    // Function to show notification using <dialog> element
    function showNotification(sensor) {
      const dialog = document.getElementById('notificationDialog');
      dialog.textContent = 'New max or min value detected for ' + sensor + '!';
      dialog.show();
      setTimeout(() => dialog.close(), 3000); // Close the dialog after 3 seconds
    }

    // Function to calculate min, max, and average
    function calculateStats(data) {
      if (data.length === 0) return { min: 0, max: 0, avg: 0 };
      let sum = data.reduce((a, b) => a + b, 0);
      let avg = sum / data.length;
      let min = Math.min(...data);
      let max = Math.max(...data);
      return { min: min, max: max, avg: avg };
    }

    // Function to update statistics display
    function updateStatsDisplay(id, stats) {
      document.getElementById(id).innerHTML = `Min: ${stats.min.toFixed(2)}, Max: ${stats.max.toFixed(2)}, Avg: ${stats.avg.toFixed(2)}`;
    }

    // Common chart options
    var commonChartOptions = {
      chart: { type: 'line', animation: false },
      xAxis: {
        type: 'datetime'
      },
      plotOptions: {
        line: {
          dataLabels: { enabled: true }
        }
      },
      credits: { enabled: false }
    };

    // Temperature Chart
    var chartT = Highcharts.chart('chart-temperature', Highcharts.merge(commonChartOptions, {
      title: { text: 'Temperature' },
      series: [{ showInLegend: false, data: [], color: '#ffcc80' }], // Light Orange
      yAxis: { title: { text: 'Temperature (Celsius)' } }
    }));

    // Humidity Chart
    var chartH = Highcharts.chart('chart-humidity', Highcharts.merge(commonChartOptions, {
      title: { text: 'Humidity' },
      series: [{ showInLegend: false, data: [], color: '#64b5f6' }], // Light Blue
      yAxis: { title: { text: 'Humidity (%)' } }
    }));

    // Pressure Chart
    var chartP = Highcharts.chart('chart-pressure', Highcharts.merge(commonChartOptions, {
      title: { text: 'Pressure' },
      series: [{ showInLegend: false, data: [], color: '#fff176' }], // Yellow
      yAxis: { title: { text: 'Pressure (hPa)' } }
    }));

    // Altitude Chart
    var chartA = Highcharts.chart('chart-altitude', Highcharts.merge(commonChartOptions, {
      title: { text: 'Approximate Altitude' },
      series: [{ showInLegend: false, data: [], color: '#bcaaa4' }], // Light Brown
      yAxis: { title: { text: 'Altitude (m)' } }
    }));

    function fetchDataAndUpdateCharts() {
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          try {
            var sensorData = JSON.parse(this.responseText);

            var temperatureData = [];
            var humidityData = [];
            var pressureData = [];
            var altitudeData = [];

            sensorData.forEach(function(entry) {
              var timestamp = new Date(entry.timestamp).getTime()  + 8 * 60 * 60 * 1000;
              temperatureData.push([timestamp, parseFloat(entry.temperature)]);
              humidityData.push([timestamp, parseFloat(entry.humidity)]);
              pressureData.push([timestamp, parseFloat(entry.pressure)]);
              altitudeData.push([timestamp, parseFloat(entry.altitude)]);
            });

            chartT.series[0].setData(temperatureData, true, true, true);
            chartH.series[0].setData(humidityData, true, true, true);
            chartP.series[0].setData(pressureData, true, true, true);
            chartA.series[0].setData(altitudeData, true, true, true);

            updateStatsDisplay('stats-temperature', calculateStats(temperatureData.map(function(point) { return point[1]; })));
            updateStatsDisplay('stats-humidity', calculateStats(humidityData.map(function(point) { return point[1]; })));
            updateStatsDisplay('stats-pressure', calculateStats(pressureData.map(function(point) { return point[1]; })));
            updateStatsDisplay('stats-altitude', calculateStats(altitudeData.map(function(point) { return point[1]; })));

            // Update current values for notification checks
            document.getElementById('current-temperature').innerText = temperatureData.length ? temperatureData[temperatureData.length - 1][1] : 0;
            document.getElementById('current-humidity').innerText = humidityData.length ? humidityData[humidityData.length - 1][1] : 0;
            document.getElementById('current-pressure').innerText = pressureData.length ? pressureData[pressureData.length - 1][1] : 0;
            document.getElementById('current-altitude').innerText = altitudeData.length ? altitudeData[altitudeData.length - 1][1] : 0;

          } catch (e) {
            console.error("Failed to parse sensor data: ", e);
          }
        }
      };
      xhttp.open("GET", "get_sensor_data_for_charts.php", true);
      xhttp.send();
    }

    function checkForNewMaxMinValues() {
      // Fetch max/min values from the server
      fetch('get_max_min.php')
        .then(response => response.json())
        .then(data => {
          let maxTemperature = data.maxTemperature;
          let minTemperature = data.minTemperature;
          let maxHumidity = data.maxHumidity;
          let minHumidity = data.minHumidity;
          let maxPressure = data.maxPressure;
          let minPressure = data.minPressure;
          let maxAltitude = data.maxAltitude;
          let minAltitude = data.minAltitude;

          // Get current sensor values from the displayed data
          let currentTemperature = parseFloat(document.getElementById('current-temperature').innerText);
          let currentHumidity = parseFloat(document.getElementById('current-humidity').innerText);
          let currentPressure = parseFloat(document.getElementById('current-pressure').innerText);
          let currentAltitude = parseFloat(document.getElementById('current-altitude').innerText);

          // Check for new max/min values and show notifications
          if (currentTemperature > maxTemperature || currentTemperature < minTemperature) {
            showNotification('Temperature');
          }
          if (currentHumidity > maxHumidity || currentHumidity < minHumidity) {
            showNotification('Humidity');
          }
          if (currentPressure > maxPressure || currentPressure < minPressure) {
            showNotification('Pressure');
          }
          if (currentAltitude > maxAltitude || currentAltitude < minAltitude) {
            showNotification('Altitude');
          }
        })
        .catch(error => {
          console.error("Error fetching max/min values: ", error);
        });
    }

    setInterval(fetchDataAndUpdateCharts, 1000); // Fetch data and update charts every 5 seconds
    setInterval(checkForNewMaxMinValues, 5000); // Check for new max/min values every 5 seconds
  </script>
</body>
</html>
