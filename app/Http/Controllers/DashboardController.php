<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Traits\PlatformSummaryTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use PlatformSummaryTrait;

    public function index()
    {
        // Get regional breakdown for charts/graphs
        $regionalSummary = $this->getRegionalSummary();

        // Get quick stats for dashboard widgets
        $quickStats = [
            'total_fundis' => $this->getFundiTotal(),
            'active_fundis' => $this->getFundiTotal(null, null, 'active'),
            'pending_fundis' => $this->getPendingFundisCount(),
            'total_operators' => $this->getActiveOperatorsCount(),
            'pending_site_visits' => $this->getPendingSiteVisitsCount(),
            'fundi_approval_rate' => $this->getFundiApprovalRate('MONTH'),
            'visit_approval_rate' => $this->getSiteVisitApprovalRate('MONTH'),
        ];

        // Get recent trends for sparkline charts
        $trends = $this->getRecentTrends();

        return view('pages.dashboard', compact('regionalSummary', 'quickStats', 'trends'));
    }

    public function adminDashboard()
    {
        // Admin-specific metrics
        $adminMetrics = [
            'pending_approvals' => [
                'fundis' => $this->getPendingFundisCount(),
                'site_visits' => $this->getPendingSiteVisitsCount(),
            ],
            'monthly_performance' => [
                'fundi_registrations' => $this->getFundiTotal('MONTH'),
                'site_visits' => $this->getSiteVisitTotal('MONTH'),
                'disbursements' => $this->getDisbursementTotal('MONTH'),
            ],
            'approval_rates' => [
                'fundis' => $this->getFundiApprovalRate('MONTH'),
                'site_visits' => $this->getSiteVisitApprovalRate('MONTH'),
            ],
            'regional_breakdown' => $this->getRegionalSummary(),
        ];

        return view('dashboard.admin', compact('adminMetrics'));
    }

    public function operatorDashboard()
    {
        $user = Auth::user();
        $userDistrict = $user->primary_district->id ?? null;

        // Operator-specific metrics (filtered by their district)
        $operatorMetrics = [
            'my_district_stats' => [
                'total_fundis' => $this->getFundiTotal(null, $userDistrict),
                'active_fundis' => $this->getFundiTotal(null, $userDistrict, 'active'),
                'pending_fundis' => $this->getPendingFundisCount($userDistrict),
                'my_site_visits' => $this->getSiteVisitTotal(null, $userDistrict),
            ],
            'monthly_activity' => [
                'fundis_registered' => $this->getFundiTotal('MONTH', $userDistrict),
                'site_visits_conducted' => $this->getSiteVisitTotal('MONTH', $userDistrict),
            ],
            'targets_vs_achievement' => $this->getOperatorTargets($user->id),
        ];

        return view('dashboard.operator', compact('operatorMetrics'));
    }

    private function getRecentTrends()
    {
        $trends = [];

        // Get last 7 days of data for sparklines
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $from = $date->startOfDay();
            $to = $date->endOfDay();

            $trends['fundis'][] = $this->countFundis($from, $to);
            $trends['site_visits'][] = $this->countSiteVisits($from, $to);
            $trends['disbursements'][] = $this->sumDisbursements($from, $to);
        }

        return $trends;
    }

    private function getOperatorTargets($operatorId)
    {
        // This would typically come from a targets/goals table
        // For now, return sample data structure
        return [
            'monthly_fundi_target' => 50,
            'monthly_fundi_achieved' => $this->getFundiTotal('MONTH'),
            'monthly_visit_target' => 20,
            'monthly_visit_achieved' => $this->getSiteVisitTotal('MONTH'),
        ];
    }

    public function exportSummary(Request $request)
    {
        $district = $request->get('district');
        $format = $request->get('format', 'pdf'); // pdf or excel

        $data = [
            'summary_date' => now(),
            'district_filter' => $district ? District::find($district)->name : 'All Districts',
            'metrics' => [
                'fundis' => [
                    'total' => $this->getFundiTotal(null, $district),
                    'active' => $this->getFundiTotal(null, $district, 'active'),
                    'pending' => $this->getPendingFundisCount($district),
                    'monthly_registrations' => $this->getFundiTotal('MONTH', $district),
                ],
                'site_visits' => [
                    'total' => $this->getSiteVisitTotal(null, $district),
                    'monthly_visits' => $this->getSiteVisitTotal('MONTH', $district),
                    'pending_approval' => $this->getPendingSiteVisitsCount($district),
                ],
                'financial' => [
                    'total_disbursed' => $this->getDisbursementTotal(null, $district),
                    'monthly_disbursements' => $this->getDisbursementTotal('MONTH', $district),
                ],
                'performance' => [
                    'fundi_approval_rate' => $this->getFundiApprovalRate('MONTH'),
                    'visit_approval_rate' => $this->getSiteVisitApprovalRate('MONTH'),
                ],
            ],
            'regional_breakdown' => $this->getRegionalSummary(),
        ];

        if ($format === 'excel') {
            return $this->exportToExcel($data);
        }

        return $this->exportToPdf($data);
    }

    private function exportToPdf($data)
    {
        // Implementation for PDF export
        // Using libraries like DomPDF or similar
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('reports.platform-summary-pdf', $data);

        return $pdf->download('platform-summary-'.now()->format('Y-m-d').'.pdf');
    }

    private function exportToExcel($data)
    {
        // Implementation for Excel export
        // Using libraries like Laravel Excel
        // return (new PlatformSummaryExport($data))->download('platform-summary-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function apiSummary(Request $request)
    {
        // API endpoint for mobile app or external systems
        $district = $request->get('district');

        return response()->json([
            'status' => 'success',
            'data' => [
                'overview' => [
                    'total_fundis' => $this->getFundiTotal(null, $district),
                    'active_fundis' => $this->getFundiTotal(null, $district, 'active'),
                    'total_operators' => $this->getActiveOperatorsCount($district),
                    'pending_approvals' => [
                        'fundis' => $this->getPendingFundisCount($district),
                        'site_visits' => $this->getPendingSiteVisitsCount($district),
                    ],
                ],
                'trends' => [
                    'daily' => [
                        'fundis' => $this->getFundiTotal('TODAY', $district),
                        'site_visits' => $this->getSiteVisitTotal('TODAY', $district),
                        'disbursements' => $this->getDisbursementTotal('TODAY', $district),
                    ],
                    'weekly' => [
                        'fundis' => $this->getFundiTotal('WEEK', $district),
                        'site_visits' => $this->getSiteVisitTotal('WEEK', $district),
                        'disbursements' => $this->getDisbursementTotal('WEEK', $district),
                    ],
                    'monthly' => [
                        'fundis' => $this->getFundiTotal('MONTH', $district),
                        'site_visits' => $this->getSiteVisitTotal('MONTH', $district),
                        'disbursements' => $this->getDisbursementTotal('MONTH', $district),
                    ],
                ],
                'performance' => [
                    'fundi_approval_rate' => $this->getFundiApprovalRate('MONTH'),
                    'visit_approval_rate' => $this->getSiteVisitApprovalRate('MONTH'),
                ],
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }
}
