<?php

namespace TripleNERDscore\BetterSubscriptionSearch\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Index extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'index';

    /**
     * @var string
     */
    protected $cp_page_title = 'Index';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'Index');

        $action = ee('Model')->get('Action')->filter('class', 'Better_subscription_search')->filter('method', 'Api')->first();
        $members = ee('Model')->get('Member')->all();
        $status = ee('Model')->get('Status')->all();

        $memberIds = $statusIds = [];

        foreach ($members as $member) {
            $memberIds[] = [
                'id' => $member->member_id,
                'name' => $member->username,
            ];
        }
        
        foreach ($status as $s) {
            $statusIds[] = $s->status;
        }

        $variables = [
            'action_id' => $action->action_id,
            'members' => $memberIds,
            'statuses' => $statusIds,
        ];

        $this->setBody('Index', $variables);

        return $this;
    }
}
