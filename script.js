
const API_URL = "a1387411d9751f10a2be3e09afc3fcb4";
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

async function checkWeather(city) {
    try {
        // Validate input
        city = city.trim();
        
        if (!city) {
            alert("Please enter a city name");
            return;
        }

        let data;
        const cacheKey = city.toLowerCase();
        
        if (navigator.onLine) {
            // Online: Fetch from API
            const response = await fetch(`${API_URL}${encodeURIComponent(city)}`);
            
            // Handle different response statuses
            if (response.status === 404) {
                alert("City not found. Please enter a valid city name.");
                searchBox.value = "";
                return;
            } else if (response.status === 401) {
                alert("API authentication failed. Please check your API key.");
                return;
            } else if (response.status === 500) {
                alert("Server error. Please try again later.");
                return;
            } else if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            data = await response.json();
            console.log("Fetched Data:", data);
            
            // Cache the data in localStorage
            try {
                localStorage.setItem(cacheKey, JSON.stringify({
                    data: data,
                    timestamp: Date.now()
                }));
            } catch (e) {
                console.warn("localStorage unavailable:", e);
            }
        } else {
            // Offline: Try to load from cache
            const cached = localStorage.getItem(cacheKey);
            if (!cached) {
                alert("No internet connection and no cached data available.");
                return;
            }
            
            const cachedData = JSON.parse(cached);
            data = cachedData.data;
            console.log("Using cached data");
        }
        
        // Extract weather data with defaults
        const {
            City_Name = "Unknown City",
            Temperature = "N/A",
            Humidity = "N/A",
            Wind_speed = "N/A",
            Wind_Direction = "N/A",
            Pressure = "N/A",
            Icon_Code = ""
        } = data[0] || {};

        // Update DOM elements
        elements.name.innerText = City_Name;
        elements.temp.innerText = `${Temperature}°C`;
        elements.humidity.innerText = `${Humidity}%`;
        elements.wind.speed.innerText = `${Wind_speed} km/h`;
        elements.wind.deg.innerText = `${Wind_Direction}°`;
        elements.pressure.innerText = `${Pressure} hPa`;

        // Update weather icon
        if (Icon_Code) {
            weatherIcon.src = `https://openweathermap.org/img/wn/${Icon_Code}@2x.png`;
            weatherIcon.alt = `Weather icon for ${City_Name}`;
        }

        // Update date and day
        updateDateTime();

    } catch (error) {
        console.error("Error fetching weather data:", error);
        alert("An error occurred while fetching weather data. Please try again.");
    }
}


function updateDateTime() {
    const now = new Date();
    
    // Format the date
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const localDate = now.toLocaleDateString('en-US', options);
    
    // Get the current day
    const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    const currentDay = days[now.getDay()];
    
    // Display in the HTML
    elements.date.innerHTML = localDate;
    elements.day.innerHTML = currentDay;
}

/**
 * Handle search button click
 */
searchBtn.addEventListener("click", () => {
    checkWeather(searchBox.value);
});

/**
 * Handle Enter key press in search box
 */
searchBox.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
        checkWeather(searchBox.value);
    }
});

// Load default city on page load
checkWeather("Guntersville");



