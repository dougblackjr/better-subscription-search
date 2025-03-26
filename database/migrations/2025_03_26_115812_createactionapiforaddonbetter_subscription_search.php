<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateactionapiforaddonbetterSubscriptionSearch extends Migration
{
    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        ee('Model')->make('Action', [
            'class' => 'Better_subscription_search',
            'method' => 'Api',
            'csrf_exempt' => false,
        ])->save();
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        ee('Model')->get('Action')
            ->filter('class', 'Better_subscription_search')
            ->filter('method', 'Api')
            ->delete();
    }
}
