# SuiteImporter
A simplified importer to import large datasets quickly

Features:

- Simple field mappings through a config file
- Core & Custom fields
- Related Email Addresses
- Relate Field Relationships
- Middle Table Relationships
- Sequential imports of multiple modules
- Optionally lists unmapped fields
- Optional tool to convert list of unmapped fields to vardefs

## Use

1. Copy the contents of the /src folder into your SuiteCRM installation,
keeping the folder structure
2. Create the mapping files in src/custom/dev/SuiteImporter/mappings
(see the sample file this directory for examples)
3. Repair and rebuild SuiteCRM to make the entry point available
4. Visit http://your_crm_instance./index.php?entryPoint=importScript

Valid parameters:
- **import=lead,contact** imports leadMappings, then contactMappings
- **maxRows=1000** stops at 1000 rows
- **offset=1000** starts at row 1001
- **doNotTruncate=1** does not truncate relevant tables
- **frail=1** stops import on first sql error
- **showUnmapped=1** shows a list of those fields of the csv that are
not currently mapped

## Additional tools (WIP, need integrating)

- **fieldParser.php** builds vardefs and mappings from list of
unmapped fields
- **csv.php** Simply displays the first 100 rows of the csv