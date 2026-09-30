<?php
declare(strict_types=1);

require_once __DIR__ . '/../Facilities/Facilities.php';

function maple_admin_facility_values_from_post($value): array
{
    return maple_facility_values_from_post($value);
}

function maple_admin_save_accommodation_with_facilities(PDO $pdo, ?int $huisId, array $values, array $facilityValues): int
{
    $facilitiesCsv = maple_facility_csv($facilityValues);

    try {
        $pdo->beginTransaction();

        if ($huisId === null) {
            $statement = $pdo->prepare('
                INSERT INTO accomodaties (Huis_naam, Locatie, PPN, Voorzieningen, `Max`, Omschrijving, Afbeelding)
                VALUES (:name, :location, :price, :facilities, :maximum, :description, :image)
            ');
            $statement->execute([
                'name' => $values['name'],
                'location' => $values['location'],
                'price' => $values['price'],
                'facilities' => $facilitiesCsv,
                'maximum' => $values['maximum'],
                'description' => $values['description'],
                'image' => $values['image'] ?? null,
            ]);
            $huisId = (int) $pdo->lastInsertId();
        } else {
            $statement = $pdo->prepare('
                UPDATE accomodaties
                SET Huis_naam = :name,
                    Locatie = :location,
                    PPN = :price,
                    Voorzieningen = :facilities,
                    `Max` = :maximum,
                    Omschrijving = :description,
                    Afbeelding = :image
                WHERE Huis_id = :huis_id
            ');
            $statement->execute([
                'name' => $values['name'],
                'location' => $values['location'],
                'price' => $values['price'],
                'facilities' => $facilitiesCsv,
                'maximum' => $values['maximum'],
                'description' => $values['description'],
                'image' => $values['image'] ?? null,
                'huis_id' => $huisId,
            ]);
        }

        $pdo->commit();

        return $huisId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}
