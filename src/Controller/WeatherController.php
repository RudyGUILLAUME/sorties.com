<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherController extends AbstractController
{
    public function __construct(private HttpClientInterface $client) {}

    #[Route('/meteo/{ville}/{date}', name: 'app_meteo')]
    public function getWeather(string $ville, string $date): JsonResponse
    {
        $geoUrl = sprintf('https://geocoding-api.open-meteo.com/v1/search?name=%s&count=1', urlencode($ville));
        $geoResponse = $this->client->request('GET', $geoUrl)->toArray();

        if (empty($geoResponse['results'])) {
            return new JsonResponse(['error' => 'Ville non trouvée'], 404);
        }

        $lat = $geoResponse['results'][0]['latitude'];
        $lon = $geoResponse['results'][0]['longitude'];

        $meteoUrl = sprintf(
            'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,weathercode&timezone=Europe/Paris&start_date=%s&end_date=%s',
            $lat,
            $lon,
            $date,
            $date
        );

        $weatherResponse = $this->client->request('GET', $meteoUrl)->toArray();

        if (empty($weatherResponse['daily'])) {
            return new JsonResponse(['error' => 'Aucune donnée météo disponible'], 404);
        }

        $daily = $weatherResponse['daily'];

        $codes = [
            0 => ['Ciel dégagé', '☀️'],
            1 => ['Principalement clair', '🌤️'],
            2 => ['Partiellement nuageux', '⛅'],
            3 => ['Couvert', '☁️'],
            45 => ['Brouillard', '🌫️'],
            48 => ['Brouillard givrant', '🌫️'],
            51 => ['Bruine légère', '🌦️'],
            53 => ['Bruine modérée', '🌦️'],
            55 => ['Bruine dense', '🌧️'],
            61 => ['Pluie légère', '🌧️'],
            63 => ['Pluie modérée', '🌧️'],
            65 => ['Pluie forte', '🌧️'],
            71 => ['Neige légère', '❄️'],
            73 => ['Neige modérée', '❄️'],
            75 => ['Neige forte', '❄️'],
            95 => ['Orage', '⛈️'],
            99 => ['Orage violent', '🌩️'],
        ];

        $code = $daily['weathercode'][0] ?? null;
        $weatherText = $codes[$code][0] ?? 'Conditions inconnues';
        $weatherIcon = $codes[$code][1] ?? '❔';

        return new JsonResponse([
            'ville' => $ville,
            'date' => $date,
            'temperature_max' => $daily['temperature_2m_max'][0] ?? null,
            'temperature_min' => $daily['temperature_2m_min'][0] ?? null,
            'precipitation' => $daily['precipitation_sum'][0] ?? null,
            'weather_text' => $weatherText,
            'weather_icon' => $weatherIcon,
        ]);
    }
}
