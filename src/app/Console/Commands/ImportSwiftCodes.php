<?php

namespace App\Console\Commands;

use App\Models\SwiftCode;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class ImportSwiftCodes extends Command
{
    protected $signature = 'import:swiftcodes';
    protected $description = 'Import SWIFT codes from theswiftcodes.com';

    private const BASE_URL         = 'https://www.theswiftcodes.com';
    private const SYSTEM_USER_ID   = '00000000-0000-0000-0000-000000000001'; // System user UUID

    public function handle()
    {
        // Очищаем таблицу перед импортом
        SwiftCode::truncate();
        $this->info('Cleared existing SWIFT codes.');

        // Настройка Guzzle с увеличенными таймаутами
        $guzzle = new GuzzleClient([
            'timeout'         => 30,
            'connect_timeout' => 5,
        ]);

        $this->info('Fetching country list…');
        $res     = $guzzle->get(self::BASE_URL . '/browse-by-country/');
        $crawler = new Crawler($res->getBody()->getContents(), self::BASE_URL . '/browse-by-country/');

        // Сбор ссылок на страны
        $countryLinks = [];
        $crawler->filter('a')->each(function (Crawler $node) use (&$countryLinks) {
            $href = $node->attr('href');
            if (!preg_match('#^/\w#', $href)) return;

            $slug = trim(parse_url($href, PHP_URL_PATH), '/');
            if (!preg_match('/^[a-z\-]+$/', $slug) || str_contains($slug, 'browse') || str_contains($slug, 'checker')) {
                return;
            }

            $countryLinks[self::BASE_URL . $href] = trim($node->text());
        });

        $this->info('Found countries: ' . count($countryLinks));
        foreach ($countryLinks as $countryUrl => $countryName) {
            $countryCode = strtoupper(trim($countryName));
            $this->info("Importing country: {$countryCode}");

            $this->importCountry($guzzle, $countryUrl, $countryCode);
            sleep(1);
        }

        $this->info('Done.');
    }

    protected function importCountry(GuzzleClient $guzzle, string $countryUrl, string $countryCode)
    {
        $res     = $guzzle->get($countryUrl);
        $crawler = new Crawler($res->getBody()->getContents(), $countryUrl);

        $rows = $crawler->filter('table tbody tr');
        $this->info('  Found rows: ' . $rows->count());

        $rows->each(function (Crawler $tr) use ($guzzle, $countryCode) {
            $cols = $tr->filter('td');
            if ($cols->count() < 5) return;

            $bankName = trim($cols->eq(1)->text());
            $city     = trim($cols->eq(2)->text());
            $branch   = trim($cols->eq(3)->text());
            $swift    = trim($cols->eq(4)->text());

            if (strlen($swift) < 8) return;

            // Получение детальной страницы для адреса
            $addressText = $branch;
            try {
                $detailHref    = $cols->eq(4)->filter('a')->attr('href');
                $detailUrl     = self::BASE_URL . $detailHref;
                $detailRes     = $guzzle->get($detailUrl);
                $detailCrawler = new Crawler($detailRes->getBody()->getContents(), $detailUrl);

                $addressNode = $detailCrawler->filterXPath("//tr[th[contains(normalize-space(.), 'Address')]]/td");
                if ($addressNode->count()) {
                    $addressText = trim($addressNode->text());
                }
            } catch (ConnectException | TransferException $e) {
                $this->warn("    Warning: failed to fetch detail for {$swift}, using branch as address.");
            }

            // Сохраняем запись с системным пользователем
            $record = SwiftCode::create([
                'swift_code' => $swift,
                'bank_name'  => $bankName,
                'country'    => $countryCode,
                'city'       => $city,
                'address'    => $addressText,
                'created_by' => self::SYSTEM_USER_ID,
                'updated_by' => self::SYSTEM_USER_ID,
            ]);

            // Логируем результаты
            $this->line('    Imported: ' . json_encode([
                    'swift_code' => $record->swift_code,
                    'bank_name'  => $record->bank_name,
                    'country'    => $record->country,
                    'city'       => $record->city,
                    'address'    => $record->address,
                    'created_at' => $record->created_at?->toDateTimeString(),
                    'updated_at' => $record->updated_at?->toDateTimeString(),
                ], JSON_UNESCAPED_UNICODE));
        });
    }
}
