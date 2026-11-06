const iconMap: Record<string, string> = {
  sun: 'mdi-weather-sunny',
  sunny: 'mdi-weather-sunny',
  clear: 'mdi-weather-sunny',
  'clear-day': 'mdi-weather-sunny',
  'clear-night': 'mdi-weather-night',
  'cloud-sun': 'mdi-weather-partly-cloudy',
  'partly-cloudy': 'mdi-weather-partly-cloudy',
  'partly_cloudy': 'mdi-weather-partly-cloudy',
  'partly-sunny': 'mdi-weather-partly-cloudy',
  cloud: 'mdi-weather-cloudy',
  cloudy: 'mdi-weather-cloudy',
  overcast: 'mdi-weather-cloudy',
  'cloud-rain': 'mdi-weather-partly-rainy',
  'light-rain': 'mdi-weather-partly-rainy',
  light_rain: 'mdi-weather-partly-rainy',
  drizzle: 'mdi-weather-partly-rainy',
  rain: 'mdi-weather-rainy',
  rainy: 'mdi-weather-rainy',
  'heavy-rain': 'mdi-weather-pouring',
  heavy_rain: 'mdi-weather-pouring',
  pouring: 'mdi-weather-pouring',
  snow: 'mdi-weather-snowy',
  snowy: 'mdi-weather-snowy',
  sleet: 'mdi-weather-snowy-rainy',
  'snow-rain': 'mdi-weather-snowy-rainy',
  thunderstorm: 'mdi-weather-lightning-rainy',
  thunder: 'mdi-weather-lightning',
  lightning: 'mdi-weather-lightning',
  storm: 'mdi-weather-lightning-rainy',
  fog: 'mdi-weather-fog',
  foggy: 'mdi-weather-fog',
  mist: 'mdi-weather-fog',
  haze: 'mdi-weather-hazy',
  windy: 'mdi-weather-windy',
  hail: 'mdi-weather-hail',
  tornado: 'mdi-weather-tornado'
}

const colorMap: Record<string, string> = {
  sun: '#F9A825',
  sunny: '#F9A825',
  clear: '#F9A825',
  'clear-day': '#F9A825',
  'clear-night': '#5C6BC0',
  'cloud-sun': '#78909C',
  'partly-cloudy': '#78909C',
  'partly_cloudy': '#78909C',
  'partly-sunny': '#78909C',
  cloud: '#90A4AE',
  cloudy: '#90A4AE',
  overcast: '#90A4AE',
  'cloud-rain': '#42A5F5',
  'light-rain': '#42A5F5',
  light_rain: '#42A5F5',
  drizzle: '#42A5F5',
  rain: '#1565C0',
  rainy: '#1565C0',
  'heavy-rain': '#0D47A1',
  heavy_rain: '#0D47A1',
  pouring: '#0D47A1',
  snow: '#90CAF9',
  snowy: '#90CAF9',
  sleet: '#80DEEA',
  'snow-rain': '#80DEEA',
  thunderstorm: '#424242',
  thunder: '#616161',
  lightning: '#616161',
  storm: '#424242',
  fog: '#B0BEC5',
  foggy: '#B0BEC5',
  mist: '#B0BEC5',
  haze: '#CFD8DC',
  windy: '#4FC3F7',
  hail: '#80CBC4',
  tornado: '#37474F'
}

export function getWeatherIcon(icon: string): string {
  const key = (icon ?? '').toLowerCase().trim()
  return iconMap[key] ?? 'mdi-weather-cloudy'
}

export function getWeatherIconColor(icon: string): string {
  const key = (icon ?? '').toLowerCase().trim()
  return colorMap[key] ?? '#90A4AE'
}
