<?php

return [
    'general_requirements' => 'General requirements:',
    'sql_requirements' => 'Requirements for a relational database:',
    'nosql_requirements' => 'Requirements for a non-relational database:',
    
    'provide_params' => 'First, select the type of relational database and provide the parameters to connect to it. Also provide the parameters to connect to the MongoDB database.',
    'test_connections' => 'The converter will test the connections.',
    'ensure_no_changes' => 'Please make sure that no changes are made to both databases (updating data, indexes, relationships) during configuration or conversion.',

    'access_rights' => 'Access rights:',
    'sql_acces_rights' => 'the database user must have access to select data from the target database.',
    'nosql_acces_rights' => 'the MongoDB user must have the <code>readWrite</code> role or <code>dbAdmin</code> role (to delete conflicting collections, create new collections, and insert documents).',

    'foreign_keys' => 'Foreign keys:',
    'sql_foreign_keys' => 'all foreign key must be within the same database. Foreign keys to other databases will be ignored.',

    'data_consistency' => 'Data consistency:',
    'nosql_data_consistency' => 'if the table has a foreign key, then all its fields must be either <code>nullable</code> or <code>non-nullable</code> (<code>required</code>). The combination may violate the integrity of the data and the algorithm will not be able to process such a connection correctly.',

    'lack_of_important_data:' => 'Lack of important data:',
    'nosql_lack_of_important_data:' => 'make sure that the MongoDB database does not contain important data to avoid possible loss.',
    
    'avoiding_conflicts:' => 'Avoiding conflicts:',
    'nosql_avoiding_conflicts:' => 'the converter will delete collections with the same name as the relational database tables.',
];