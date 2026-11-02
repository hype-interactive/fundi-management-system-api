<?php

use App\Livewire\FundisTable;
use App\Models\District;
use App\Models\Fundi;
use App\Models\Product;
use App\Models\Region;
use App\Models\SiteVisit;
use App\Models\SiteVisitFundi;
use App\Models\SiteVisitProduct;
use App\Models\User;
use App\Models\Ward;
use App\Services\FundiListPdf;
use App\Services\FundiListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Regression tests for the fundis list downloads.
 *
 * Bug: both downloads ran as Livewire actions, which hold the whole file in
 * memory, and the PDF laid out every row through Dompdf's HTML engine, so
 * /fundis?region=3 died with a 502 instead of downloading.
 */
function exportGeo(string $name): array
{
    $region = Region::create(['name' => "{$name} Region"]);
    $district = District::create(['region_id' => $region->id, 'name' => "{$name} District"]);
    $ward = Ward::create(['district_id' => $district->id, 'name' => "{$name} Ward"]);

    return compact('region', 'district', 'ward');
}

function exportFundi(Ward $ward, User $creator, string $fname, array $attributes = []): Fundi
{
    static $sequence = 0;
    $sequence++;

    return Fundi::create(array_merge([
        'unique_id' => 'FND'.str_pad($sequence, 8, '0', STR_PAD_LEFT),
        'fname' => $fname,
        'lname' => 'Fundi',
        'gender' => 'male',
        'primary_phone' => '+255700'.str_pad($sequence, 6, '0', STR_PAD_LEFT),
        'transaction_phone' => '+255710'.str_pad($sequence, 6, '0', STR_PAD_LEFT),
        'ward_id' => $ward->id,
        'ranking' => 'fundi_kuu',
        'status' => 'active',
        'created_by' => $creator->id,
        'qr_code_path' => 'qrcodes/existing.svg',
    ], $attributes));
}

function exportAdmin(): User
{
    return User::factory()->create(['role_id' => 2, 'status' => 'active']);
}

function exportCsvRows(string $csv): array
{
    $lines = array_map('str_getcsv', array_filter(explode("\n", $csv)));
    $headers = array_shift($lines);

    return array_map(fn ($line) => array_combine($headers, $line), $lines);
}

/**
 * The PDF's text, from its uncompressed content streams.
 */
function exportPdfText(string $pdf): string
{
    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);

    return collect($streams[1])
        ->map(fn ($stream) => @gzuncompress($stream) ?: $stream)
        ->implode("\n");
}

test('guests cannot download the fundis list', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['fundis.export.excel', 'fundis.export.pdf']);

test('excel export only contains the selected region and fills in every column', function () {
    $admin = exportAdmin();
    $included = exportGeo('Included');
    $excluded = exportGeo('Excluded');

    $fundi = exportFundi($included['ward'], $admin, 'Asha', ['ranking' => 'fundi_kuu', 'nida_number' => '19900101']);
    exportFundi($excluded['ward'], $admin, 'Baraka');

    $rows = exportCsvRows(
        $this->actingAs($admin)
            ->get(route('fundis.export.excel', ['region' => $included['region']->id]))
            ->assertOk()
            ->assertHeader('X-Accel-Buffering', 'no')
            ->streamedContent()
    );

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['Name'])->toBe($fundi->full_name)
        ->and($rows[0]['Ward'])->toBe('Included Ward')
        ->and($rows[0]['District'])->toBe('Included District')
        ->and($rows[0]['Region'])->toBe('Included Region')
        ->and($rows[0]['NIDA Number'])->toBe('19900101')
        ->and($rows[0]['Ranking'])->toBe('Fundi Mkuu')
        ->and($rows[0]['Registered By'])->toBe($admin->full_name);
});

test('excel export reports a fundi rejected without approval as rejected', function () {
    $admin = exportAdmin();
    ['ward' => $ward] = exportGeo('Status');

    exportFundi($ward, $admin, 'Rejected', ['rejected_at' => now(), 'rejected_by' => $admin->id]);

    $rows = exportCsvRows($this->actingAs($admin)->get(route('fundis.export.excel'))->streamedContent());

    expect($rows[0]['Status'])->toBe('Rejected');
});

test('pdf totals sites and bags the same way as the list page', function () {
    $admin = exportAdmin();
    ['ward' => $ward] = exportGeo('Totals');

    $busy = exportFundi($ward, $admin, 'Busy');
    $idle = exportFundi($ward, $admin, 'Idle');

    $visit = SiteVisit::factory()->create();
    SiteVisitFundi::create(['site_visit_id' => $visit->id, 'fundi_id' => $busy->id]);
    $product = Product::factory()->create();
    SiteVisitProduct::create(['site_visit_id' => $visit->id, 'product_id' => $product->id, 'quantity' => 7]);
    SiteVisitProduct::create(['site_visit_id' => $visit->id, 'product_id' => $product->id, 'quantity' => 5]);

    $this->actingAs($admin);
    $rows = app(FundiListQuery::class)->forPdf([])->get()->keyBy('id');

    expect($busy->fresh()->total_product_quantity)->toBe(12)
        ->and((int) $rows[$busy->id]->bags_total)->toBe(12)
        ->and((int) $rows[$busy->id]->sites_count)->toBe($busy->siteVisitFundis()->count())
        ->and((int) $rows[$idle->id]->bags_total)->toBe(0)
        ->and((int) $rows[$idle->id]->sites_count)->toBe(0);
});

test('exports keep an operator inside their own districts whatever region they ask for', function (string $route) {
    $admin = exportAdmin();
    $own = exportGeo('Own');
    $other = exportGeo('Other');
    exportFundi($own['ward'], $admin, 'Mine');
    exportFundi($other['ward'], $admin, 'Secret');

    $operator = User::factory()->create(['role_id' => 3, 'status' => 'active']);
    $operator->districts()->attach($own['district']->id);

    $this->actingAs($operator);

    $response = $this->get(route($route, ['region' => $other['region']->id]))->assertOk();
    $content = $route === 'fundis.export.excel' ? $response->streamedContent() : exportPdfText($response->getContent());
    expect($content)->not->toContain('Secret');

    $response = $this->get(route($route))->assertOk();
    $content = $route === 'fundis.export.excel' ? $response->streamedContent() : exportPdfText($response->getContent());
    expect($content)->toContain('Mine')->not->toContain('Secret');
})->with(['fundis.export.excel', 'fundis.export.pdf']);

test('the site visit filter of zero survives into the export link', function () {
    $admin = exportAdmin();
    ['ward' => $ward] = exportGeo('Visits');
    exportFundi($ward, $admin, 'Asha');

    Livewire::actingAs($admin)
        ->withQueryParams(['site_visit_count' => '0', 'region' => 3])
        ->test(FundisTable::class)
        ->assertSee(e(route('fundis.export.excel', ['region' => 3, 'site_visit_count' => '0'])), false)
        ->assertSee(e(route('fundis.export.pdf', ['region' => 3, 'site_visit_count' => '0'])), false);
});

test('pdf export is a real pdf attachment with the selected rows', function () {
    $admin = exportAdmin();
    $included = exportGeo('Included');
    $excluded = exportGeo('Excluded');
    exportFundi($included['ward'], $admin, 'Asha');
    exportFundi($excluded['ward'], $admin, 'Baraka');

    $response = $this->actingAs($admin)
        ->get(route('fundis.export.pdf', ['region' => $included['region']->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('magic_builders_mafundi.pdf');

    $text = exportPdfText($response->getContent());

    expect($response->getContent())->toStartWith('%PDF-')
        ->and($text)->toContain('Asha Fundi')
        ->and($text)->toContain('Included Ward, Included District, Included Region')
        ->and($text)->not->toContain('Baraka');
});

test('pdf handles long unwrapped text, odd characters and many pages', function () {
    $admin = exportAdmin();
    ['ward' => $ward] = exportGeo('Layout');

    exportFundi($ward, $admin, str_repeat('Verylongname', 30));
    exportFundi($ward, $admin, "José (O'Brien) \\ Ñ");
    foreach (range(1, 120) as $i) {
        exportFundi($ward, $admin, "Row{$i}");
    }

    $response = $this->actingAs($admin)->get(route('fundis.export.pdf'))->assertOk();

    expect(substr_count($response->getContent(), '/Type /Page'))->toBeGreaterThan(3);
});

test('download queries do not grow with the number of fundis', function (string $route) {
    $admin = exportAdmin();
    ['ward' => $ward] = exportGeo('Queries');

    $countQueries = function () use ($route, $admin) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->actingAs($admin)->get(route($route))->assertOk();
        if ($route === 'fundis.export.excel') {
            $response->streamedContent();
        }

        return count(DB::getQueryLog());
    };

    foreach (range(1, 5) as $i) {
        exportFundi($ward, $admin, "A{$i}");
    }
    $countQueries(); // The first request also loads the user's role once.
    $few = $countQueries();

    foreach (range(1, 40) as $i) {
        exportFundi($ward, $admin, "B{$i}");
    }

    expect($countQueries())->toBe($few);
})->with(['fundis.export.excel', 'fundis.export.pdf']);

test('chunked exports return every row exactly once, newest first', function () {
    $admin = exportAdmin();
    ['ward' => $ward] = exportGeo('Chunks');

    // created_at runs against id here, which breaks keyset paging if the list's ordering is kept.
    $ids = range(1, 503);
    DB::table('fundis')->insert(array_map(fn ($i) => [
        'unique_id' => 'BULK'.$i,
        'fname' => "Row{$i}",
        'mname' => 'Mid',
        'lname' => 'Fundi',
        'gender' => 'male',
        'primary_phone' => '+255'.$i,
        'transaction_phone' => '+255'.$i,
        'ward_id' => $ward->id,
        'ranking' => 'fundi_kuu',
        'status' => 'active',
        'created_by' => $admin->id,
        'created_at' => now()->addMinutes($i % 7),
    ], $ids));

    $rows = exportCsvRows($this->actingAs($admin)->get(route('fundis.export.excel'))->streamedContent());
    $exported = array_column($rows, 'Name');

    expect($exported)->toHaveCount(503)
        ->and(array_unique($exported))->toHaveCount(503)
        ->and($exported[0])->toBe('Row503 Mid Fundi');
});

test('oversized pdf requests go back to the list with a message instead of crashing', function () {
    $admin = exportAdmin();
    ['ward' => $ward] = exportGeo('Huge');
    exportFundi($ward, $admin, 'Asha');

    $this->mock(FundiListQuery::class)
        ->shouldReceive('build')
        ->andReturn(Mockery::mock(Builder::class, ['count' => FundiListPdf::MAX_ROWS + 1]));

    $this->actingAs($admin)
        ->from(route('fundis', ['region' => 3]))
        ->get(route('fundis.export.pdf'))
        ->assertRedirect(route('fundis', ['region' => 3]))
        ->assertSessionHas('alert', fn ($alert) => $alert['type'] === 'error');
});

test('invalid filter values are rejected instead of raising a server error', function () {
    $this->actingAs(exportAdmin())
        ->get(route('fundis.export.excel', ['region' => 'abc']))
        ->assertRedirect();
});
