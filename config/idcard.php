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
    | The vendor's print process loses ~0.4cm of height (width prints
    | accurately), so the page height carries an extra 5.9cm - 5.5cm = 0.4cm
    | of vertical bleed (0.2cm / ~5.665pt per edge). The template content is
    | still sized and positioned for the 5.5cm live area and centered inside
    | this taller page - do not scale content up to fill the bleed.
    |
    */
    'paper_size' => [0, 0, 167.24, 243.78],

    'paper_orientation' => 'landscape',

];
