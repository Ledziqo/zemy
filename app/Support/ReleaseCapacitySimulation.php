<?php

namespace App\Support;

/**
 * Transparent, no-load capacity model. It intentionally does not generate
 * production traffic: MySQL's hourly connection counter cannot be measured
 * reliably from one PHP request or one persistent PDO worker.
 */
class ReleaseCapacitySimulation
{
    public function run(): array
    {
        $limit = 500;
        $planningBudget = 350;
        $reserve = $limit - $planningBudget;
        $stages = [1, 5, 10, 20, 30, 40, 80];

        // Conservative hourly activity model for one busy venue. Menu loads,
        // unchanged workboard polls, and QR downloads are file/cache work after
        // the optimizations; orders/actions still need authoritative MySQL.
        $activity = [
            'workboard_polls' => 120,
            'guest_menu_and_qr_cache_requests' => 125,
            'order_and_service_writes' => 12,
            'staff_actions_and_changed_board_reads' => 5,
        ];
        $dbBackedPerHotel = $activity['order_and_service_writes'] + $activity['staff_actions_and_changed_board_reads'];

        return [
            'mode' => 'bounded_capacity_model_no_production_load',
            'status' => 'REVIEW REQUIRED',
            'hosting_connection_limit_per_hour' => $limit,
            'planning_budget_per_hour' => $planningBudget,
            'reserved_headroom_per_hour' => $reserve,
            'activity_assumptions_per_busy_hotel_per_hour' => $activity,
            'db_backed_request_units_per_hotel_hour' => $dbBackedPerHotel,
            'stages' => array_map(function (int $hotels) use ($limit, $planningBudget, $dbBackedPerHotel, $activity): array {
                $dbUnits = $hotels * $dbBackedPerHotel;
                $worstCaseNewConnections = $dbUnits;
                return [
                    'busy_hotels' => $hotels,
                    'estimated_total_requests' => $hotels * array_sum($activity),
                    'cache_or_file_requests' => $hotels * ($activity['workboard_polls'] + $activity['guest_menu_and_qr_cache_requests']),
                    'db_backed_request_units' => $dbUnits,
                    'worst_case_new_connections_if_every_db_request_opens_one' => $worstCaseNewConnections,
                    'within_500_hour_limit_under_worst_case' => $worstCaseNewConnections <= $limit,
                    'within_350_hour_planning_budget_under_worst_case' => $worstCaseNewConnections <= $planningBudget,
                    'result' => $worstCaseNewConnections <= $planningBudget ? 'MODEL PASS' : 'MODEL EXCEEDS SAFE BUDGET',
                ];
            }, $stages),
            'estimated_safe_busy_hotel_count_under_worst_case' => intdiv($planningBudget, $dbBackedPerHotel),
            'limitations' => [
                'A request unit is not a database connection.',
                'Persistent PDO reuse, PHP-FPM worker count, Hostinger pooling and other tenants determine actual connection creation.',
                'This model does not create orders, send traffic, consume the quota, or certify maximum capacity.',
            ],
        ];
    }
}
