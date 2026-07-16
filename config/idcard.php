<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ID Card Print Size
    |--------------------------------------------------------------------------
    |
    | Physical card size is 8.6cm x 5.5cm (standard card stock dimensions
    | required by the printing vendor). Values are in points (cm * 72 / 2.54),
    | consumed by DomPDF::setPaper(). Shared by IdCardController (single card)
    | and GenerateFundiIdCards (bulk job) so both paths stay in sync.
    |
    */
    'paper_size' => [0, 0, 155.91, 243.78],

    'paper_orientation' => 'landscape',

];
