/*
 *  WiFi Indoor Sensor
 *  By PaulRB Nov 2015
 *  WeMos D1-mini Sketch
 */

//#define USING_BMP180
#define USING_HTU21
//#define USING_BME280
//#define USING_I2C_LCD
#define USING_BATLEVEL

extern "C" {
#include "user_interface.h"
}

#include <Wire.h>

#include <SparkFunHTU21D.h>
#include <SFE_BMP180.h>
#include <SparkFunBME280.h>
#include <ESP8266WiFi.h>
#include <LiquidCrystal_I2C.h>

const double e = 2.71828;
const byte battLevelPin = A0;

const char ssid[]     = "ssid";
const char password[] = "wifipassword";
const char host[]     = "www.hostname.co.uk";

#ifdef USING_I2C_LCD
LiquidCrystal_I2C lcd(0x27, 2, 1, 0, 4, 5, 6, 7, 3, POSITIVE);
#endif

//Create an instance of the sensor objects
#ifdef USING_HTU21
HTU21D humiditySensor;
#endif

#ifdef USING_BMP180
SFE_BMP180 pressureSensor;
#endif

#ifdef USING_BME280
BME280 combinedSensor;
#endif

// Use WiFiClient class to create TCP connections
WiFiClient client;

void setup()
{
  Serial.begin(115200);
  Serial.println();

  Serial.println("Starting Wire");
  Wire.begin();
  //Wire.begin(2, 0); // For ESP-01
  Serial.println("Wire started");

#ifdef USING_I2C_LCD
  lcd.begin(16, 2);
#endif

  Serial.println("Starting Sensors");

#ifdef USING_HTU21
  humiditySensor.begin();
#endif

#ifdef USING_BMP180
  pressureSensor.begin();
#endif

#ifdef USING_BME280
  combinedSensor.settings.commInterface = I2C_MODE;
  combinedSensor.settings.I2CAddress = 0x76;
  combinedSensor.settings.runMode = 3; //Normal mode
  combinedSensor.settings.tStandby = 0;
  combinedSensor.settings.filter = 0;
  combinedSensor.settings.tempOverSample = 1;
  combinedSensor.settings.pressOverSample = 1;
  combinedSensor.settings.humidOverSample = 1;
  combinedSensor.begin();
#endif

  Serial.println("Sensors started");

}

void loop()
{

  unsigned long startTime = millis();
  double temperatureNow;
  String sensors;
  String values;

#ifdef USING_BMP180
  delay(pressureSensor.startTemperature());
  pressureSensor.getTemperature(temperatureNow);
  double pressureNow;
  delay(pressureSensor.startPressure(1));
  pressureSensor.getPressure(pressureNow, temperatureNow);
#endif

#ifdef USING_HTU21
  temperatureNow = humiditySensor.readTemperature();
  double humidityNow = humiditySensor.readHumidity();
#endif

#ifdef USING_BME280
  combinedSensor.begin(); // must do this every time because reset() is used, see below
  temperatureNow = combinedSensor.readTempC();
  double humidityNow = combinedSensor.readFloatHumidity();
  double pressureNow = combinedSensor.readFloatPressure() / 100;
  combinedSensor.reset(); // reduces current consumption from 400uA to 8uA by putting sensor to sleep/standby.
#endif

#ifdef USING_BATLEVEL
  double battLevelNow = analogRead(battLevelPin) / 209.66; // assumes external 180K resistor, needs calibration for each circuit
#endif

  Serial.print("New sensor readings:");
  Serial.print(" Temperature:");
  Serial.print(temperatureNow, 1);
  Serial.print("C");

#if defined USING_HTU21 || defined USING_BME280
  double absoluteHumidityNow = (6.112 * pow(e, (17.67 * temperatureNow) / (temperatureNow + 243.5)) * humidityNow * 2.1674) / (273.15 + temperatureNow);

  Serial.print(" Humidity:");
  Serial.print(humidityNow, 1);
  Serial.print("% = ");
  Serial.print(absoluteHumidityNow, 1);
  Serial.print("g/m3");
#endif

#if defined USING_BMP180 || defined USING_BME280
  Serial.print(" Pressure:");
  Serial.print(pressureNow, 1);
  Serial.print("Pa");
#endif

#ifdef USING_BATLEVEL
  Serial.print(" Batt Level:");
  Serial.print(battLevelNow, 2);
  Serial.print("V");

#endif

  Serial.println();

#ifdef USING_I2C_LCD
  lcd.setCursor(0, 0);
  lcd.print("T:");
  lcd.print(temperatureNow, 1);
  lcd.print("C");
  lcd.print(" H:");
  lcd.print(humidityNow, 1);
  lcd.print("% ");
  lcd.setCursor(0, 1);
  lcd.print("P:");
  lcd.print(pressureNow, 1);
  lcd.print("Pa");
#endif

  WiFi.mode(WIFI_STA); // Station Mode
  wifi_fpm_set_sleep_type(LIGHT_SLEEP_T); // Enable light sleep mode, not sure this is doing anything

  Serial.println();
  Serial.print("Connecting to ");
  Serial.print(ssid);

  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED && millis() - startTime < 30000UL) {
    delay(500);
    Serial.print(".");
  }

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi connection failed");
  }
  else {

    Serial.println("OK");
    Serial.print("WiFi connected, IP address: ");
    Serial.println(WiFi.localIP());

    Serial.println();
    Serial.print("connecting to ");
    Serial.println(host);

    const int httpPort = 80;
    if (!client.connect(host, httpPort)) {
      Serial.println("Host connection failed");
    }
    else {

      if (temperatureNow > -30 && temperatureNow < 50) {
        sensors += "TC";
        values += temperatureNow;
      }

#ifdef USING_BATLEVEL
      if (battLevelNow > 0 && battLevelNow < 20) {
        sensors += ",BC";
        values += ",";
        values += battLevelNow;
      }
#endif

#if defined USING_HTU21 || defined USING_BME280
      if (humidityNow > 10 && humidityNow < 100) {
        sensors += ",HC";
        values += ",";
        values += humidityNow;
      }

      if (absoluteHumidityNow > 5 && absoluteHumidityNow < 25) {
        sensors += ",AC";
        values += ",";
        values += absoluteHumidityNow;
      }
#endif

#if defined USING_BMP180 || defined USING_BME280
      if (pressureNow > 900 && pressureNow < 1100) {
        sensors += ",PC";
        values += ",";
        values += pressureNow;
      }
#endif

      // We now create a URI for the request
      String url = "/script.php";
      url += "?sensor=";
      url += sensors;
      url += "&value=";
      url += values;

      Serial.print("Requesting URL: ");
      Serial.println(url);

      // This will send the request to the server
      client.print(String("GET ") + url + " HTTP/1.1\r\n" +
                   "Host: " + host + "\r\n" +
                   "Connection: close\r\n\r\n");

      while (!client.available()) {
        if (millis() - startTime > 30000UL) {
          Serial.println("request failed");
          break;
        }
        delay(100);
      }

      // Read all the lines of the reply from server and print them to Serial
      while (client.available()) {
        String line = client.readStringUntil('\r');
        Serial.print(line);
      }

      Serial.println();
      Serial.println("closing connection");

      Serial.print("Total time: ");
      Serial.println( millis() - startTime);
    }
  }

  Serial.println("Sleeping...!");
  //WiFi.forceSleepBegin();
  //delay(900000UL); // 15 mins
  //WiFi.forceSleepWake();
  ESP.deepSleep(900000000UL); // 15 mins. requires ~1K between D0 --> RST connection to wake, wire connection prevents sketch upload!
}


