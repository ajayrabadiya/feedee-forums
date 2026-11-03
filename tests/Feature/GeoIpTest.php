<?php

namespace Tests\Feature;

use App\Models\IpCountryRange;
use App\Models\SupportClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GeoIpTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15';

    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('User-Agent', self::BROWSER);
        $this->seedRanges([
            ['81.2.69.0', '81.2.69.255', 'GB'],
            ['8.8.8.0', '8.8.8.255', 'US'],
            ['2a02:c7f::', '2a02:c7f:ffff:ffff:ffff:ffff:ffff:ffff', 'GB'],
            ['10.0.0.0', '10.255.255.255', 'US'],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    /**
     * @param  list<array{string, string, string}>  $ranges
     */
    private function seedRanges(array $ranges): void
    {
        foreach ($ranges as [$from, $to, $code]) {
            IpCountryRange::create(['ip_from' => IpCountryRange::toHex($from), 'ip_to' => IpCountryRange::toHex($to), 'country_code' => $code]);
        }
    }

    private function clickFrom(string $ip, array $headers = []): SupportClick
    {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])->get(route('support.go', 'cashapp'), $headers)->assertRedirect();

        return SupportClick::latest('id')->firstOrFail();
    }

    /**
     * @param  list<string>  $lines
     */
    private function gzipFile(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dbip').'.csv.gz';
        file_put_contents($path, gzencode(implode("\n", $lines)."\n"));
        $this->temporaryFiles[] = $path;

        return $path;
    }

    /**
     * @return array<string, array{string, ?string, string}>
     */
    public static function addressProvider(): array
    {
        return [
            'IPv4 in the United Kingdom' => ['81.2.69.160', 'GB', 'United Kingdom'],
            'IPv4 in the United States' => ['8.8.8.8', 'US', 'United States'],
            'IPv6 in the United Kingdom' => ['2a02:c7f:7e14::1', 'GB', 'United Kingdom'],
            'address outside every range' => ['1.1.1.1', null, 'Unknown'],
            'private network address' => ['10.1.2.3', null, 'Unknown'],
            'localhost' => ['127.0.0.1', null, 'Unknown'],
        ];
    }

    #[DataProvider('addressProvider')]
    public function test_country_is_detected_from_the_local_database(string $ip, ?string $code, string $name): void
    {
        $click = $this->clickFrom($ip);

        $this->assertSame($code, $click->country_code);
        $this->assertSame($name, $click->country_name);
    }

    public function test_trusted_country_header_is_used_only_when_configured(): void
    {
        $this->assertSame('GB', $this->clickFrom('81.2.69.160', ['CF-IPCountry' => 'CA'])->country_code);

        config(['analytics.geoip.header' => 'CF-IPCountry']);

        $this->assertSame('CA', $this->clickFrom('81.2.69.161', ['CF-IPCountry' => 'ca'])->country_code);
    }

    public function test_unusable_header_values_fall_back_to_the_database(): void
    {
        config(['analytics.geoip.header' => 'CF-IPCountry']);

        $this->assertSame('GB', $this->clickFrom('81.2.69.160', ['CF-IPCountry' => 'XX'])->country_code);
        $this->assertSame('US', $this->clickFrom('8.8.8.8', ['CF-IPCountry' => 'T1'])->country_code);
    }

    public function test_browser_language_is_never_used_to_guess_the_country(): void
    {
        $click = $this->clickFrom('1.1.1.1', ['Accept-Language' => 'en-GB,en;q=0.9']);

        $this->assertNull($click->country_code);
    }

    public function test_lookups_are_cached_per_address(): void
    {
        $this->assertSame('GB', $this->clickFrom('81.2.69.160')->country_code);

        IpCountryRange::query()->delete();

        $this->assertSame('GB', $this->clickFrom('81.2.69.160')->country_code);
        $this->assertNull($this->clickFrom('81.2.69.170')->country_code);
    }

    public function test_click_is_still_recorded_as_unknown_when_geolocation_fails(): void
    {
        Schema::drop('ip_country_ranges');

        $click = $this->clickFrom('81.2.69.160');

        $this->assertNull($click->country_code);
        $this->assertSame('Unknown', $click->country_name);
    }

    public function test_import_command_replaces_ranges_and_merges_neighbours(): void
    {
        $file = $this->gzipFile([
            '0.0.0.0,0.255.255.255,ZZ',
            '1.0.0.0,1.0.0.255,AU',
            '1.0.1.0,1.0.3.255,AU',
            '1.0.4.0,1.0.7.255,CN',
            'not,a,valid,line',
            '81.2.69.0,81.2.69.255,GB',
            '2001:200::,2001:200:ffff:ffff:ffff:ffff:ffff:ffff,JP',
        ]);

        $this->artisan('app:import-geoip', ['--file' => $file])
            ->expectsOutputToContain('4 IP ranges imported')
            ->assertSuccessful();

        $this->assertSame(4, IpCountryRange::count());
        $this->assertSame('AU', IpCountryRange::lookup('1.0.2.10'));
        $this->assertSame('CN', IpCountryRange::lookup('1.0.5.1'));
        $this->assertSame('JP', IpCountryRange::lookup('2001:200::1'));
        $this->assertNull(IpCountryRange::lookup('0.1.2.3'));
        $this->assertNull(IpCountryRange::lookup('8.8.8.8'));
    }

    public function test_import_keeps_existing_data_when_the_file_has_no_ranges(): void
    {
        $this->artisan('app:import-geoip', ['--file' => $this->gzipFile(['0.0.0.0,0.255.255.255,ZZ'])])->assertFailed();
        $this->artisan('app:import-geoip', ['--file' => 'missing-file.csv.gz'])->assertFailed();

        $this->assertSame(4, IpCountryRange::count());
    }

    public function test_import_downloads_the_latest_database_and_falls_back_to_last_month(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 2));

        Http::fake([
            'download.db-ip.com/free/dbip-country-lite-2026-11.csv.gz' => Http::response('', 404),
            'download.db-ip.com/free/dbip-country-lite-2026-10.csv.gz' => Http::response(gzencode("5.5.5.0,5.5.5.255,DE\n")),
        ]);

        $this->artisan('app:import-geoip')->assertSuccessful();

        $this->assertSame('DE', IpCountryRange::lookup('5.5.5.5'));
        Http::assertSentCount(2);
        $this->assertFileDoesNotExist(storage_path('app/private/dbip-country-lite.csv.gz'));
    }
}
