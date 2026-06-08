<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\FundiMinimalResource;
use App\Http\Resources\SiteVisitListResource;
use App\Models\Fundi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseAPIController
{
    public function index(Request $request): JsonResponse
    {
        $operator = $this->getOperator();
        $districtIds = $this->getOperatorDistricts();

        // Get date range for stats (default last 30 days)
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->subDays(30);
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();

        // Fundi statistics
        $fundiStats = [
            'total' => Fundi::whereHas('ward.district', function ($q) use ($districtIds) {
                $q->whereIn('id', $districtIds);
            })->count(),

            'approved' => Fundi::whereHas('ward.district', function ($q) use ($districtIds) {
                $q->whereIn('id', $districtIds);
            })->approved()->count(),

            'pending' => Fundi::whereHas('ward.district', function ($q) use ($districtIds) {
                $q->whereIn('id', $districtIds);
            })->whereNull('approved_at')->whereNull('rejected_at')->count(),

            'active' => Fundi::whereHas('ward.district', function ($q) use ($districtIds) {
                $q->whereIn('id', $districtIds);
            })->where('status', 'active')->count(),

            'registered_this_period' => Fundi::whereHas('ward.district', function ($q) use ($districtIds) {
                $q->whereIn('id', $districtIds);
            })->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
        ];

        // Site visit statistics
        $siteVisitStats = [
            'total' => $operator->siteVisits()->count(),
            'approved' => $operator->siteVisits()->where('status', 'approved')->count(),
            'pending' => $operator->siteVisits()->where('status', 'pending')->count(),
            'rejected' => $operator->siteVisits()->where('status', 'rejected')->count(),
            'this_period' => $operator->siteVisits()
                ->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
        ];

        // Transaction statistics
        $transactionStats = DB::table('transactions')
            ->join('fundis', 'transactions.fundi_id', '=', 'fundis.id')
            ->join('wards', 'fundis.ward_id', '=', 'wards.id')
            ->join('districts', 'wards.district_id', '=', 'districts.id')
            ->whereIn('districts.id', $districtIds)
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN transactions.status = ? THEN 1 ELSE 0 END) as successful_count,
                SUM(CASE WHEN transactions.status = ? THEN transactions.amount ELSE 0 END) as total_amount,
                SUM(CASE WHEN transactions.processed_at BETWEEN ? AND ? THEN transactions.amount ELSE 0 END) as period_amount
            ', ['completed', 'completed', $dateFrom, $dateTo])
            ->first();

        // Recent activities
        $recentFundis = Fundi::with(['ward'])
            ->where('created_by', $operator->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentSiteVisits = $operator->siteVisits()
            ->with(['ward'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $this->successResponse([
            'period' => [
                'from' => $dateFrom->format('Y-m-d'),
                'to' => $dateTo->format('Y-m-d'),
            ],
            'stats' => [
                'fundis' => $fundiStats,
                'site_visits' => $siteVisitStats,
                'transactions' => [
                    'total_count' => $transactionStats->total_count ?? 0,
                    'successful_count' => $transactionStats->successful_count ?? 0,
                    'total_amount' => formatCurrency($transactionStats->total_amount ?? 0),
                    'period_amount' => formatCurrency($transactionStats->period_amount ?? 0),
                ],
            ],
            'recent_activities' => [
                'fundis' => FundiMinimalResource::collection($recentFundis),
                'site_visits' => SiteVisitListResource::collection($recentSiteVisits),
            ],
        ]);
    }
}
