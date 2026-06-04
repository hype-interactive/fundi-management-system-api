<?php

namespace App\Http\Controllers;

use App\Models\SiteVisit;

class SiteVisitController extends Controller
{
    public function show(SiteVisit $siteVisit)
    {
        // Load all necessary relationships
        $siteVisit->load([
            'operator',
            'ward.district.region',
            'photos',
            'products',
            'fundis',
            'approver',
        ]);

        return view('pages.site-visit', compact('siteVisit'));
    }
}
