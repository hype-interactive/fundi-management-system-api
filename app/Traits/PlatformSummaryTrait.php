<?php

namespace App\Traits;

use App\Models\Fundi;
use App\Models\User;
use App\Models\SiteVisit;
use App\Models\Transaction;
use App\Traits\DateTimeTrait;
use Illuminate\Support\Facades\DB;

trait PlatformSummaryTrait
{
    use DateTimeTrait;

    // ===== FUNDI METRICS =====

    public function getFundiTotal($tag = null, $district = null, $status = null)
    {
        if ($tag) {
            if ($tag == "TODAY") {
                return $this->countFundis(DateTimeTrait::todayFrom(), DateTimeTrait::todayTo(), $district, $status);
            }
            if ($tag == "WEEK") {
                return $this->countFundis(DateTimeTrait::getDateFrom('monday this week'), DateTimeTrait::getDateTo('sunday this week'), $district, $status);
            }
            if ($tag == "MONTH") {
                return $this->countFundis(DateTimeTrait::getDateFrom('first day of this month'), DateTimeTrait::getDateTo('last day of this month'), $district, $status);
            }
            if ($tag == "YEAR") {
                return $this->countFundis(DateTimeTrait::getDateFrom('first day of january this year'), DateTimeTrait::getDateTo('last day of december this year'), $district, $status);
            }
        }

        $query = Fundi::query();

        if ($district) {
            $query->whereHas('ward', function($q) use ($district) {
                $q->where('district_id', $district);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    public function getFundiDiff($tag)
    {
        if ($tag == "TODAY") {
            $current = $this->countFundis(DateTimeTrait::todayFrom(), DateTimeTrait::todayTo());
            $previous = $this->countFundis(DateTimeTrait::minusTimeFrom("-1 day"), DateTimeTrait::minusTimeTo("-1 day"));
            return $current - $previous;
        }

        if ($tag == "WEEK") {
            $current = $this->countFundis(DateTimeTrait::getDateFrom('monday this week'), DateTimeTrait::getDateTo('sunday this week'));
            $previous = $this->countFundis(DateTimeTrait::getDateFrom('monday last week'), DateTimeTrait::getDateTo('sunday last week'));
            return $current - $previous;
        }

        if ($tag == "MONTH") {
            $current = $this->countFundis(DateTimeTrait::getDateFrom('first day of this month'), DateTimeTrait::getDateTo('last day of this month'));
            $previous = $this->countFundis(DateTimeTrait::getDateFrom('first day of last month'), DateTimeTrait::getDateTo('last day of last month'));
            return $current - $previous;
        }

        if ($tag == "YEAR") {
            $current = $this->countFundis(DateTimeTrait::getDateFrom('first day of january this year'), DateTimeTrait::getDateTo('last day of december this year'));
            $previous = $this->countFundis(DateTimeTrait::getDateFrom('first day of january last year'), DateTimeTrait::getDateTo('last day of december last year'));
            return $current - $previous;
        }
    }

    private function countFundis($from, $to, $district = null, $status = null)
    {
        $query = Fundi::whereBetween('created_at', [$from, $to]);

        if ($district) {
            $query->whereHas('ward', function($q) use ($district) {
                $q->where('district_id', $district);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    // ===== SITE VISIT METRICS =====

    public function getSiteVisitTotal($tag = null, $district = null, $status = null)
    {
        if ($tag) {
            if ($tag == "TODAY") {
                return $this->countSiteVisits(DateTimeTrait::todayFrom(), DateTimeTrait::todayTo(), $district, $status);
            }
            if ($tag == "WEEK") {
                return $this->countSiteVisits(DateTimeTrait::getDateFrom('monday this week'), DateTimeTrait::getDateTo('sunday this week'), $district, $status);
            }
            if ($tag == "MONTH") {
                return $this->countSiteVisits(DateTimeTrait::getDateFrom('first day of this month'), DateTimeTrait::getDateTo('last day of this month'), $district, $status);
            }
            if ($tag == "YEAR") {
                return $this->countSiteVisits(DateTimeTrait::getDateFrom('first day of january this year'), DateTimeTrait::getDateTo('last day of december this year'), $district, $status);
            }
        }

        $query = SiteVisit::query();

        if ($district) {
            $query->whereHas('ward', function($q) use ($district) {
                $q->where('district_id', $district);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    public function getSiteVisitDiff($tag)
    {
        if ($tag == "TODAY") {
            $current = $this->countSiteVisits(DateTimeTrait::todayFrom(), DateTimeTrait::todayTo());
            $previous = $this->countSiteVisits(DateTimeTrait::minusTimeFrom("-1 day"), DateTimeTrait::minusTimeTo("-1 day"));
            return $current - $previous;
        }

        if ($tag == "WEEK") {
            $current = $this->countSiteVisits(DateTimeTrait::getDateFrom('monday this week'), DateTimeTrait::getDateTo('sunday this week'));
            $previous = $this->countSiteVisits(DateTimeTrait::getDateFrom('monday last week'), DateTimeTrait::getDateTo('sunday last week'));
            return $current - $previous;
        }

        if ($tag == "MONTH") {
            $current = $this->countSiteVisits(DateTimeTrait::getDateFrom('first day of this month'), DateTimeTrait::getDateTo('last day of this month'));
            $previous = $this->countSiteVisits(DateTimeTrait::getDateFrom('first day of last month'), DateTimeTrait::getDateTo('last day of last month'));
            return $current - $previous;
        }

        if ($tag == "YEAR") {
            $current = $this->countSiteVisits(DateTimeTrait::getDateFrom('first day of january this year'), DateTimeTrait::getDateTo('last day of december this year'));
            $previous = $this->countSiteVisits(DateTimeTrait::getDateFrom('first day of january last year'), DateTimeTrait::getDateTo('last day of december last year'));
            return $current - $previous;
        }
    }

    private function countSiteVisits($from, $to, $district = null, $status = null)
    {
        $query = SiteVisit::whereBetween('created_at', [$from, $to]);

        if ($district) {
            $query->whereHas('ward', function($q) use ($district) {
                $q->where('district_id', $district);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    // ===== FINANCIAL METRICS =====

    public function getDisbursementTotal($tag = null, $district = null)
    {
        if ($tag) {
            if ($tag == "TODAY") {
                return $this->sumDisbursements(DateTimeTrait::todayFrom(), DateTimeTrait::todayTo(), $district);
            }
            if ($tag == "WEEK") {
                return $this->sumDisbursements(DateTimeTrait::getDateFrom('monday this week'), DateTimeTrait::getDateTo('sunday this week'), $district);
            }
            if ($tag == "MONTH") {
                return $this->sumDisbursements(DateTimeTrait::getDateFrom('first day of this month'), DateTimeTrait::getDateTo('last day of this month'), $district);
            }
            if ($tag == "YEAR") {
                return $this->sumDisbursements(DateTimeTrait::getDateFrom('first day of january this year'), DateTimeTrait::getDateTo('last day of december this year'), $district);
            }
        }

        $query = Transaction::where('status', 'completed');

        if ($district) {
            $query->whereHas('fundi.ward', function($q) use ($district) {
                $q->where('district_id', $district);
            });
        }

        return round($query->sum('amount'));
    }

    public function getDisbursementDiff($tag)
    {
        if ($tag == "TODAY") {
            $current = $this->sumDisbursements(DateTimeTrait::todayFrom(), DateTimeTrait::todayTo());
            $previous = $this->sumDisbursements(DateTimeTrait::minusTimeFrom("-1 day"), DateTimeTrait::minusTimeTo("-1 day"));
            return $current - $previous;
        }

        if ($tag == "WEEK") {
            $current = $this->sumDisbursements(DateTimeTrait::getDateFrom('monday this week'), DateTimeTrait::getDateTo('sunday this week'));
            $previous = $this->sumDisbursements(DateTimeTrait::getDateFrom('monday last week'), DateTimeTrait::getDateTo('sunday last week'));
            return $current - $previous;
        }

        if ($tag == "MONTH") {
            $current = $this->sumDisbursements(DateTimeTrait::getDateFrom('first day of this month'), DateTimeTrait::getDateTo('last day of this month'));
            $previous = $this->sumDisbursements(DateTimeTrait::getDateFrom('first day of last month'), DateTimeTrait::getDateTo('last day of last month'));
            return $current - $previous;
        }

        if ($tag == "YEAR") {
            $current = $this->sumDisbursements(DateTimeTrait::getDateFrom('first day of january this year'), DateTimeTrait::getDateTo('last day of december this year'));
            $previous = $this->sumDisbursements(DateTimeTrait::getDateFrom('first day of january last year'), DateTimeTrait::getDateTo('last day of december last year'));
            return $current - $previous;
        }
    }

    private function sumDisbursements($from, $to, $district = null)
    {
        $query = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$from, $to]);

        if ($district) {
            $query->whereHas('fundi.ward', function($q) use ($district) {
                $q->where('district_id', $district);
            });
        }

        return round($query->sum('amount'));
    }

    // ===== OPERATOR METRICS =====

    public function getActiveOperatorsCount($district = null)
    {
        $query = User::whereHas('role', function($q) {
            $q->where('name', 'operator');
        })->where('status', 'active');

        if ($district) {
            $query->whereHas('districts', function($q) use ($district) {
                $q->where('districts.id', $district);
            });
        }

        return $query->count();
    }

    // ===== PENDING APPROVALS =====

    public function getPendingFundisCount($district = null)
    {
        $query = Fundi::where('status', 'pending');

        if ($district) {
            $query->whereHas('ward', function($q) use ($district) {
                $q->where('district_id', $district);
            });
        }

        return $query->count();
    }

    public function getPendingSiteVisitsCount($district = null)
    {
        $query = SiteVisit::where('status', 'pending');

        if ($district) {
            $query->whereHas('ward', function($q) use ($district) {
                $q->where('district_id', $district);
            });
        }

        return $query->count();
    }

    // ===== REGIONAL BREAKDOWN =====

    public function getRegionalSummary()
    {
        return DB::table('fundis')
            ->join('wards', 'fundis.ward_id', '=', 'wards.id')
            ->join('districts', 'wards.district_id', '=', 'districts.id')
            ->join('regions', 'districts.region_id', '=', 'regions.id')
            ->select(
                'regions.name as region_name',
                DB::raw('COUNT(fundis.id) as total_fundis'),
                DB::raw('COUNT(CASE WHEN fundis.status = "active" THEN 1 END) as active_fundis'),
                DB::raw('COUNT(CASE WHEN fundis.status = "pending" THEN 1 END) as pending_fundis')
            )
            ->whereNull('fundis.deleted_at')
            ->groupBy('regions.id', 'regions.name')
            ->orderBy('regions.name')
            ->get();
    }

    // ===== SUCCESS RATES =====

    public function getFundiApprovalRate($tag = null)
    {
        $from = null;
        $to = null;

        if ($tag) {
            if ($tag == "TODAY") {
                $from = DateTimeTrait::todayFrom();
                $to = DateTimeTrait::todayTo();
            } elseif ($tag == "WEEK") {
                $from = DateTimeTrait::getDateFrom('monday this week');
                $to = DateTimeTrait::getDateTo('sunday this week');
            } elseif ($tag == "MONTH") {
                $from = DateTimeTrait::getDateFrom('first day of this month');
                $to = DateTimeTrait::getDateTo('last day of this month');
            } elseif ($tag == "YEAR") {
                $from = DateTimeTrait::getDateFrom('first day of january this year');
                $to = DateTimeTrait::getDateTo('last day of december this year');
            }
        }

        $query = Fundi::query();

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        $total = $query->count();
        $approved = $query->where('status', 'active')->count();

        return $total > 0 ? round(($approved / $total) * 100, 1) : 0;
    }

    public function getSiteVisitApprovalRate($tag = null)
    {
        $from = null;
        $to = null;

        if ($tag) {
            if ($tag == "TODAY") {
                $from = DateTimeTrait::todayFrom();
                $to = DateTimeTrait::todayTo();
            } elseif ($tag == "WEEK") {
                $from = DateTimeTrait::getDateFrom('monday this week');
                $to = DateTimeTrait::getDateTo('sunday this week');
            } elseif ($tag == "MONTH") {
                $from = DateTimeTrait::getDateFrom('first day of this month');
                $to = DateTimeTrait::getDateTo('last day of this month');
            } elseif ($tag == "YEAR") {
                $from = DateTimeTrait::getDateFrom('first day of january this year');
                $to = DateTimeTrait::getDateTo('last day of december this year');
            }
        }

        $query = SiteVisit::query();

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        $total = $query->count();
        $approved = $query->where('status', 'approved')->count();

        return $total > 0 ? round(($approved / $total) * 100, 1) : 0;
    }
}
