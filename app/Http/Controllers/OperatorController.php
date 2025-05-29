<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    /**
     * Display the specified operator.
     */
    public function show(User $operator)
    {
        // Ensure the user is actually an operator (not a fundi or other user type)
        if (!$operator->role || $operator->role->name === 'fundi') {
            abort(404, 'Operator not found');
        }

        // Load necessary relationships
        $operator->load([
            'role',
            'districts',
            'siteVisits' => function($query) {
                $query->latest()->take(5);
            },
            'incentives' => function($query) {
                $query->latest()->take(5);
            },
            'activityLogs' => function($query) {
                $query->latest()->take(10);
            }
        ]);

        return view('pages.operator', compact('operator'));
    }
}
