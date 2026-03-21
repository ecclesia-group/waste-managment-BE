<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Fleet;
use App\Models\Payment;
use App\Models\Pickup;
use App\Models\Purchase;
use App\Models\RoutePlannerBinAssignment;
use App\Models\Violation;
use App\Models\WasteHandoverRequest;
use App\Models\WeighbridgeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function providerReports(Request $request)
    {
        $user = $request->user();
        $providerSlug = $user->provider_slug;

        // Clients grouped by status for provider
        $activeStatuses = ['activate', 'active'];
        $inactiveStatuses = ['deactivate', 'inactive'];
        $suspendedStatuses = ['pending', 'suspended'];

        $customersOverview = [
            'active' => Client::query()
                ->where('provider_slug', $providerSlug)
                ->whereIn('status', $activeStatuses)
                ->count(),
            'inactive' => Client::query()
                ->where('provider_slug', $providerSlug)
                ->whereIn('status', $inactiveStatuses)
                ->count(),
            'suspended' => Client::query()
                ->where('provider_slug', $providerSlug)
                ->whereIn('status', $suspendedStatuses)
                ->count(),
        ];

        $fleetOverview = [
            'active' => Fleet::query()
                ->where('provider_slug', $providerSlug)
                ->where('status', 'active')
                ->count(),
            'inactive' => Fleet::query()
                ->where('provider_slug', $providerSlug)
                ->where('status', '!=', 'active')
                ->count(),
        ];

        // Utilization: scanned/unscanned bin assignments + handovers
        $utilization = [
            'scanned_bins' => RoutePlannerBinAssignment::query()
                ->where('provider_slug', $providerSlug)
                ->where('scan_status', 'scanned')
                ->count(),
            'unscanned_bins' => RoutePlannerBinAssignment::query()
                ->where('provider_slug', $providerSlug)
                ->whereIn('scan_status', ['pending', 'not_scanned'])
                ->count(),
            'handover_requests_total' => WasteHandoverRequest::query()
                ->where(function ($q) use ($providerSlug) {
                    $q->where('requester_provider_slug', $providerSlug)
                        ->orWhere('target_provider_slug', $providerSlug);
                })
                ->count(),
            'handover_requests_completed' => WasteHandoverRequest::query()
                ->where(function ($q) use ($providerSlug) {
                    $q->where('requester_provider_slug', $providerSlug)
                        ->orWhere('target_provider_slug', $providerSlug);
                })
                ->where('status', 'completed')
                ->count(),
            'handover_requests_accepted' => WasteHandoverRequest::query()
                ->where(function ($q) use ($providerSlug) {
                    $q->where('requester_provider_slug', $providerSlug)
                        ->orWhere('target_provider_slug', $providerSlug);
                })
                ->where('status', 'accepted')
                ->count(),
        ];

        // Routing analytics: average seconds between assignment row creation and scanned_at
        $routingAnalytics = [
            'avg_seconds_to_scan' => null,
            'by_group' => [],
        ];

        $scannedAssignments = RoutePlannerBinAssignment::query()
            ->where('provider_slug', $providerSlug)
            ->where('scan_status', 'scanned')
            ->whereNotNull('scanned_at')
            ->get(['created_at', 'scanned_at', 'group_slug']);

        if (! $scannedAssignments->isEmpty()) {
            $routingAnalytics['avg_seconds_to_scan'] = $scannedAssignments->avg(function ($row) {
                if (! $row->scanned_at || ! $row->created_at) {
                    return null;
                }
                return Carbon::parse($row->created_at)->diffInSeconds(Carbon::parse($row->scanned_at));
            });

            $routingAnalytics['by_group'] = $scannedAssignments
                ->groupBy('group_slug')
                ->map(function ($groupRows) {
                    return [
                        'avg_seconds_to_scan' => $groupRows->avg(function ($row) {
                            return Carbon::parse($row->created_at)->diffInSeconds(Carbon::parse($row->scanned_at));
                        }),
                        'scanned_bins' => $groupRows->count(),
                    ];
                })
                ->values()
                ->toArray();
        }

        // Payment analytics: Payment rows linked to provider for successful status
        // NOTE: "outstanding" is computed from purchases that do not have a successful payment record.
        $paidPurchasesCountAndTotal = DB::table('purchases as p')
            ->join('clients as c', 'c.client_slug', '=', 'p.client_slug')
            ->leftJoin('payments as pay', function ($join) use ($providerSlug) {
                $join->on('pay.purchase_id', '=', 'p.id')
                    ->where('pay.provider_slug', '=', $providerSlug);
            })
            ->where('c.provider_slug', '=', $providerSlug)
            ->selectRaw('
                COUNT(DISTINCT CASE WHEN pay.id IS NOT NULL AND pay.status = "success" THEN p.id ELSE NULL END) as paid_purchases_count,
                SUM(CASE WHEN pay.id IS NOT NULL AND pay.status = "success" THEN p.total_price ELSE 0 END) as paid_purchases_total_amount
            ')
            ->first();

        $outstandingPurchases = DB::table('purchases as p')
            ->join('clients as c', 'c.client_slug', '=', 'p.client_slug')
            ->leftJoin('payments as pay', function ($join) use ($providerSlug) {
                $join->on('pay.purchase_id', '=', 'p.id')
                    ->where('pay.provider_slug', '=', $providerSlug);
            })
            ->where('c.provider_slug', '=', $providerSlug)
            ->selectRaw('
                COUNT(DISTINCT CASE WHEN pay.id IS NULL OR pay.status != "success" THEN p.id ELSE NULL END) as unpaid_purchases_count,
                SUM(CASE WHEN pay.id IS NULL OR pay.status != "success" THEN p.total_price ELSE 0 END) as outstanding_total_amount
            ')
            ->first();

        $paymentAnalytics = [
            'payments_success_count' => Payment::query()
                ->where('provider_slug', $providerSlug)
                ->where('status', 'success')
                ->count(),
            'payments_success_total_amount' => Payment::query()
                ->where('provider_slug', $providerSlug)
                ->where('status', 'success')
                ->sum('amount'),
            'paid_purchases_count' => (int) ($paidPurchasesCountAndTotal?->paid_purchases_count ?? 0),
            'paid_purchases_total_amount' => (float) ($paidPurchasesCountAndTotal?->paid_purchases_total_amount ?? 0),
            'unpaid_purchases_count' => (int) ($outstandingPurchases?->unpaid_purchases_count ?? 0),
            'outstanding_total_amount' => (float) ($outstandingPurchases?->outstanding_total_amount ?? 0),
        ];

        // Violation overview
        $violationOverview = [
            'total_violations' => Violation::query()
                ->where('provider_slug', $providerSlug)
                ->count(),
            'by_type' => Violation::query()
                ->where('provider_slug', $providerSlug)
                ->select('type', DB::raw('count(*) as total'))
                ->groupBy('type')
                ->get()
                ->toArray(),
        ];

        // Provider rankings (top providers by revenue and scanned bins)
        $topRevenueProviders = DB::table('providers as pr')
            ->leftJoin('payments as pay', 'pay.provider_slug', '=', 'pr.provider_slug')
            ->select(
                'pr.provider_slug',
                DB::raw('COALESCE(SUM(CASE WHEN pay.status = "success" THEN pay.amount ELSE 0 END), 0) as revenue_total')
            )
            ->groupBy('pr.provider_slug')
            ->orderByDesc('revenue_total')
            ->limit(5)
            ->get()
            ->toArray();

        $topScannedProviders = DB::table('providers as pr')
            ->leftJoin('route_planner_bin_assignments as rpa', 'rpa.provider_slug', '=', 'pr.provider_slug')
            ->select(
                'pr.provider_slug',
                DB::raw('COALESCE(SUM(CASE WHEN rpa.scan_status = "scanned" THEN 1 ELSE 0 END), 0) as scanned_bins_total')
            )
            ->groupBy('pr.provider_slug')
            ->orderByDesc('scanned_bins_total')
            ->limit(5)
            ->get()
            ->toArray();

        $rankings = [
            'top_by_revenue' => $topRevenueProviders,
            'top_by_scanned_bins' => $topScannedProviders,
        ];

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider reports retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'customers_overview' => $customersOverview,
                'fleet_overview' => $fleetOverview,
                'utilization' => $utilization,
                'routing_analytics' => $routingAnalytics,
                'payment_analytics' => $paymentAnalytics,
                'violation_overview' => $violationOverview,
                'rankings' => $rankings,
            ]
        );
    }

    public function facilityReports(Request $request)
    {
        $user = $request->user();
        $facilitySlug = $user->facility_slug;

        $bucket = $request->query('bucket');
        $from = $request->query('from');
        $to = $request->query('to');

        $allowedBuckets = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];
        if (! empty($bucket) && ! in_array((string) $bucket, $allowedBuckets, true)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Invalid bucket. Use: daily, weekly, monthly, quarterly, yearly",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $fromDate = null;
        $toDate = null;
        try {
            if (! empty($from)) {
                $fromDate = Carbon::parse($from)->startOfDay();
            }
            if (! empty($to)) {
                $toDate = Carbon::parse($to)->endOfDay();
            }
        } catch (\Throwable $e) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Invalid date range",
                status_code: self::API_FAIL,
                data: []
            );
        }

        // Weighbridge / waste intake
        $weighbridgeQuery = WeighbridgeRecord::query()
            ->where('facility_slug', $facilitySlug);

        if ($fromDate) {
            $weighbridgeQuery->where('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $weighbridgeQuery->where('created_at', '<=', $toDate);
        }

        $timeBucketBreakdown = [];
        if (! empty($bucket)) {
            $bucketSql = match ((string) $bucket) {
                'daily' => "DATE(created_at)",
                'weekly' => "CONCAT(YEAR(created_at), '-W', LPAD(WEEK(created_at, 1), 2, '0'))",
                'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
                'quarterly' => "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))",
                'yearly' => "DATE_FORMAT(created_at, '%Y')",
                default => "DATE(created_at)",
            };

            $timeBucketBreakdown = (clone $weighbridgeQuery)
                ->select(
                    DB::raw($bucketSql . " as label"),
                    DB::raw('count(*) as total_entries'),
                    DB::raw('sum(amount) as total_amount'),
                    DB::raw('sum(gross_weight) as total_gross_weight')
                )
                ->groupBy(DB::raw($bucketSql))
                ->orderBy('label')
                ->get()
                ->values()
                ->toArray();
        }

        $wasteOverview = [
            'total_entries' => (clone $weighbridgeQuery)->count(),
            'total_amount' => (clone $weighbridgeQuery)->sum('amount'),
            'total_gross_weight' => (clone $weighbridgeQuery)->sum('gross_weight'),
            'scan_status_breakdown' => (clone $weighbridgeQuery)
                ->select('scan_status', DB::raw('count(*) as total'))
                ->groupBy('scan_status')
                ->get()
                ->toArray(),
            'time_bucket' => [
                'bucket' => $bucket ? (string) $bucket : null,
                'from' => $fromDate ? $fromDate->toDateString() : null,
                'to' => $toDate ? $toDate->toDateString() : null,
                'breakdown' => $timeBucketBreakdown,
            ],
        ];

        // Payment analytics for weighbridge (paid vs credit)
        $paymentAnalytics = [
            'paid_entries' => (clone $weighbridgeQuery)->where('payment_status', 'paid')->count(),
            'credit_entries' => (clone $weighbridgeQuery)->where('payment_status', 'credit')->count(),
            'paid_total_amount' => (clone $weighbridgeQuery)->where('payment_status', 'paid')->sum('amount'),
            'credit_total_amount' => (clone $weighbridgeQuery)->where('payment_status', 'credit')->sum('amount'),
        ];

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility reports retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'waste_overview' => $wasteOverview,
                'payment_analytics' => $paymentAnalytics,
            ]
        );
    }

    public function districtAssemblyReports(Request $request)
    {
        $user = $request->user();
        $mmdaSlug = $user->district_assembly_slug;

        // Providers under this MMDA (providers table stores district_assembly slug)
        $providerSlugs = DB::table('providers')
            ->where('district_assembly', $mmdaSlug)
            ->pluck('provider_slug')
            ->toArray();

        // Facilities under this MMDA
        $facilitySlugs = DB::table('facilities')
            ->where('district_assembly', $mmdaSlug)
            ->pluck('facility_slug')
            ->toArray();

        $customerCounts = Client::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->toArray();

        $assignmentUtilization = RoutePlannerBinAssignment::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->select('scan_status', DB::raw('count(*) as total'))
            ->groupBy('scan_status')
            ->get()
            ->toArray();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "MMDA reports retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'providers_total' => count($providerSlugs),
                'facilities_total' => count($facilitySlugs),
                'customer_counts_by_status' => $customerCounts,
                'assignment_scan_status_breakdown' => $assignmentUtilization,
            ]
        );
    }

    public function adminReports(Request $request)
    {
        // Super-admin platform-wide summary
        $customersTotal = Client::count();
        $providersTotal = DB::table('providers')->count();
        $facilitiesTotal = DB::table('facilities')->count();
        $assignmentsScannedTotal = RoutePlannerBinAssignment::query()
            ->where('scan_status', 'scanned')
            ->count();

        $totalViolations = Violation::query()->count();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Platform reports retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'customers_total' => $customersTotal,
                'providers_total' => $providersTotal,
                'facilities_total' => $facilitiesTotal,
                'assignments_scanned_total' => $assignmentsScannedTotal,
                'total_violations' => $totalViolations,
            ]
        );
    }
}

