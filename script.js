// Configuration
const API_KEY = "a1387411d9751f10a2be3e09afc3fcb4"; // Your OpenWeather API key
const BASE_URL = "https://api.openweathermap.org/data/2.5/weather";

// DOM Elements
const searchBox = document.querySelector(".search input");
const searchBtn = document.querySelector(".search button");
const weatherIcon = document.querySelector(".weather-icon");

const elements = {
    name: document.querySelector(".city"),
    temp: document.querySelector(".temp"),
    humidity: document.querySelector(".humidity"),
    wind: {
        speed: document.querySelector(".wind"),
        deg: document.querySelector(".wind-direction")
    },
    pressure: document.querySelector(".pressure"),
    date: document.querySelector(".date"),
    day: document.querySelector(".day")
};

/**
 * Fetch and display weather data for a city
 */
async function checkWeather(city) {
    try {
        city = city.trim();
        
        if (!city) {
            alert("Please enter a city name");
            return;
        }

        const response = await fetch(
            `${BASE_URL}?q=${encodeURIComponent(city)}&appid=${API_KEY}&units=metric`
        );
        
        if (!response.ok) {
            if (response.status === 404) {
                alert("City not found. Please enter a valid city name.");
            } else if (response.status === 401) {
                alert("API authentication failed.");
            } else {
                alert("Error fetching weather data. Please try again.");
            }
            searchBox.value = "";
            return;
        }
        
        const data = await response.json();
        
        // Update DOM with weather data
        elements.name.innerText = data.name;
        elements.temp.innerText = `${Math.round(data.main.temp)}°C`;
        elements.humidity.innerText = `${data.main.humidity}%`;
        elements.wind.speed.innerText = `${data.wind.speed} km/h`;
        elements.wind.deg.innerText = `${data.wind.deg || 0}°`;
        elements.pressure.innerText = `${data.main.pressure} hPa`;

        // Update weather icon
        if (data.weather && data.weather[0]) {
            weatherIcon.src = `https://openweathermap.org/img/wn/${data.weather[0].icon}@2x.png`;
            weatherIcon.alt = `Weather icon for ${data.name}`;
        }

        // Update date and day
        updateDateTime();

    } catch (error) {
        console.error("Error fetching weather data:", error);
        alert("An error occurred while fetching weather data. Please try again.");
    }
}

/**
 * Update the current date and day display
 */
function updateDateTime() {
    const now = new Date();
    
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const localDate = now.toLocaleDateString('en-US', options);
    
    const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    const currentDay = days[now.getDay()];
    
    elements.date.innerHTML = localDate;
    elements.day.innerHTML = currentDay;
}

// Event listeners
searchBtn.addEventListener("click", () => {
    checkWeather(searchBox.value);
});

searchBox.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
        checkWeather(searchBox.value);
    }
});

// Load default city on page load
checkWeather("Guntersville");
