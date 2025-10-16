document.addEventListener('DOMContentLoaded', async () => {
    const widget = document.getElementById('meteo-widget');
    if (!widget) return;

    const ville = encodeURIComponent(widget.dataset.ville);
    const date = widget.dataset.date;
    const url = `/meteo/${ville}/${date}`;

    try {
        const res = await fetch(url);

        if (!res.ok) {
            widget.innerHTML = `<p class="text-red-500">Erreur serveur (${res.status})</p>`;
            return;
        }

        const data = await res.json();
        console.log("📦 Données reçues :", data);

        if (data.error) {
            widget.innerHTML = `<p class="text-red-500">${data.error}</p>`;
            return;
        }

        const temperatureMax = Math.round(data.temperature_max);
        const temperatureMin = Math.round(data.temperature_min);
        const condition = data.weather_text;
        const icon = data.weather_icon;
        const villeNom = data.ville;

        widget.innerHTML = `
            <span class="text-6xl">${icon}</span>
            <p class="text-4xl font-bold text-text-light dark:text-text-dark">${temperatureMax}°C</p>
            <p class="text-subtle-light dark:text-subtle-dark">${condition}</p>
           

            <div class="w-full border-t border-subtle-light/20 dark:border-subtle-dark/20 pt-4 flex justify-between gap-6 mt-4">
    <div class="flex flex-col items-center">
        <p class="text-sm font-bold">Min</p>
        <p class="text-sm text-subtle-light dark:text-subtle-dark">${temperatureMin}°C</p>
    </div>
    <div class="flex flex-col items-center">
        <p class="text-sm font-bold">Max</p>
        <p class="text-sm text-subtle-light dark:text-subtle-dark">${temperatureMax}°C</p>
    </div>
    <div class="flex flex-col items-center">
        <p class="text-sm font-bold">Pluie</p>
        <p class="text-sm text-subtle-light dark:text-subtle-dark">${data.precipitation} mm</p>
    </div>
</div>

        `;
    } catch (error) {
        console.error('Erreur lors du chargement de la météo :', error);
        widget.innerHTML = `<p class="text-red-500">Erreur de chargement de la météo.</p>`;
    }
});
