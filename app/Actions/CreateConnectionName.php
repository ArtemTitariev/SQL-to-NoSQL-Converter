<?php
namespace App\Actions;

use Illuminate\Support\Str;

class CreateConnectionName {

    /**
     * Create unique database connectoin name
     * It is formed from: "db_" prefix + authenticated user id + 
     * database name + a string of 10 pseudorandom characters. 
     * All parts are separated by an underscore.
     * 
     * @param string $databaseName
     * 
     * @return string
     */
    public function create(string $databaseName): string {
        return 'db_' . auth()->user()->id . '_' 
            . $databaseName . '_'
            . Str::random(10);
    }
}