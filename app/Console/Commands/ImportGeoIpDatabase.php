<?php

namespace App\Console\Commands;

use App\Models\IpCountryRange;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

#[Signature('app:import-geoip {--file= : Import a downloaded dbip-country-lite file (.csv or .csv.gz) instead of downloading the latest one}')]
#[Description('Download and import the free DB-IP country database used to show visitor countries in Support analytics')]
class ImportGeoIpDatabase extends Command
{
    /**
     * Rows inserted per query.
     */
    private const CHUNK_SIZE = 1000;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $downloaded = null;

        try {
            $path = $this->option('file') ?: ($downloaded = $this->download());

            if (! is_file($path) || ! is_readable($path)) {
                throw new RuntimeException("Cannot read {$path}.");
            }

            $this->components->info('Importing IP ranges. This can take a minute or two');
            $count = $this->import($path);
        } catch (Throwable $exception) {
            $this->components->error('GeoIP import failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            if ($downloaded !== null && is_file($downloaded)) {
                @unlink($downloaded);
            }
        }

        $this->components->info(number_format($count).' IP ranges imported. IP geolocation by DB-IP (https://db-ip.com), licensed under CC BY 4.0.');

        return self::SUCCESS;
    }

    /**
     * Download this month's database, falling back to last month's before it is published.
     */
    private function download(): string
    {
        $target = storage_path('app/private/dbip-country-lite.csv.gz');

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        foreach ([now(), now()->subMonthNoOverflow()] as $month) {
            $url = sprintf(config('analytics.geoip.download_url'), $month->format('Y-m'));
            $this->components->info("Downloading {$url}");

            if (Http::timeout(300)->sink($target)->get($url)->successful() && filesize($target) > 0) {
                return $target;
            }
        }

        throw new RuntimeException('The DB-IP database could not be downloaded.');
    }

    /**
     * Replace all ranges in one transaction, so lookups never see a half-imported table.
     * Consecutive ranges of the same country are merged to keep the table small.
     */
    private function import(string $path): int
    {
        $handle = gzopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Cannot open {$path}.");
        }

        try {
            return DB::transaction(function () use ($handle): int {
                IpCountryRange::query()->delete();

                $count = 0;
                $chunk = [];
                $pending = null;

                while (($line = gzgets($handle)) !== false) {
                    $range = $this->parse($line);

                    if ($range === null) {
                        continue;
                    }

                    if ($pending !== null && $pending['country_code'] === $range['country_code'] && $this->increment($pending['ip_to']) === $range['ip_from']) {
                        $pending['ip_to'] = $range['ip_to'];

                        continue;
                    }

                    if ($pending !== null) {
                        $chunk[] = $pending;
                    }

                    $pending = $range;

                    if (count($chunk) >= self::CHUNK_SIZE) {
                        IpCountryRange::query()->insert($chunk);
                        $count += count($chunk);
                        $chunk = [];
                    }
                }

                if ($pending !== null) {
                    $chunk[] = $pending;
                }

                if ($chunk !== []) {
                    IpCountryRange::query()->insert($chunk);
                    $count += count($chunk);
                }

                if ($count === 0) {
                    throw new RuntimeException('The file contained no IP ranges. The existing data was kept.');
                }

                return $count;
            });
        } finally {
            gzclose($handle);
        }
    }

    /**
     * One "start,end,country" CSV line, or null for unknown countries and malformed lines.
     *
     * @return array{ip_from: string, ip_to: string, country_code: string}|null
     */
    private function parse(string $line): ?array
    {
        $columns = str_getcsv(trim($line), ',', '"', '');

        if (count($columns) < 3) {
            return null;
        }

        $from = IpCountryRange::toHex($columns[0]);
        $to = IpCountryRange::toHex($columns[1]);
        $code = strtoupper(trim($columns[2]));

        if ($from === null || $to === null || $from > $to || ! IpCountryRange::isKnownCountry($code)) {
            return null;
        }

        return ['ip_from' => $from, 'ip_to' => $to, 'country_code' => $code];
    }

    /**
     * The next address after a hex-encoded one.
     */
    private function increment(string $hex): string
    {
        $bytes = (string) hex2bin($hex);

        for ($i = strlen($bytes) - 1; $i >= 0; $i--) {
            if ($bytes[$i] !== "\xff") {
                $bytes[$i] = chr(ord($bytes[$i]) + 1);

                return bin2hex($bytes);
            }

            $bytes[$i] = "\0";
        }

        return bin2hex($bytes);
    }
}
