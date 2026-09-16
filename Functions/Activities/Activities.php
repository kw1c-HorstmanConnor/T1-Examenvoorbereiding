<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/Database.php';
require_once __DIR__ . '/../Helpers/View.php';

function maple_supported_activity_languages(): array
{
    return ['en', 'de', 'fr', 'es'];
}

function maple_activity_slug(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'activity';
}

function maple_activity_image_class(string $title, int $index): string
{
    $slug = maple_activity_slug($title);
    $allowed = ['hiking', 'canoeing', 'kayaking', 'fishing', 'boat-tours', 'campfires'];

    if (in_array($slug, $allowed, true)) {
        return $slug;
    }

    return $allowed[$index % count($allowed)];
}

function maple_activity_default_image(string $slug): string
{
    $images = [
        'hiking' => 'Images/Hiking.jpg',
        'canoeing' => 'Images/canoeing.png',
        'kayaking' => 'Images/Kayaking.png',
        'fishing' => 'Images/fishing.png',
        'boat-tours' => 'Images/boat.png',
        'campfires' => 'Images/campfire.png',
    ];

    return $images[$slug] ?? 'Images/background.png';
}

function maple_activity_base_translation(array $row): array
{
    return [
        'title' => (string) ($row['Titel'] ?? ''),
        'summary' => (string) ($row['Korte_omschrijving'] ?? ''),
        'description' => (string) ($row['Omschrijving'] ?? ''),
        'extra' => (string) ($row['Extra_informatie'] ?? ''),
    ];
}

function maple_activity_from_row(array $row, int $index): array
{
    $title = (string) ($row['Titel'] ?? 'Activity');
    $price = $row['Prijs'] ?? null;
    $imageClass = maple_activity_image_class($title, $index);
    $image = trim((string) ($row['Afbeelding'] ?? ''));

    if ($image === '' || $image === 'Images/background.png') {
        $image = maple_activity_default_image($imageClass);
    }

    return [
        'id' => (int) ($row['Activiteit_id'] ?? ($index + 1)),
        'slug' => maple_activity_slug($title),
        'image' => $image,
        'imageClass' => $imageClass,
        'location' => (string) ($row['Locatie'] ?? ''),
        'duration' => (string) ($row['Duur'] ?? ''),
        'price' => $price === null || $price === '' ? null : (float) $price,
        'translations' => [],
    ];
}

function maple_activity_fallbacks(): array
{
    return [
        [
            'id' => 1,
            'slug' => 'hiking',
            'image' => 'Images/Hiking.jpg',
            'imageClass' => 'hiking',
            'location' => 'Rocky Ridge Trail',
            'duration' => '2-4 hours',
            'price' => 0.00,
            'translations' => [
                'en' => [
                    'title' => 'Hiking',
                    'summary' => 'Follow marked forest trails with wide views over the lake.',
                    'description' => 'Explore quiet pine paths, ridge viewpoints and lakeside routes at your own pace. Trails start close to camp and range from relaxed morning walks to more demanding half-day hikes.',
                    'extra' => 'Wear sturdy shoes and bring water. Trail maps are available at reception.',
                ],
                'de' => [
                    'title' => 'Wandern',
                    'summary' => 'Folge markierten Waldwegen mit weitem Blick über den See.',
                    'description' => 'Erkunde ruhige Kiefernwege, Aussichtspunkte und Routen am See in deinem eigenen Tempo. Die Wege starten nahe am Camp und reichen vom entspannten Spaziergang bis zur anspruchsvolleren Halbtagestour.',
                    'extra' => 'Trage feste Schuhe und nimm Wasser mit. Wanderkarten gibt es an der Rezeption.',
                ],
                'fr' => [
                    'title' => 'Randonnée',
                    'summary' => 'Suivez des sentiers balisés avec de belles vues sur le lac.',
                    'description' => 'Explorez des chemins forestiers calmes, des points de vue et des itinéraires au bord du lac à votre rythme. Les sentiers commencent près du camp et vont de la promenade tranquille à la randonnée d’une demi-journée.',
                    'extra' => 'Portez des chaussures solides et emportez de l’eau. Des cartes sont disponibles à la réception.',
                ],
                'es' => [
                    'title' => 'Senderismo',
                    'summary' => 'Sigue senderos señalizados con vistas amplias del lago.',
                    'description' => 'Explora caminos tranquilos entre pinos, miradores y rutas junto al lago a tu propio ritmo. Los senderos empiezan cerca del campamento y van desde paseos relajados hasta caminatas de medio día.',
                    'extra' => 'Usa calzado resistente y lleva agua. Hay mapas disponibles en recepción.',
                ],
            ],
        ],
        [
            'id' => 2,
            'slug' => 'canoeing',
            'image' => 'Images/canoeing.png',
            'imageClass' => 'canoeing',
            'location' => 'Maple Lake Dock',
            'duration' => '1-2 hours',
            'price' => 18.00,
            'translations' => [
                'en' => [
                    'title' => 'Canoeing',
                    'summary' => 'Paddle across calm water in a classic Canadian canoe.',
                    'description' => 'Take a canoe from the dock and glide over sheltered parts of Maple Lake. This easy activity is ideal for couples and small groups who want a peaceful view of the shoreline.',
                    'extra' => 'Life jackets are included and must be worn on the water.',
                ],
                'de' => [
                    'title' => 'Kanufahren',
                    'summary' => 'Paddle in einem klassischen kanadischen Kanu über ruhiges Wasser.',
                    'description' => 'Starte am Steg und gleite über die geschützten Bereiche des Maple Lake. Diese leichte Aktivität ist ideal für Paare und kleine Gruppen, die das Ufer in Ruhe erleben möchten.',
                    'extra' => 'Schwimmwesten sind inbegriffen und müssen auf dem Wasser getragen werden.',
                ],
                'fr' => [
                    'title' => 'Canoë',
                    'summary' => 'Pagayez sur une eau calme dans un canoë canadien classique.',
                    'description' => 'Partez du ponton et glissez sur les zones abritées du lac Maple. Cette activité facile convient aux couples et petits groupes qui veulent profiter tranquillement du rivage.',
                    'extra' => 'Les gilets de sauvetage sont inclus et doivent être portés sur l’eau.',
                ],
                'es' => [
                    'title' => 'Canoa',
                    'summary' => 'Rema por aguas tranquilas en una canoa canadiense clásica.',
                    'description' => 'Sal desde el muelle y deslízate por las zonas protegidas de Maple Lake. Es una actividad sencilla para parejas y grupos pequeños que quieren disfrutar de la orilla con calma.',
                    'extra' => 'Los chalecos salvavidas están incluidos y deben usarse en el agua.',
                ],
            ],
        ],
        [
            'id' => 3,
            'slug' => 'kayaking',
            'image' => 'Images/Kayaking.png',
            'imageClass' => 'kayaking',
            'location' => 'North Shore Launch',
            'duration' => '90 minutes',
            'price' => 22.50,
            'translations' => [
                'en' => [
                    'title' => 'Kayaking',
                    'summary' => 'Move faster over the lake and explore hidden coves.',
                    'description' => 'Kayaking gives you a more active way to discover Maple Lake. Paddle along quiet bays, watch for wildlife near the reeds and enjoy a flexible route guided by the weather.',
                    'extra' => 'Basic swimming ability is required. Dry bags can be borrowed at reception.',
                ],
                'de' => [
                    'title' => 'Kajakfahren',
                    'summary' => 'Bewege dich schneller über den See und entdecke versteckte Buchten.',
                    'description' => 'Mit dem Kajak entdeckst du Maple Lake auf aktive Weise. Paddle an ruhigen Buchten entlang, beobachte Tiere am Schilf und genieße eine flexible Route je nach Wetter.',
                    'extra' => 'Grundlegende Schwimmkenntnisse sind erforderlich. Packsäcke können an der Rezeption ausgeliehen werden.',
                ],
                'fr' => [
                    'title' => 'Kayak',
                    'summary' => 'Avancez plus vite sur le lac et explorez des criques cachées.',
                    'description' => 'Le kayak offre une façon plus sportive de découvrir le lac Maple. Pagayez le long de baies calmes, observez la faune près des roseaux et profitez d’un itinéraire adapté à la météo.',
                    'extra' => 'Il faut savoir nager. Des sacs étanches peuvent être empruntés à la réception.',
                ],
                'es' => [
                    'title' => 'Kayak',
                    'summary' => 'Avanza más rápido por el lago y explora calas escondidas.',
                    'description' => 'El kayak te permite descubrir Maple Lake de una forma más activa. Rema por bahías tranquilas, observa la fauna cerca de los juncos y disfruta de una ruta flexible según el clima.',
                    'extra' => 'Se requiere saber nadar. Puedes pedir bolsas impermeables en recepción.',
                ],
            ],
        ],
        [
            'id' => 4,
            'slug' => 'fishing',
            'image' => 'Images/fishing.png',
            'imageClass' => 'fishing',
            'location' => 'West Bank Pier',
            'duration' => 'Half day',
            'price' => 15.00,
            'translations' => [
                'en' => [
                    'title' => 'Fishing',
                    'summary' => 'Spend a slow morning fishing from the pier or shoreline.',
                    'description' => 'Cast your line from the west pier or find a quiet spot along the shoreline. The lake is best in the early morning, when the water is still and the camp is just waking up.',
                    'extra' => 'Bring your own permit if required. Equipment rental is available in limited numbers.',
                ],
                'de' => [
                    'title' => 'Angeln',
                    'summary' => 'Verbringe einen ruhigen Morgen beim Angeln am Steg oder Ufer.',
                    'description' => 'Wirf deine Angel am Weststeg aus oder suche dir einen ruhigen Platz am Ufer. Der See ist am frühen Morgen am schönsten, wenn das Wasser still ist und das Camp langsam erwacht.',
                    'extra' => 'Bitte bring falls nötig deine eigene Genehmigung mit. Leihausrüstung ist begrenzt verfügbar.',
                ],
                'fr' => [
                    'title' => 'Pêche',
                    'summary' => 'Passez une matinée calme à pêcher depuis le ponton ou la rive.',
                    'description' => 'Lancez votre ligne depuis le ponton ou trouvez un coin tranquille au bord de l’eau. Le lac est idéal tôt le matin, quand l’eau est calme et que le camp se réveille.',
                    'extra' => 'Apportez votre permis si nécessaire. Le matériel de location est disponible en quantité limitée.',
                ],
                'es' => [
                    'title' => 'Pesca',
                    'summary' => 'Disfruta una mañana tranquila pescando desde el muelle o la orilla.',
                    'description' => 'Lanza tu línea desde el muelle oeste o busca un lugar tranquilo en la orilla. El lago está mejor a primera hora, cuando el agua está quieta y el campamento despierta.',
                    'extra' => 'Trae tu permiso si es necesario. El alquiler de equipo está disponible en cantidades limitadas.',
                ],
            ],
        ],
        [
            'id' => 5,
            'slug' => 'boat-tours',
            'image' => 'Images/boat.png',
            'imageClass' => 'boat-tours',
            'location' => 'Main Marina',
            'duration' => '75 minutes',
            'price' => 28.00,
            'translations' => [
                'en' => [
                    'title' => 'Boat Tours',
                    'summary' => 'Join a guided lake tour with stories about the area.',
                    'description' => 'Step aboard for a relaxed trip across Maple Lake. Your guide points out mountain views, wildlife areas and local stories that help you understand the landscape around camp.',
                    'extra' => 'Tours depend on weather conditions. Please arrive ten minutes before departure.',
                ],
                'de' => [
                    'title' => 'Bootstouren',
                    'summary' => 'Nimm an einer geführten Seetour mit Geschichten aus der Umgebung teil.',
                    'description' => 'Komm an Bord und genieße eine entspannte Fahrt über Maple Lake. Dein Guide zeigt Bergblicke, Tiergebiete und lokale Geschichten rund um die Landschaft des Camps.',
                    'extra' => 'Touren sind wetterabhängig. Bitte sei zehn Minuten vor Abfahrt da.',
                ],
                'fr' => [
                    'title' => 'Excursions en bateau',
                    'summary' => 'Participez à une visite guidée du lac avec des récits sur la région.',
                    'description' => 'Montez à bord pour une sortie détendue sur le lac Maple. Votre guide présente les vues sur les montagnes, les zones de faune et des histoires locales sur le paysage du camp.',
                    'extra' => 'Les excursions dépendent de la météo. Merci d’arriver dix minutes avant le départ.',
                ],
                'es' => [
                    'title' => 'Paseos en barco',
                    'summary' => 'Únete a un tour guiado por el lago con historias de la zona.',
                    'description' => 'Sube a bordo para un recorrido relajado por Maple Lake. El guía señala vistas de montaña, zonas de fauna e historias locales que ayudan a entender el paisaje del campamento.',
                    'extra' => 'Los tours dependen del clima. Llega diez minutos antes de la salida.',
                ],
            ],
        ],
        [
            'id' => 6,
            'slug' => 'campfires',
            'image' => 'Images/campfire.png',
            'imageClass' => 'campfires',
            'location' => 'Central Fire Circle',
            'duration' => 'Evening',
            'price' => 0.00,
            'translations' => [
                'en' => [
                    'title' => 'Campfires',
                    'summary' => 'End the day around the fire with warm drinks and stories.',
                    'description' => 'Gather at the central fire circle after sunset for a relaxed camp evening. Share stories, toast marshmallows and enjoy the quiet rhythm of the forest at night.',
                    'extra' => 'Campfires only take place when local fire safety rules allow it.',
                ],
                'de' => [
                    'title' => 'Lagerfeuer',
                    'summary' => 'Beende den Tag am Feuer mit warmen Getränken und Geschichten.',
                    'description' => 'Triff dich nach Sonnenuntergang am zentralen Feuerplatz zu einem entspannten Campabend. Erzähle Geschichten, röste Marshmallows und genieße den Wald bei Nacht.',
                    'extra' => 'Lagerfeuer finden nur statt, wenn die örtlichen Brandschutzregeln es erlauben.',
                ],
                'fr' => [
                    'title' => 'Feux de camp',
                    'summary' => 'Terminez la journée autour du feu avec boissons chaudes et histoires.',
                    'description' => 'Retrouvez-vous au cercle de feu après le coucher du soleil pour une soirée détendue. Partagez des histoires, grillez des guimauves et profitez du calme de la forêt.',
                    'extra' => 'Les feux de camp ont lieu uniquement si les règles locales de sécurité incendie le permettent.',
                ],
                'es' => [
                    'title' => 'Fogatas',
                    'summary' => 'Termina el día junto al fuego con bebidas calientes e historias.',
                    'description' => 'Reúnete en el círculo central después del atardecer para una noche relajada. Comparte historias, tuesta malvaviscos y disfruta del bosque por la noche.',
                    'extra' => 'Las fogatas solo se realizan cuando las normas locales de seguridad contra incendios lo permiten.',
                ],
            ],
        ],
    ];
}

function maple_activity_load_from_database(): array
{
    $pdo = maple_pdo();
    $statement = $pdo->prepare(
        'SELECT
            a.Activiteit_id,
            a.Titel,
            a.Korte_omschrijving,
            a.Omschrijving,
            a.Afbeelding,
            a.Locatie,
            a.Duur,
            a.Prijs,
            a.Extra_informatie,
            t.Taal_code,
            t.Titel AS Vertaalde_titel,
            t.Korte_omschrijving AS Vertaalde_korte_omschrijving,
            t.Omschrijving AS Vertaalde_omschrijving,
            t.Extra_informatie AS Vertaalde_extra_informatie
        FROM Activiteiten AS a
        LEFT JOIN ActiviteitVertalingen AS t
            ON t.Activiteit_id = a.Activiteit_id
        WHERE a.Actief = 1
        ORDER BY a.Activiteit_id ASC'
    );
    $statement->execute();

    $activities = [];
    $orderedIds = [];

    foreach ($statement->fetchAll() as $row) {
        $activityId = (int) $row['Activiteit_id'];

        if (!isset($activities[$activityId])) {
            $orderedIds[] = $activityId;
            $activity = maple_activity_from_row($row, count($orderedIds) - 1);
            $baseTranslation = maple_activity_base_translation($row);

            foreach (maple_supported_activity_languages() as $language) {
                $activity['translations'][$language] = $baseTranslation;
            }

            $activities[$activityId] = $activity;
        }

        $language = strtolower((string) ($row['Taal_code'] ?? ''));

        if (in_array($language, maple_supported_activity_languages(), true)) {
            $activities[$activityId]['translations'][$language] = [
                'title' => (string) ($row['Vertaalde_titel'] ?: $row['Titel']),
                'summary' => (string) ($row['Vertaalde_korte_omschrijving'] ?: $row['Korte_omschrijving']),
                'description' => (string) ($row['Vertaalde_omschrijving'] ?: $row['Omschrijving']),
                'extra' => (string) ($row['Vertaalde_extra_informatie'] ?: ($row['Extra_informatie'] ?? '')),
            ];
        }
    }

    $result = [];

    foreach ($orderedIds as $activityId) {
        $result[] = $activities[$activityId];
    }

    return $result;
}

function maple_activities(): array
{
    try {
        $activities = maple_activity_load_from_database();

        if ($activities !== []) {
            return $activities;
        }
    } catch (Throwable $exception) {
        error_log('Activity database fallback used: ' . $exception->getMessage());
    }

    return maple_activity_fallbacks();
}

function maple_activity_translation(array $activity, string $language = 'en'): array
{
    $translations = $activity['translations'] ?? [];

    return $translations[$language] ?? $translations['en'] ?? [
        'title' => '',
        'summary' => '',
        'description' => '',
        'extra' => '',
    ];
}

function maple_activity_image_url(array $activity, string $assetBase): string
{
    $image = trim((string) ($activity['image'] ?? ''));

    if ($image === '') {
        $image = 'Images/background.png';
    }

    if (preg_match('/^(?:https?:)?\/\//', $image) || substr($image, 0, 1) === '/') {
        return $image;
    }

    return $assetBase . ltrim($image, '/');
}
