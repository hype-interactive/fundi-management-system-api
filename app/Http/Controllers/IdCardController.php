<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

class IdCardController extends Controller
{
    public function generate()
    {
        // Get data from session
        $data = session()->get('id_card_data');

        if (! $data) {
            return redirect()->back()->with('error', 'ID card data not found');
        }

        // Clear the session data after use
        session()->forget('id_card_data');

        $pdf = Pdf::loadView('documents.id-card', $data)
            ->setPaper(config('idcard.paper_size'), config('idcard.paper_orientation'));

        return $pdf->stream($data['name'].'_Id_card_'.$data['id_number'].'.pdf');

    }
}
