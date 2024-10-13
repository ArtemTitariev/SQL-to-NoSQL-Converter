<?php

return [
    'store_connection_params_policy' => 'The parameters for connecting to databases will be stored in encrypted form. The application will use them only during the conversion process. After the conversion is complete, you can delete them.',
    'unsupported_data_type' => "Data type :dataTypeName (full name: :dataType) of the :driver database is not supported.",
    'select_tables_policy' => "At this step, you can select the tables whose data you want to convert. For most columns, you can change the data type as needed. Please note: you can set up the organization of relationships in the next step.",
    'adjust_relationships_policy' => "The system configured relationships between collections. At this step, you can make adjustments to the formed relationships.",
];