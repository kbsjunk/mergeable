<?php namespace Kitbs\Mergeable;

use Illuminate\Database\Schema\Blueprint;

class MergeableBlueprint {

    /**
     * Add a "merged at" timestamp and "merged to" foreign key for the table.
     *
     * @return void
     */
    public function mergeable(Blueprint &$table)
    {
        $table->timestamp('merged_at')->nullable();
        $table->integer('merged_to', false, true)->nullable();

        // return $table;
    }

    /**
    * Indicate that the "merged at" and "merged to" columns should be dropped.
    *
    * @return void
    */
    public function dropMergeable(Blueprint &$table)
    {
        $table->dropColumn('merged_at');
        $table->dropColumn('merged_to');

        // return $table;
    }

}