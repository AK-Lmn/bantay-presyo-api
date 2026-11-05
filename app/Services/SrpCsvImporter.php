<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\Srp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Imports an SRP bulletin from CSV. The whole file is rejected if any row is invalid.
 *
 * Expected header: commodity, price, and optionally unit and effective_date.
 */
class SrpCsvImporter
{
    public const MAX_ROWS = 5000;

    /**
     * @return array{created: int, updated: int}
     *
     * @throws ValidationException
     */
    public function import(string $path, ?string $defaultEffectiveDate = null): array
    {
        $rows = $this->parse($path, $defaultEffectiveDate);

        $result = DB::transaction(function () use ($rows) {
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                $srp = Srp::query()->updateOrCreate(
                    ['commodity_id' => $row['commodity_id'], 'effective_date' => $row['effective_date']],
                    ['price' => $row['price']],
                );

                $srp->wasRecentlyCreated ? $created++ : $updated++;
            }

            return ['created' => $created, 'updated' => $updated];
        });

        PriceAnalyticsService::clearCache();

        return $result;
    }

    /**
     * @return list<array{commodity_id: int, price: string, effective_date: string}>
     *
     * @throws ValidationException
     */
    private function parse(string $path, ?string $defaultEffectiveDate): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'The file could not be read.']);
        }

        try {
            $header = fgetcsv($handle, escape: '\\');
            $columns = $this->columns($header === false ? [] : $header);
            $commodities = $this->commodityLookup();

            $rows = [];
            $errors = [];
            $seen = [];
            $line = 1;

            while (($record = fgetcsv($handle, escape: '\\')) !== false) {
                $line++;

                if ($record === [null] || implode('', $record) === '') {
                    continue;
                }

                if (count($rows) + count($errors) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages(['file' => 'A bulletin can have at most '.self::MAX_ROWS.' rows.']);
                }

                $value = fn (string $column) => isset($columns[$column]) ? trim((string) ($record[$columns[$column]] ?? '')) : '';

                $error = null;
                $commodityId = $this->matchCommodity($commodities, $value('commodity'), $value('unit'), $error);
                $price = $error === null ? $this->parsePrice($value('price'), $error) : null;
                $date = $error === null ? $this->parseDate($value('effective_date') ?: $defaultEffectiveDate, $error) : null;

                if ($error === null && isset($seen[$commodityId.'|'.$date])) {
                    $error = "duplicate of row {$seen[$commodityId.'|'.$date]}.";
                }

                if ($error !== null || $commodityId === null || $price === null || $date === null) {
                    $errors[] = "Row {$line}: {$error}";

                    continue;
                }

                $seen[$commodityId.'|'.$date] = $line;
                $rows[] = ['commodity_id' => $commodityId, 'price' => $price, 'effective_date' => $date];
            }
        } finally {
            fclose($handle);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'The file has no data rows.']);
        }

        return $rows;
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array<string, int>
     *
     * @throws ValidationException
     */
    private function columns(array $header): array
    {
        $columns = [];

        foreach ($header as $index => $name) {
            $name = strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $name)));
            $columns[$name] = $index;
        }

        $missing = array_diff(['commodity', 'price'], array_keys($columns));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'The header row is missing: '.implode(', ', $missing).'.',
            ]);
        }

        return $columns;
    }

    /**
     * @return array<string, list<array{id: int, unit: string}>>
     */
    private function commodityLookup(): array
    {
        $lookup = [];

        foreach (Commodity::query()->get(['id', 'name', 'unit']) as $commodity) {
            $lookup[mb_strtolower($commodity->name)][] = ['id' => $commodity->id, 'unit' => mb_strtolower($commodity->unit)];
        }

        return $lookup;
    }

    /**
     * @param  array<string, list<array{id: int, unit: string}>>  $commodities
     */
    private function matchCommodity(array $commodities, string $name, string $unit, ?string &$error): ?int
    {
        if ($name === '') {
            $error = 'commodity is empty.';

            return null;
        }

        $matches = $commodities[mb_strtolower($name)] ?? [];

        if ($unit !== '') {
            $matches = array_values(array_filter($matches, fn (array $match) => $match['unit'] === mb_strtolower($unit)));
        }

        if (count($matches) === 1) {
            return $matches[0]['id'];
        }

        $error = $matches === []
            ? "unknown commodity \"{$name}\"".($unit !== '' ? " ({$unit})" : '').'.'
            : "\"{$name}\" matches more than one commodity; add a unit column.";

        return null;
    }

    private function parsePrice(string $raw, ?string &$error): ?string
    {
        $clean = str_replace([',', ' '], '', preg_replace('/^(₱|PHP|Php)/u', '', $raw) ?? '');

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $clean) || (float) $clean <= 0 || (float) $clean > 100000) {
            $error = "invalid price \"{$raw}\".";

            return null;
        }

        return number_format((float) $clean, 2, '.', '');
    }

    private function parseDate(?string $raw, ?string &$error): ?string
    {
        if ($raw === null || $raw === '') {
            $error = 'effective_date is empty.';

            return null;
        }

        $date = Carbon::canBeCreatedFromFormat($raw, 'Y-m-d') ? Carbon::createFromFormat('Y-m-d', $raw) : null;

        if ($date === null || $date->format('Y-m-d') !== $raw) {
            $error = "invalid effective_date \"{$raw}\", expected YYYY-MM-DD.";

            return null;
        }

        return $raw;
    }
}
