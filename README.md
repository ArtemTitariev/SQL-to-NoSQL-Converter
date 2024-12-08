# Web-based system for migrating data from a relational database to a non-relational database

The web-based system allows to convert a relational database to a non-relational one. The main task is to migrate data from one database to another. 
The user can customize the conversion process, namely:
- select the necessary tables;
- change data types;
- and set up relationships between entities.

Additionally, a user profile has been implemented. For convenience, a bilingual interface is provided. English and Ukrainian are supported.

#### Supported relational databases:
- MySQL;
- PostgreSQL.

#### Supported non-relational database - MongoDB.

## Important!

To perform the migration (conversion), the system will need to create connections to the relational and non-relational databases. The parameters are entered through the web interface at the stage of creating the conversion. 
<strong>These parameters will be stored in encrypted form and will be used only during the cofiguration and data migration process</strong>.


## Installation:

1. Install dependencies, setup enviroment:

    ```
    composer install
    npm install
    cp .env.example .env
    ```

3. Create the necessary relational database:

    ```
    php artisan db
    create database sql_to_nosql_converter
    ```

4. Run the initial migrations:

   ```
   php artisan migrate
   ```
5. Configure the web server.

6. Set up and configure supervisor on your machine to work with queues.

7. Start Reverb server.


### Queues used:

- `read_schema`: a queue for analyzing (“reading”) the relational database schema;
- `process_relationships`: a queue for the initial processing of relationships;
- `etl_operations`: a queue for performing ETL operations;
- `default`: a queue for emails and sending events via webokets.
