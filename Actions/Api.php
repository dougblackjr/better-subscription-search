<?php

namespace TripleNERDscore\BetterSubscriptionSearch\Actions;

use ExpressionEngine\Service\Addon\Controllers\Action\AbstractRoute;

class Api extends AbstractRoute
{
    public function process()
    {
        $db = ee()->db;
        $table = 'cartthrob_subscriptions';

        $page   = (int) ee()->input->get_post('page') ?: 1;
        $limit  = (int) ee()->input->get_post('limit') ?: 10;
        $search = ee()->input->get_post('search', true);
        $member = ee()->input->get_post('member', true);
        $status = ee()->input->get_post('status', true);
        $offset = ($page - 1) * $limit;

        // Start query with join
        $db->from("$table AS s")
            ->select('s.id, s.name, s.status, s.order_id, s.member_id, s.last_bill_date, s.next_bill_date, m.screen_name')
            ->join('members AS m', 's.member_id = m.member_id', 'left');

        // Apply filters
        if (!empty($member)) {
            $db->where('s.member_id', $member);
        }

        if (!empty($status)) {
            $db->where('s.status', $status);
        }

        // Smart search logic
        if (!empty($search)) {
            $db->group_start()
                ->like('s.id', $search)
                ->or_like('s.order_id', $search)
                ->or_like('s.name', $search)
                ->or_like('s.description', $search)
                ->group_end();
        }

        // Apply pagination (+1 to detect if more records exist)
        $db->order_by('s.id', 'DESC')
            ->limit($limit + 1)
            ->offset($offset);

        $query = $db->get();
        $results = $query->result_array();

        // Process rows
        $subscriptions = [];
        foreach (array_slice($results, 0, $limit) as $row) {
            $subscriptions[] = [
                'id'           => $row['id'],
                'last_rebill'  => $row['last_bill_date'] ? ee()->localize->human_time($row['last_bill_date']) : '',
                'next_rebill'  => $row['next_bill_date'] ? ee()->localize->human_time($row['next_bill_date']) : '',
                'name'         => $row['name'],
                'member'       => $row['screen_name'] ?? 'Guest',
                'order'        => $row['order_id'],
                'order_url'    => (string) ee('CP/URL')->make('cp/publish/edit/entry/' . $row['order_id']),
                'status'       => $row['status'],
                'manage_url'   => (string) ee('CP/URL')->make('cp/addons/settings/cartthrob_subscriptions/subscriptions/edit/' . $row['id']),
            ];
        }

        // Check if more records exist
        $has_more = count($results) > $limit;

        // Send AJAX response
        ee()->output->send_ajax_response([
            'subscriptions' => $subscriptions,
            'has_more'      => $has_more,
        ]);
    }
}
