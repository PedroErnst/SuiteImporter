<?php

if (!empty($_REQUEST['fieldList'])) {
    $fieldsRaw = str_replace([',', ';'], ' ', $_REQUEST['fieldList']);
    $fields = explode(' ', trim($fieldsRaw, ' '));

    echo 'VARDEFS <br />';

    foreach ($fields as $field) {
        $suiteName = makeSuiteFieldName($field);
        echo '$dictionary[$module][\'fields\'][\'' . $suiteName . '\'] = [';
        echo '\'name\' => \'' . $suiteName . '\',';
        echo '\'source\' => \'custom_fields\',';
        echo '\'type\' => \'varchar\',';
        echo '\'vname\' => \'LBL_' . strtoupper($suiteName) . '\',';
        echo '];';
    }

    echo '<br /><br />MAPPINGS <br />';

    foreach ($fields as $field) {
        $suiteName = makeSuiteFieldName($field);
        echo "<br />'$field' => '$suiteName',";
    }
}

function makeSuiteFieldName($name) {
    if (substr(strtolower($name), -2) !== '_c') {
        $name .= '_c';
    }
    return strtolower(str_replace('__', '_', $name));
}

?>

<form>
    <input type="text" name="fieldList" value="<?php echo $_REQUEST['fieldList'] ?: '' ?>">
    <input type="submit" value="Submit">

</form>
