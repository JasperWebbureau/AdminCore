<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

$projectRoot = dirname(__DIR__, 4);
require $projectRoot . '/.env.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Database/Connection.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Database/PDO.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Utils/_Enum.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Database/Column/DefaultColumnLenth.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Database/Column/ColumnType.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Database/Column/Column.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Database/Table.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Autowire/Definition/PropertyDefinition.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Autowire/Definition/EntityDefinition.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Autowire/Schema/SchemaIndexDefinition.php';
require_once $projectRoot . '/flexgrid/flexgrid/src/Autowire/Schema/SchemaManager.php';

if (!isset($db['host'], $db['name'], $db['username'], $db['password'])) {
    throw new RuntimeException('Databaseconfiguratie ontbreekt in .env.php.');
}

$connection = new PDO(
    'mysql:host=' . $db['host'] . ';dbname=' . $db['name'],
    $db['username'],
    $db['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

\Flexgrid\Database\Connection::addDatabase(
    $db['name'],
    $db['host'],
    $db['username'],
    $db['password']
);
unset($db);

$columnRows = $connection->query(
    'SHOW FULL COLUMNS FROM `admin_core_number_sequence`'
)->fetchAll();
$columns = [];
foreach ($columnRows as $columnRow) {
    $columns[$columnRow['Field']] = $columnRow;
}

$expectedTypes = [
    'tenant_id' => 'varchar(64)',
    'sequence_key' => 'varchar(64)',
    'period_key' => 'varchar(32)',
    'current_value' => 'bigint',
];
foreach ($expectedTypes as $column => $typePrefix) {
    adminCoreAssert(isset($columns[$column]), 'Databasekolom ' . $column . ' ontbreekt.');
    adminCoreAssert(
        strpos(strtolower((string)$columns[$column]['Type']), $typePrefix) === 0,
        'Databasekolom ' . $column . ' heeft een onverwacht type.'
    );
    adminCoreAssert($columns[$column]['Null'] === 'NO', 'Databasekolom ' . $column . ' moet NOT NULL zijn.');
}

$indexRows = $connection->query(
    'SHOW INDEX FROM `admin_core_number_sequence` WHERE `Key_name` = \'tenant_sequence\''
)->fetchAll();
usort($indexRows, function (array $left, array $right): int {
    return (int)$left['Seq_in_index'] <=> (int)$right['Seq_in_index'];
});

adminCoreAssert(count($indexRows) === 3, 'Unique index tenant_sequence moet drie kolommen bevatten.');
adminCoreAssert(
    array_column($indexRows, 'Column_name') === ['tenant_id', 'sequence_key', 'period_key'],
    'Unique index tenant_sequence heeft een onverwachte kolomvolgorde.'
);
foreach ($indexRows as $indexRow) {
    adminCoreAssert((int)$indexRow['Non_unique'] === 0, 'Index tenant_sequence moet unique zijn.');
}

$autowireTable = new \Flexgrid\Database\Table('admin_core_number_sequence');
foreach (array_keys($expectedTypes) as $column) {
    adminCoreAssert(
        $autowireTable->hasColumn($column),
        'Flexgrid Table-metadata herkent databasekolom ' . $column . ' niet.'
    );
}
adminCoreAssert(
    $autowireTable->hasIndex('tenant_sequence'),
    'Flexgrid Table-metadata herkent index tenant_sequence niet.'
);

$entityDefinition = new \Flexgrid\Autowire\Definition\EntityDefinition();
$entityDefinition->setTableName('admin_core_number_sequence');
$entityDefinition->setClassAnnotations([
    'Entity' => ['name' => 'admin_core_number_sequence'],
    'Index' => [
        'tenant_sequence' => [
            'columns' => ['tenantId', 'sequenceKey', 'periodKey'],
            'unique' => true,
        ],
    ],
]);

foreach (
    [
        'tenantId' => ['varchar', 64],
        'sequenceKey' => ['varchar', 64],
        'periodKey' => ['varchar', 32],
        'currentValue' => ['bigint', null],
    ] as $propertyName => $databaseSettings
) {
    $property = new \Flexgrid\Autowire\Definition\PropertyDefinition();
    $property->setName($propertyName);
    $property->setColumnName($propertyName);
    $property->setIsColumn(true);
    $property->setDatabaseType($databaseSettings[0]);
    $property->setDatabaseLength($databaseSettings[1]);
    $property->addAnnotation('Column', [
        'type' => $databaseSettings[0],
        'length' => $databaseSettings[1],
        'required' => true,
    ]);
    $entityDefinition->addProperty($property);
}

$schemaManager = new \Flexgrid\Autowire\Schema\SchemaManager();
$schemaManager->synchronize([$entityDefinition]);
$schemaManager->synchronize([$entityDefinition]);

echo "AdminCore number-sequence schema test passed.\n";
