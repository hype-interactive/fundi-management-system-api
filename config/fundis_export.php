<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fundi PDF Export Row Cap
    |--------------------------------------------------------------------------
    |
    | Both the single-file/ZIP split trigger and the per-file row cap within a
    | split - one constant, not two, since a district-only split without a
    | matching per-file cap could still produce an oversized single file.
    |
    | Measured against the production 512MB queue worker limit: DomPDF's
    | marginal memory cost per row rises with row count (~209KB/row at 50
    | rows, ~382KB/row by 500, OOM before 1,000 rows in one document). 500
    | rows peaks at ~194MB, leaving comfortable headroom. Consumed by
    | GenerateFundisPdfReport and FundisTable's pre-flight confirmation.
    |
    */
    'pdf_row_cap' => 500,

];
