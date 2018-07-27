<?php

set_time_limit(0);

require_once __DIR__ . '/CSVImporter.php';

$importModules = explode(',', $_GET['import']);

foreach ($importModules as $module) {
    $importer = new CSVImporter();
    $importer->runImport($module);
}
