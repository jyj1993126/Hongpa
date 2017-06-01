<?php

namespace App\Service\Migrate;

use Illuminate\Database\Migrations\MigrationCreator;

class CustomStubCreator extends MigrationCreator
{

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return __DIR__.'/stubs';
    }
}
