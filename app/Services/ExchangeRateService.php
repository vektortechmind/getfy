<?php

namespace App\Services;

use App\Support\CheckoutCurrencyCatalog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const FRANKFURTER_URL = 'https://api.frankfurter.app/latest';

    private const CHUNK_SIZE = 30;

    /**
     * Taxas BRL → moeda estrangeira (rate_to_brl: unidades da moeda por 1 BRL).
     *
     * @param  list<string>  $codes  Códigos ISO 4217 (sem BRL)
     * @return array<string, float>
     */
    public function fetchRatesFromBrl(array $codes): array
    {
        $codes = array_values(array_unique(array_filter(array_map(
            fn ($c) => strtoupper(trim((string) $c)),
            $codes
        ), fn ($c) => $c !== '' && $c !== 'BRL')));

        if ($codes === []) {
            return [];
        }

        $out = [];
        foreach (array_chunk($codes, self::CHUNK_SIZE) as $chunk) {
            $part = $this->fetchChunk($chunk);
            foreach ($part as $code => $rate) {
                $out[$code] = $rate;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $codes
     * @return array<string, float>
     */
    private function fetchChunk(array $codes): array
    {
        $to = implode(',', $codes);
        try {
            $response = Http::timeout(15)->get(self::FRANKFURTER_URL, [
                'from' => 'BRL',
                'to' => $to,
            ]);
            if (! $response->successful()) {
                Log::warning('ExchangeRateService: Frankfurter HTTP error', [
                    'status' => $response->status(),
                    'codes' => $codes,
                ]);

                return [];
            }
            $data = $response->json();
            $rates = is_array($data['rates'] ?? null) ? $data['rates'] : [];
            $out = [];
            foreach ($codes as $code) {
                if (isset($rates[$code]) && is_numeric($rates[$code])) {
                    $out[$code] = (float) $rates[$code];
                }
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('ExchangeRateService: Frankfurter exception', [
                'message' => $e->getMessage(),
                'codes' => $codes,
            ]);

            return [];
        }
    }

    /**
     * Mescla taxas em linhas de moeda do tenant.
     *
     * @param  list<array{code: string, symbol?: string, label?: string, rate_to_brl?: float}>  $existing
     * @return list<array{code: string, symbol: string, label: string, rate_to_brl: float}>
     */
    public function applyRatesToCurrencyRows(array $existing, ?array $codesToFetch = null): array
    {
        $byCode = [];
        foreach ($existing as $row) {
            if (! is_array($row) || empty($row['code'])) {
                continue;
            }
            $code = strtoupper(trim((string) $row['code']));
            $meta = CheckoutCurrencyCatalog::metadataFor($code);
            $byCode[$code] = [
                'code' => $code,
                'symbol' => (string) ($row['symbol'] ?? $meta['symbol']),
                'label' => (string) ($row['label'] ?? $meta['label']),
                'rate_to_brl' => (float) ($row['rate_to_brl'] ?? 0),
            ];
        }

        $toFetch = $codesToFetch ?? array_keys($byCode);
        $toFetch = array_values(array_filter($toFetch, fn ($c) => strtoupper($c) !== 'BRL'));
        $fetched = $this->fetchRatesFromBrl($toFetch);

        foreach ($fetched as $code => $rate) {
            if (! isset($byCode[$code])) {
                $meta = CheckoutCurrencyCatalog::metadataFor($code);
                $byCode[$code] = [
                    'code' => $code,
                    'symbol' => $meta['symbol'],
                    'label' => $meta['label'],
                    'rate_to_brl' => $rate,
                ];
            } else {
                $byCode[$code]['rate_to_brl'] = $rate;
            }
        }

        if (! isset($byCode['BRL'])) {
            $meta = CheckoutCurrencyCatalog::metadataFor('BRL');
            $byCode['BRL'] = [
                'code' => 'BRL',
                'symbol' => $meta['symbol'],
                'label' => $meta['label'],
                'rate_to_brl' => 1.0,
            ];
        } else {
            $byCode['BRL']['rate_to_brl'] = 1.0;
        }

        return CheckoutCurrencyCatalog::mergeTenantCurrencies(array_values($byCode));
    }

    /**
     * Importa todas as moedas suportadas do catálogo com taxas da API (onde disponível).
     *
     * @return list<array{code: string, symbol: string, label: string, rate_to_brl: float}>
     */
    public function buildFullCatalogWithRates(): array
    {
        $rows = CheckoutCurrencyCatalog::defaultTenantCurrencyRows();
        $codes = array_values(array_filter(
            array_map(fn ($r) => $r['code'], $rows),
            fn ($c) => $c !== 'BRL'
        ));

        return $this->applyRatesToCurrencyRows($rows, $codes);
    }
}
