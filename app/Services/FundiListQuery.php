<?php

namespace App\Services;

use App\Models\Fundi;
use App\Models\SiteVisitFundi;
use App\Models\SiteVisitProduct;
use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The fundis list query, shared by the fundis table and its exports so that
 * both apply the same filters and the same district restrictions.
 */
class FundiListQuery
{
    use LocationTrait;

    public function build(array $filters): Builder
    {
        $search_key = $filters['search_key'] ?? null;
        $site_visit_count = $filters['site_visit_count'] ?? null;

        $query = Fundi::query()
            ->select([
                'fundis.id',
                'fundis.fname',
                'fundis.mname',
                'fundis.lname',
                'fundis.gender',
                'fundis.primary_phone',
                'fundis.transaction_phone',
                'fundis.nida_number',
                'fundis.ranking',
                'fundis.status',
                'fundis.ward_id',
                'fundis.approved_at',
                'fundis.rejected_at',
                'fundis.rejected_by',
                'fundis.approved_by',
                'fundis.created_by',
                'fundis.created_at',
                'fundis.legacy_match',
                'fundis.legacy_id',
                DB::raw("CONCAT_WS(' ', fname, mname, lname) as full_name"),
            ])
            ->with([
                'ward:id,name,district_id',
                'ward.district:id,name,region_id',
                'ward.district.region:id,name',
            ]);

        $this->applyLocationFiltersToQuery(
            $query,
            $filters['region'] ?? null,
            $filters['district'] ?? null,
            $filters['ward'] ?? null
        );

        if ($search_key) {
            $searchTerm = '%'.$search_key.'%';
            $query->where(function ($q) use ($searchTerm, $search_key) {
                $q->whereRaw("CONCAT_WS(' ', fname, mname, lname) LIKE ?", [$searchTerm])
                    ->orWhere('fundis.unique_id', 'like', $searchTerm);

                // Handle phone search if the search term looks like a phone number
                if (preg_match('/^[0-9\s\-\+]+$/', $search_key)) {
                    $formattedPhone = $this->formatSearchStringForPhoneSearch($search_key);

                    // Search by removing spaces from stored phone numbers
                    $q->orWhereRaw("REPLACE(REPLACE(primary_phone, ' ', ''), '-', '') LIKE ?", [$formattedPhone])
                        ->orWhereRaw("REPLACE(REPLACE(transaction_phone, ' ', ''), '-', '') LIKE ?", [$formattedPhone]);
                }
            });
        }

        return $query
            ->when($filters['gender'] ?? null, fn ($q, $gender) => $q->where('fundis.gender', $gender))
            ->when($site_visit_count !== null && $site_visit_count !== '', function ($q) use ($site_visit_count) {
                $sub = '(SELECT COUNT(*) FROM site_visit_fundis WHERE site_visit_fundis.fundi_id = fundis.id)';
                if ($site_visit_count === '0') {
                    $q->whereRaw("{$sub} = 0");
                } elseif ($site_visit_count === '1-5') {
                    $q->whereRaw("{$sub} BETWEEN 1 AND 5");
                } elseif ($site_visit_count === '5+') {
                    $q->whereRaw("{$sub} > 5");
                }
            })
            ->when($filters['approval_status'] ?? null, function ($q, $approval_status) {
                if ($approval_status === 'approved') {
                    $q->approved();
                } elseif ($approval_status === 'rejected') {
                    $q->rejected();
                } elseif ($approval_status === 'pending') {
                    $q->pending();
                }
            })
            ->when($filters['created_by'] ?? null, fn ($q, $created_by) => $q->where('fundis.created_by', $created_by))
            ->when($filters['approved_by'] ?? null, fn ($q, $approved_by) => $q->where('fundis.approved_by', $approved_by))
            ->when($filters['legacy_match'] ?? null, fn ($q, $legacy_match) => $q->where('fundis.legacy_match', $legacy_match))
            ->when($filters['date_from'] ?? null, fn ($q, $date_from) => $q->whereDate('fundis.created_at', '>=', $date_from))
            ->when($filters['date_to'] ?? null, fn ($q, $date_to) => $q->whereDate('fundis.created_at', '<=', $date_to))
            ->latest('fundis.created_at');
    }

    /**
     * The list query plus the people named on each Excel row, loaded in bulk.
     */
    public function forExcel(array $filters): Builder
    {
        return $this->build($filters)
            ->with(['creator:id,fname,mname,lname', 'approver:id,fname,mname,lname']);
    }

    /**
     * The list query plus the totals on each PDF row, without a query per row.
     */
    public function forPdf(array $filters): Builder
    {
        return $this->build($filters)
            ->withCount('siteVisitFundis as sites_count')
            ->addSelect([
                // Same total as Fundi::$total_product_quantity.
                'bags_total' => SiteVisitProduct::query()
                    ->selectRaw('COALESCE(SUM(quantity), 0)')
                    ->whereIn(
                        'site_visit_id',
                        SiteVisitFundi::query()
                            ->select('site_visit_id')
                            ->whereColumn('site_visit_fundis.fundi_id', 'fundis.id')
                    ),
            ]);
    }

    // Format search string for phone searchability
    private function formatSearchStringForPhoneSearch($searchTerm)
    {
        // Remove all non-digit characters (spaces, dashes, etc.)
        $digitsOnly = preg_replace('/\D/', '', $searchTerm);

        // Remove leading zeros
        $digitsOnly = ltrim($digitsOnly, '0');

        // Add country code and wildcard for LIKE query
        return '%+255'.$digitsOnly.'%';
    }
}
