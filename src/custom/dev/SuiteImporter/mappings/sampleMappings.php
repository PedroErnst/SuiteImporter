<?php

// module name in SuiteCRM as specified in modules/TheModule/TheModule.php
$module = 'Contacts';

// the table name in the database
$table = 'contacts';

// the location of the csv file to be imported
$path = __DIR__ . '/../../../../import/Contacts.csv';

/**
 * The keys define the fields in the CSV, the values are the fields in the CRM
 *
 * CoreFiels will be imported into the specified table
 * Custom Fields will be imported into the _cstm table
 */
$coreFields = [
    'Id' => 'id_c',
    'CreatedDate' => 'date_entered',
    'LastModifiedDate' => 'date_modified',
    'IsDeleted' => 'deleted',
    'Name' => 'name',
];

$customFields = [
    'Amount' => 'amount_c',
];

// These fields from the CSV will be imported as EmailBeans and related back to the main bean
$emailFields = [

];

/**
 * These relationships will be built. The "field" is expected to contain the ID of the related record
 */
$relationships = [

    'ParentId' => [
        'field' => 'ParentId',
        'type' => 'relate',
        'other_table' => 'cases',
        'other_table_id' => 'sf_id_c',
        'this_id' => 'case_id',
    ],

    'AccountId' => [
        'field' => 'AccountId',
        'type' => 'middle_table',
        'middle_table' => 'accounts_contacts',
        'other_table' => 'accounts',
        'other_table_id' => 'sf_id_c',
        'this_id' => 'contact_id',
        'other_id' => 'account_id',
    ],
];

/**
 * Class AOP_Case_UpdatesMappingTransformation
 *
 * This class is used to apply modifications while importing.
 */
class ContactsMappingTransformation
{
    public static function apply($row, $field)
    {
        if ($field === 'FieldToModify') {
            return $row['FieldToModify'] / 2;
        }
        return $row[$field];
    }
}