/*********
  Name: Phiraphong A/L A Watt
  Matric.No: 288584
  Project (ESP-based Environmental Monitoring System Project with Database Storage)
*********/
#include <Wire.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_BME280.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <Servo.h>

// WiFi credentials
const char* ssid = "V2027";
const char* password = "password";

// Server URLs with correct IP and port
const char* serverUrl = "http://192.168.162.21:8080/sensor_project/insert_data.php";
const char* maxMinUrl = "http://192.168.162.21:8080/sensor_project/get_max_min.php";
const char* apiKey = "q1w2e3r4t5y6u7i8o9";

// Pin Definitions
#define MQ135_PIN A0  // MQ-135 gas sensor connected to analog pin A0
#define SERVO_PIN D5  // Servo motor connected to digital pin D5
#define SEALEVELPRESSURE_HPA (1013.25)

// Variables to store sensor readings
String temp_mean = "";
String humid_mean = "";
String press_mean = "";
String alt_mean = "";
float temperature, humidity, pressure, altitude;

// Variables to store max/min values (the value given is initial)
float maxTemperature = -100.0;
float minTemperature = 100.0;
float maxHumidity = 0.0;
float minHumidity = 100.0;
float maxPressure = 0.0;
float minPressure = 1100.0;
float maxAltitude = -1000.0;
float minAltitude = 10000.0;

// WiFi client object
WiFiClient client;
Adafruit_BME280 bme;
Servo myServo; // Create a servo object

void setup() {
  // Initialize serial communication at 115200 baud rate
  Serial.begin(115200);

  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.println("Connected to WiFi");

  // Configure pins
  myServo.attach(SERVO_PIN); // Attach the servo to the D5 pin
  myServo.write(0); // Initialize the servo position

  // Initialize BME280
  if (!bme.begin(0x76)) {
    Serial.println("Could not find a valid BME280 sensor, check wiring!");
    while (1);
  }

  Serial.println("Setup complete.");
}

// Function to read BME280 sensor
void readBME280() {
  temperature = bme.readTemperature();
  humidity = bme.readHumidity();
  pressure = bme.readPressure() / 100.0F;
  altitude = bme.readAltitude(SEALEVELPRESSURE_HPA);

  // Determine temperature meaning
  if (temperature < 15) {
    temp_mean = "Cold (Consider using heating)";
  } else if (temperature < 25) {
    temp_mean = "Comfortable (Ideal room temperature)";
  } else if (temperature < 30) {
    temp_mean = "Warm (Might be comfortable with light clothing or AC)";
  } else {
    temp_mean = "Hot (Consider using AC or cooling methods)";
  }

  // Determine humidity meaning
  if (humidity < 30) {
    humid_mean = "Very Dry (May cause dry skin or respiratory irritation) - Consider using a humidifier.";
  } else if (humidity < 50) {
    humid_mean = "Ideal (Comfortable for most people)";
  } else if (humidity < 70) {
    humid_mean = "Moderately Humid (May feel slightly muggy) - AC can help remove excess moisture.";
  } else {
    humid_mean = "High Humidity (Can feel muggy and promote mold growth) - Dehumidifier or increased ventilation recommended.";
  }

  // Determine pressure meaning (assuming baseline is 1013.25 hPa)
  float baselinePressure = SEALEVELPRESSURE_HPA;
  if (pressure < baselinePressure - 4) {
    press_mean = "Low Pressure (approaching storms)";
  } else if (pressure > baselinePressure + 4) {
    press_mean = "High Pressure (stable or improving weather conditions)";
  } else {
    press_mean = "Mid Pressure (normal conditions)";
  }

  // Determine altitude meaning
  if (altitude < 100) {
    alt_mean = "Sea Level";
  } else if (altitude < 500) {
    alt_mean = "Low Land";
  } else if (altitude < 1000) {
    alt_mean = "Hills";
  } else {
    alt_mean = "Mountainous";
  }
}

// Function to fetch max/min values from the server
void fetchMaxMinValues() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(client, maxMinUrl);
    int httpResponseCode = http.GET();
    if (httpResponseCode > 0) {
      String payload = http.getString();
      Serial.println("Max/Min values: " + payload);
      // Parse the JSON response and update max/min values
      DynamicJsonDocument doc(1024);
      deserializeJson(doc, payload);
      maxTemperature = doc["maxTemperature"];
      minTemperature = doc["minTemperature"];
      maxHumidity = doc["maxHumidity"];
      minHumidity = doc["minHumidity"];
      maxPressure = doc["maxPressure"];
      minPressure = doc["minPressure"];
      maxAltitude = doc["maxAltitude"];
      minAltitude = doc["minAltitude"];
    } else {
      Serial.println("Error fetching max/min values: " + String(httpResponseCode));
    }
    http.end();
  } else {
    Serial.println("WiFi not connected");
  }
}

// Function to check for new max/min values and activate servo
void checkForNewMaxMinValues() {
  bool newMaxMinDetected = false;

  // Check temperature
  if (temperature > maxTemperature || temperature < minTemperature) {
    newMaxMinDetected = true;
  }

  // Check humidity
  if (humidity > maxHumidity || humidity < minHumidity) {
    newMaxMinDetected = true;
  }

  // Check pressure
  if (pressure > maxPressure || pressure < minPressure) {
    newMaxMinDetected = true;
  }

  // Check altitude
  if (altitude > maxAltitude || altitude < minAltitude) {
    newMaxMinDetected = true;
  }

  // Activate servo if any new max/min value is detected
  if (newMaxMinDetected) {
    myServo.write(90); // Move servo to 90 degrees
    delay(1000); // Keep servo in position for 1 second
    myServo.write(0);  // Move servo back to 0 degrees
  }
}

// Function to send data to the server
void sendData() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;                                                      // Create HTTP client object
    http.begin(client, serverUrl);                                        // Initialize HTTP client with server URL
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");  // Add header

    // Create POST data string
    String postData = "temperature=" + String(temperature) + "&temp_mean=" + temp_mean + "&humidity=" + String(humidity) + "&humid_mean=" + humid_mean + "&pressure=" + String(pressure) + "&press_mean=" + press_mean + "&altitude=" + String(altitude) + "&alt_mean=" + alt_mean + "&api_key=" + String(apiKey);

    // Send POST request
    Serial.println("Sending POST request...");
    int httpResponseCode = http.POST(postData);
    if (httpResponseCode > 0) {
      String response = http.getString();                                 // Get response
      Serial.println(postData);                                           // Print POST data
      Serial.println("HTTP Response code: " + String(httpResponseCode));  // Print response code
      Serial.println("Response: " + response);                            // Print response
    } else {
      Serial.print("Error on sending POST: ");  // Print error
      Serial.println(httpResponseCode);
      Serial.println(http.errorToString(httpResponseCode));
    }
    http.end();  // End HTTP client
  } else {
    Serial.println("WiFi not connected");  // Print WiFi not connected message
  }
}

void loop() {
  static int readingCount = 0;  // Initialize reading counter
  
  readBME280();  // Read BME280 sensor
  fetchMaxMinValues(); // Fetch max/min values from server
  checkForNewMaxMinValues(); // Check for new max/min values and activate servo if needed
  
  // Skip sending data for the first 10 readings
  if (readingCount >= 10) {
    sendData();    // Send data to server
  } else {
    readingCount++;  // Increment counter for calibration readings
    Serial.println("Calibrating... (" + String(readingCount) + "/10)");
  }

  delay(5000);   // Wait for 5 seconds before next reading
}
