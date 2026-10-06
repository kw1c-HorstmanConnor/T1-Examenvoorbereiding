<?php
declare(strict_types=1);

require_once __DIR__ . '/../Facilities/Facilities.php';
require_once __DIR__ . '/../Accommodations/Accommodations.php';

function maple_admin_facility_values_from_post($value): array
{
    return maple_facility_values_from_post($value);
}

function maple_admin_accommodation_identifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

/**
 * @param int|null $huisId
 */
function maple_admin_save_accommodation_with_facilities(PDO $pdo, $huisId, array $values, array $facilityValues): int
{
    $facilitiesCsv = maple_facility_csv($facilityValues);
    $columns = maple_accommodations_columns($pdo);
    $descriptionColumn = maple_accommodations_find_column($columns, ['Omschrijving', 'Omschr']);

    $params = [
        'name' => $values['name'],
        'location' => $values['location'],
        'price' => $values['price'],
        'facilities' => $facilitiesCsv,
        'maximum' => $values['maximum'],
    ];

    if ($descriptionColumn !== '') {
        $params['description'] = $values['description'];
    }


    try {
        $pdo->beginTransaction();

        if ($huisId === null) {
            $insertColumns = ['Huis_naam', 'Locatie', 'PPN', 'Voorzieningen', 'Max'];
            $insertValues = [':name', ':location', ':price', ':facilities', ':maximum'];

            if ($descriptionColumn !== '') {
                $insertColumns[] = $descriptionColumn;
                $insertValues[] = ':description';
            }


            $statement = $pdo->prepare(
                'INSERT INTO accomodaties ('
                . implode(', ', array_map('maple_admin_accommodation_identifier', $insertColumns))
                . ') VALUES ('
                . implode(', ', $insertValues)
                . ')'
            );
            $statement->execute($params);
            $huisId = (int) $pdo->lastInsertId();
        } else {
            $updateColumns = [
                ['Huis_naam', ':name'],
                ['Locatie', ':location'],
                ['PPN', ':price'],
                ['Voorzieningen', ':facilities'],
                ['Max', ':maximum'],
            ];

            if ($descriptionColumn !== '') {
                $updateColumns[] = [$descriptionColumn, ':description'];
            }


            $setParts = [];
            foreach ($updateColumns as $column) {
                $setParts[] = maple_admin_accommodation_identifier($column[0]) . ' = ' . $column[1];
            }

            $params['huis_id'] = $huisId;
            $statement = $pdo->prepare(
                'UPDATE accomodaties SET '
                . implode(', ', $setParts)
                . ' WHERE Huis_id = :huis_id'
            );
            $statement->execute($params);
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
