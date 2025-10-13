// public/js/meteo.js

document.addEventListener('DOMContentLoaded', async () => {
    // Ces variables seront injectées par Twig
    const widget = document.getElementById('meteo-widget');
    const ville = widget.dataset.ville;
    const date = widget.dataset.date;

    const url = `/meteo/${encodeURIComponent(ville)}/${date}`;
    console.log('Fetching météo:', url);

    try {
        const res = await fetch(url);
        const data = await res.json();
        console.log('Data reçue:', data);

        if (data.error) {
            widget.innerHTML = `<p>${data.error}</p>`;
            return;
        }

        widget.innerHTML = `
            <div style="font-size: 2.5rem;">${data.weather_icon}</div>
            <p><strong>${data.weather_text}</strong></p>
            <p>🌡️ <strong>Max :</strong> ${data.temperature_max}°C | <strong>Min :</strong> ${data.temperature_min}°C</p>
            <p>💧 <strong>Précipitations :</strong> ${data.precipitation} mm</p>
        `;
    } catch (e) {
        console.error(e);
        widget.innerHTML = `<p>Impossible de charger la météo.</p>`;
    }
});
