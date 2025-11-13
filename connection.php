<?php 
// CORS headers at the very top
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Database configuration - MOVE TO CONFIG FILE FOR PRODUCTION
$serverName = "localhost";
$userName = "root";
$password = "";
$dbName = "if0_38238968_dipesh";

// API Key - MOVE TO ENVIRONMENT VARIABLE FOR PRODUCTION
// Get your key from: https://openweathermap.org/api
$apikey = "a1387411d9751f10a2be3e09afc3fcb4"; // TODO: Move to .env file

// Create connection
$conn = mysqli_connect($serverName, $userName, $password);
if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// Create database if not exists
$createDatabase = "CREATE DATABASE IF NOT EXISTS " . $dbName;
if (!mysqli_query($conn, $createDatabase)) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to create database"]);
    exit();
}

// Select database
mysqli_select_db($conn, $dbName);

// Create table if not exists
$createTable = "CREATE TABLE IF NOT EXISTS WEATHER(
    id INT AUTO_INCREMENT PRIMARY KEY,
    City_Name VARCHAR(255) NOT NULL,
    Temperature VARCHAR(255),
    Humidity VARCHAR(255),
    Wind_Speed VARCHAR(255),
    Wind_Direction VARCHAR(255),
    Pressure VARCHAR(255),
    Icon_Code VARCHAR(100),
    Last_Updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_city (City_Name)
)";

if (!mysqli_query($conn, $createTable)) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to create table"]);
    exit();
}

// Get city name from query parameter
$cityname = isset($_GET['t']) ? trim($_GET['t']) : "Guntersville";

// Validate city name (basic validation)
if (empty($cityname) || strlen($cityname) > 100) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid city name"]);
    exit();
}

/**
 * Fetch weather data from OpenWeather API and update database
 */
function checkWeather($conn, $city, $apikey) {
    $city = urlencode($city);
    $url = "https://api.openweathermap.org/data/2.5/weather?q=$city&appid=$apikey&units=metric";

    // Fetch data from API
    $mydata = @file_get_contents($url);
    if ($mydata === false) {
        return null;
    }
    
    $data = json_decode($mydata, true);
    
    // Check if API returned valid data
    if (!isset($data['name']) || !isset($data['main'])) {
        return null;
    }

    $City_Name = $data['name'];
    $Temperature = $data['main']['temp'];
    $Humidity = $data['main']['humidity'];
    $Wind_Speed = $data['wind']['speed'];
    $Wind_Direction = $data['wind']['deg'];
    $Pressure = $data['main']['pressure'];
    $Icon_Code = $data['weather'][0]['icon'];

    // Use prepared statements to prevent SQL injection
    $stmt = mysqli_prepare($conn, "SELECT * FROM WEATHER WHERE City_Name = ?");
    mysqli_stmt_bind_param($stmt, "s", $City_Name);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($check_result) > 0) {
        // Update existing record
        $stmt_update = mysqli_prepare($conn, 
            "UPDATE WEATHER SET 
            Temperature = ?,
            Humidity = ?,
            Wind_Speed = ?,
            Wind_Direction = ?,
            Pressure = ?,
            Icon_Code = ?
            WHERE City_Name = ?");
        
        mysqli_stmt_bind_param($stmt_update, "sssssss", 
            $Temperature, $Humidity, $Wind_Speed, $Wind_Direction, $Pressure, $Icon_Code, $City_Name);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    } else {
        // Insert new record
        $stmt_insert = mysqli_prepare($conn,
            "INSERT INTO WEATHER(City_Name, Temperature, Humidity, Wind_Speed, Wind_Direction, Pressure, Icon_Code) 
             VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        mysqli_stmt_bind_param($stmt_insert, "sssssss", 
            $City_Name, $Temperature, $Humidity, $Wind_Speed, $Wind_Direction, $Pressure, $Icon_Code);
        mysqli_stmt_execute($stmt_insert);
        mysqli_stmt_close($stmt_insert);
    }
    
    mysqli_stmt_close($stmt);

    return [
        'City_Name' => $City_Name,
        'Temperature' => $Temperature,
        'Humidity' => $Humidity,
        'Wind_speed' => $Wind_Speed,
        'Wind_Direction' => $Wind_Direction,
        'Pressure' => $Pressure,
        'Icon_Code' => $Icon_Code
    ];
}

// Main logic
$finaldata = [];

// Use prepared statement to select data
$stmt_select = mysqli_prepare($conn, "SELECT * FROM WEATHER WHERE City_Name = ?");
mysqli_stmt_bind_param($stmt_select, "s", $cityname);
mysqli_stmt_execute($stmt_select);
$result1 = mysqli_stmt_get_result($stmt_select);

if (mysqli_num_rows($result1) == 0) {
    // No data exists, fetch from API
    $weatherData = checkWeather($conn, $cityname, $apikey);
    if ($weatherData) {
        $finaldata[] = $weatherData;
    } else {
        http_response_code(404);
        echo json_encode(["error" => "City not found"]);
        exit();
    }
} else {
    // Data exists, check if it needs updating
    $row = mysqli_fetch_assoc($result1);
    $lastFetchTime = new DateTime($row['Last_Updated']);
    $currentTime = new DateTime();
    $timeDiff = $currentTime->getTimestamp() - $lastFetchTime->getTimestamp();

    // Update if data is older than 2 hours
    if ($timeDiff > 2 * 60 * 60) {
        // Delete old record
        $stmt_delete = mysqli_prepare($conn, "DELETE FROM WEATHER WHERE City_Name = ?");
        mysqli_stmt_bind_param($stmt_delete, "s", $cityname);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
        
        // Fetch new data
        $weatherData = checkWeather($conn, $cityname, $apikey);
        if ($weatherData) {
            $finaldata[] = $weatherData;
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to fetch weather data"]);
            exit();
        }
    } else {
        // Use cached data
        $finaldata[] = [
            'City_Name' => $row['City_Name'],
            'Temperature' => $row['Temperature'],
            'Humidity' => $row['Humidity'],
            'Wind_speed' => $row['Wind_Speed'],
            'Wind_Direction' => $row['Wind_Direction'],
            'Pressure' => $row['Pressure'],
            'Icon_Code' => $row['Icon_Code']
        ];
    }
}

mysqli_stmt_close($stmt_select);
mysqli_close($conn);

// Return JSON response
echo json_encode($finaldata);
?>