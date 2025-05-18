<?php

namespace App\Http\Controllers;

use App\Models\Fundi;
use Illuminate\Http\Request;

class FundiController extends Controller
{
    public function show(Fundi $fundi)
    {
        return view('pages.fundi', compact('fundi'));
    }
}
