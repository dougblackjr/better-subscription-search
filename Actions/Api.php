<?php

namespace TripleNERDscore\BetterSubscriptionSearch\Actions;

use ExpressionEngine\Service\Addon\Controllers\Action\AbstractRoute;

class Api extends AbstractRoute
{
    public function process()
    {
        $db = ee()->db;
        $table = 'cartthrob_subscriptions';

        // Get input with fallbacks
        $page   = (int) ee()->input->get_post('page') ?: 1;
        $limit  = (int) ee()->input->get_post('limit') ?: 10;
        $search = ee()->input->get_post('search', true);
        $member = ee()->input->get_post('member', true);
        $status = ee()->input->get_post('status', true);

        $offset = ($page - 1) * $limit;

        $db->from($table);

        // Apply filters
        if (!empty($member)) {
            $db->where('member_id', $member);
        }

        if (!empty($status)) {
            $db->where('status', $status);
        }

        if (!empty($search)) {
            $db->group_start()
                ->like('id', $search)
                ->or_like('order_id', $search)
                ->or_like('name', $search)
                ->or_like('description', $search)
                ->group_end();
        }

        // Clone query for "has_more" check
        $total_query = clone $db;
        $total_query->limit($limit, $offset + $limit); // peek ahead 1 page

        // Apply pagination
        $db->order_by('id', 'DESC');
        $db->limit($limit)->offset($offset);
        $query = $db->get();

        $subscriptions = [];
        foreach ($query->result_array() as $row) {
            $lastBillDate = isset($row['last_bill_date']) && $row['last_bill_date'] ? ee()->localize->human_time($row['last_bill_date']) : '';
            $nextBillDate = isset($row['next_bill_date']) && $row['next_bill_date'] ? ee()->localize->human_time($row['next_bill_date']) : '';
            $orderUrl = ee('CP/URL')->make('cp/publish/edit/entry/' . $row['order_id']);
            $manageUrl = ee('CP/URL')->make('cp/addons/settings/cartthrob_subscriptions/subscriptions/edit/' . $row['id']);
            $subscriptions[] = [
                'id'           => $row['id'],
                'last_rebill'  => $lastBillDate,
                'next_rebill'  => $nextBillDate,
                'name'         => $row['name'],
                'member'       => $this->getMemberName($row['member_id']),
                'order'        => $row['order_id'],
                'order_url'    => (string) $orderUrl,
                'status'       => $row['status'],
                'manage_url'   => (string) $manageUrl,
            ];
        }

        // Determine if more pages exist
        $has_more = $total_query->get()->num_rows() > 0;

        ee()->output->send_ajax_response([
            'subscriptions' => $subscriptions,
            'has_more'      => $has_more,
        ]);
    }

    private function getMemberName($member_id)
    {
        if (!$member_id) {
            return 'Guest';
        }

        $query = ee()->db->select('screen_name')
            ->from('members')
            ->where('member_id', (int) $member_id)
            ->get();

        if ($query->num_rows() > 0) {
            return $query->row('screen_name');
        }

        return 'Unknown';
    }
}
