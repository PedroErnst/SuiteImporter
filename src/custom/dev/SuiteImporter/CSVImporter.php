<?php

class CSVImporter
{
    private $import;
    private $table;
    private $module;
    private $path;
    private $coreFields;
    private $customFields;
    private $relationships;
    private $rows = 0;
    private $header = [];
    private $bean;
    private $maxReached;
    private $emailFields = [];
    private $parameters = [
        'maxRows' => 0,
        'doNotTruncate' => 1,
        'frail' => 0,
        'offset' => 0,
        'showUnmapped' => 0,
    ];
    private $nullTypes = [
        'date',
        'datetime',
        'datetimecombo',
    ];
    private $zeroTypes = [
        'int',
        'bool',
        'currency',
    ];


    public function __construct()
    {
        $this->enableErrors();
    }

    /**
     *
     */
    public function enableErrors()
    {
        ob_implicit_flush();
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

    /**
     *
     */
    public function loadMappings()
    {
        $path = '';
        $module = '';
        $table = '';
        $coreFields = '';
        $customFields = '';
        $relationships = [];
        $emailFields = [];
        require_once __DIR__ . '/mappings/' . $this->import . 'Mappings.php';
        $this->path = $path;
        $this->module = $module;
        $this->table = $table;
        $this->coreFields = $coreFields;
        $this->coreFields['id'] = 'id';
        $this->relationships = $relationships;
        $this->emailFields = $emailFields;
        unset($this->coreFields['']);
        $this->customFields = $customFields;
        $this->customFields['id_c'] = 'id_c';
        unset($this->customFields['']);
    }

    /**
     *
     */
    public function loadParameters()
    {
        foreach ($this->parameters as $name => $value) {
            if (!empty($_REQUEST[$name])) {
                $this->parameters[$name] = $_REQUEST[$name];
            }
        }
    }

    /**
     * @param $import
     */
    public function runImport($import)
    {
        try {
            $this->import = $import;
            $this->init();

            $file = fopen($this->path, 'rb');
            $this->header = fgetcsv($file);
            $fieldsPerRow = count($this->header);

            $this->showUnmapped();

            $failedRows = 0;
            while ($row = fgetcsv($file)) {
                if ($this->parameters['offset'] !== 0) {
                    $this->parameters['offset']--;
                    continue;
                }

                $this->rows++;
                if (count($row) !== $fieldsPerRow) {
                    echo "<br />Row {$this->rows} has faulty data (incorrect number of fields)<br />";
                    $failedRows++;
                    continue;
                }

                try {
                    $this->processRow($row);
                }
                catch (Exception $e) {
                    echo "<br />Row {$this->rows} has faulty data: {$e->getMessage()} <br />";
                    $failedRows++;
                    if ($this->parameters['frail'] !== 0) {
                        $this->maxReached = true;
                    }
                }

                if ($this->parameters['maxRows'] && $this->rows >= $this->parameters['maxRows']) {
                    $this->maxReached = true;
                    break;
                }
            }

            fclose($file);
        }
        catch (Exception $e) {
            echo "<br /><br /> " . $e->getMessage();
        }

        $successRows = $this->rows - $failedRows;
        echo "<br />Total rows: <br />";
        echo "Inserted: $successRows<br />";
        echo "Failed: $failedRows<br />";
    }

    /**
     *
     */
    private function showUnmapped()
    {
        if (!$this->parameters['showUnmapped']) {
            return;
        }

        echo "Unmapped fields:<br /><br />";
        foreach ($this->header as $fieldName) {
            if (!array_key_exists($fieldName, $this->coreFields)
                && !array_key_exists($fieldName, $this->customFields)) {
                echo "$fieldName<br />";
            }
        }
    }

    /**
     * @throws Exception
     */
    private function init()
    {
        $this->maxReached = false;
        $this->rows = 0;
        $this->loadMappings();
        $this->loadParameters();
        if (empty($this->parameters['doNotTruncate'])) {
            $this->truncateTables();
        }
        $this->bean = BeanFactory::newBean($this->module);

        if (!$this->bean) {
            throw new Exception('Bean name invalid: ' . $this->module);
        }

        echo "Importing $this->path <br /><br />";
    }

    /**
     * @param $row
     * @throws Exception
     */
    public function processRow($row)
    {
        $row = $this->addHeaderToRow($row);
        $this->insertRow($row);

        echo ".";
        ob_flush();
        flush();
        echo str_pad('',4096);

        if (!($this->rows % 100)) {
            echo $this->rows . " rows inserted<br />";
        }
    }

    /**
     * @param $row
     * @return array
     */
    public function addHeaderToRow($row)
    {
        $result = [];
        foreach ($row as $key => $val) {
            $result[$this->header[$key]] = $val;
        }
        return $result;
    }

    /**
     *
     */
    public function truncateTables()
    {
        global $db;
        $db->query("TRUNCATE {$this->table}");
        $db->query("TRUNCATE {$this->table}_cstm");
        echo "Truncated {$this->table} and {$this->table}_cstm <br />";

        foreach ($this->relationships as $relationship) {
            if ($relationship['type'] === 'middle_table') {
                $db->query("TRUNCATE {$relationship['middle_table']}");
                echo "Truncated {$relationship['middle_table']} <br />";
            }
        }
    }

    /**
     * @param $row
     * @throws Exception
     */
    public function insertRow($row)
    {
        $id = create_guid();

        $row['id'] = $id;
        $row['id_c'] = $id;

        $this->insertValuesIntoTable($row, $this->coreFields, $this->table);

        if (count($this->customFields) > 1) {
            $this->insertValuesIntoTable($row, $this->customFields, $this->table . '_cstm');
        }

        foreach ($this->emailFields as $key => $field) {
            $this->createEmail($id, $row[$field], $key);
        }

        foreach ($this->relationships as $relationship) {
            $this->insertRelationship($row, $relationship);
        }
    }

    /**
     * @param $id
     * @param $emailAddress
     * @param $key
     * @throws Exception
     */
    private function createEmail($id, $emailAddress, $key)
    {
        global $db;

        $emailId = create_guid();

        $sql = 'INSERT INTO email_addresses (id, email_address, email_address_caps) ' .
            'VALUES (' . $db->quoted($emailId) . ',' . $db->quoted($emailAddress) . ','
            . $db->quoted(strtoupper($emailAddress)) . ')';

        $this->runSql($sql);

        $sql = 'INSERT INTO email_addr_bean_rel (id, email_address_id, bean_id, bean_module, primary_address) ' .
            'VALUES (' . $db->quoted(create_guid()) . ',' . $db->quoted($emailId) . ',' . $db->quoted($id)
            . ',' . $db->quoted($this->module) . ',' . ($key === 0 ? 1 : 0) . ')';

        $this->runSql($sql);
    }

    /**
     * @param $row
     * @param $relationship
     * @throws Exception
     */
    private function insertRelationship($row, $relationship)
    {
        global $db;

        $subSql = 'SELECT id FROM ' . $relationship['other_table'] . ' WHERE '
            . $relationship['other_table_id'] . " ='" . $row[$relationship['field']] . "' LIMIT 1";

        $subRes = $this->runSql($subSql);
        if ($subRow = $db->fetchRow($subRes)) {
            switch ($relationship['type']) {
                case 'middle_table':
                    $sql = 'INSERT INTO ' . $relationship['middle_table']
                        . ' (id,' . $relationship['this_id'] . ',' . $relationship['other_id']
                        . ') VALUES ('
                        . $db->quoted(create_guid()) . ',' . $db->quoted($row['id']) . ',' . $db->quoted($subRow['id'])
                        . ')';
                    break;
                case 'relate':
                    $sql = 'UPDATE ' . $this->table . ' SET ' . $relationship['this_id'] . '='
                        . $db->quoted($subRow['id']) . ' WHERE id = ' . $db->quoted($row['id']);
                    break;
            }

            $this->runSql($sql);
        }

    }

    /**
     * @param $row
     * @param $fields
     * @param $table
     * @throws Exception
     */
    public function insertValuesIntoTable($row, $fields, $table)
    {
        $sql = "INSERT INTO $table (";
        foreach ($fields as $key => $field) {
            $sql .= "$field,";
        }
        $sql = rtrim($sql, ',');

        $sql .= ") VALUES (";
        $className = $this->module . 'MappingTransformation';
        foreach ($fields as $key => $field) {
            if (class_exists($className)) {
                $value = $className::apply($row, $key);
            } else {
                $value = $row[$key];
            }
            $sql .= $this->processField($field, $value) . ',';
        }
        $sql = rtrim($sql, ',');

        $sql .= ")";

        $this->runSql($sql);
    }

    /**
     * @param $fieldName
     * @param $value
     * @return string
     */
    private function processField($fieldName, $value)
    {
        global $db, $app_list_strings;

        if (!isset($this->bean->field_defs[$fieldName])) {
            return $db->quoted(utf8_encode($value));
        }
        $def = $this->bean->field_defs[$fieldName];

        if ($value === '') {
            return $this->returnNullValue($def['type'], $value);
        }

        if ($def['type'] === 'enum') {
            $value = array_search($value, $app_list_strings[$def['options']], false);
        }

        if (!is_numeric($value) && in_array($def['type'], $this->zeroTypes, false)) {
            return 0;
        }

        return $db->quoted(utf8_encode($value));
    }

    /**
     * @param $type
     * @param $value
     * @return int|string
     */
    private function returnNullValue($type, $value)
    {
        global $db;

        if (in_array($type, $this->nullTypes, false)) {
            return 'NULL';
        }

        if (in_array($type, $this->zeroTypes, false)) {
            return 0;
        }

        return $db->quoted(utf8_encode($value));
    }

    /**
     * @param $sql
     * @return bool|resource
     * @throws Exception
     */
    private function runSql($sql)
    {
        global $db;

        $result = $db->query($sql);

        if (!$result) {
            throw new Exception('SQL failed: ' . $sql . '<br /><br /> Error: ' . $db->lastDbError());
        }

        return $result;
    }
}