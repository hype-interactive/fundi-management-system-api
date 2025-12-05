<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class IdCardController extends Controller
{
    public function generate()
    {
   // Get data from session
    $data = session()->get('id_card_data');
    
    if (!$data) {
        return redirect()->back()->with('error', 'ID card data not found');
    }

    // Clear the session data after use
    session()->forget('id_card_data');

    $pdf = Pdf::loadView($data->name.'_Id_card_'.$data->id_number, $data)
    ->setPaper([0, 0, 300, 530], 'landscape');

        
    return $pdf->stream('id-card.pdf');

    }
}
