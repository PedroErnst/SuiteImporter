<?php

$path = __DIR__ . '/../../../sfa/';
$fileName = $_GET['file'];

$file = fopen($path . $fileName, 'rb');

echo '<table style="border: 1px solid black;">';

$rows = 0;
while ($row = fgetcsv($file)) {
    printRow($row);
    if ($rows > 100) {
        break;
    }
    $rows++;
}

echo '</table>';

function printRow($row)
{
    echo '<tr style="border: 1px solid black;">';
    foreach ($row as $cell) {
        printCell($cell);
    }
    echo '</tr>';
}

function printCell($cell)
{
    echo '<td style="border: 1px solid black;">';
    echo substr($cell, 0, 50);
    echo '</td>';
}