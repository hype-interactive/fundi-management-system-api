<?php

namespace App\Http\Controllers;

use App\Models\Fundi;

class FundiController extends Controller
{
    public function show(Fundi $fundi)
    {
        $this->authorize('view', $fundi);

        return view('pages.fundi', compact('fundi'));
    }
}
