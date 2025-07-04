<?php
/**
 * Sample usage for EaseDoctrine
 *
 * @package EaseDoctrine
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/EaseDoctrine/i18n.php';

use EaseDoctrine\EaseDoctrine;

// Example connection params for SQLite (change as needed)
$connectionParams = [
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/../var/data.sqlite',
];

$config = [
    'entityPath' => __DIR__ . '/../src/Entity',
    'devMode' => true,
];

try {
    $easeDoctrine = new EaseDoctrine($connectionParams, $config);
    $entityManager = $easeDoctrine->getEntityManager();
    // ... use $entityManager or EaseDoctrine API ...
    echo _(
        'EaseDoctrine initialized successfully.'
    ) . PHP_EOL;
} catch (Exception $e) {
    echo sprintf(
        _('Error: %s'), $e->getMessage()
    ) . PHP_EOL;
}
