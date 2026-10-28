<?php

namespace App\Domain;

use App\Models\ShipmentFile;
use App\Support\Dates;
use Illuminate\Support\Carbon;

/**
 * The one completeness check for a quote request. The browser runs the same
 * rules for instant feedback; this result is the one that counts.
 * Returns translation keys (with params) so both languages work.
 */
class InquiryValidator
{
    public const INCOTERMS_SEA = ['EXW', 'FCA', 'FAS', 'FOB', 'CFR', 'CIF', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP'];

    public const INCOTERMS_OTHER = ['EXW', 'FCA', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP'];

    public const SEA = ['fcl', 'lcl', 'bulk', 'roro', 'se'];

    public const CONTAINERS = ["20'GP", "40'GP", "40'HC", "45'HC", "20'RF", "40'RF"];

    /** @return array<int, array{0:string,1:array}> */
    public static function problems(array $f): array
    {
        $p = [];
        $q = $f['inq'] ?? [];
        $service = $f['service'] ?? null;
        $addons = $f['addons'] ?? [];

        if (empty($f['client_id'])) {
            $p[] = ['rfq.p_client', []];
        }
        if (! $service || ! in_array($service, ShipmentFile::MAIN_SERVICES, true)) {
            $p[] = [$addons === ['insurance'] ? 'rfq.p_ins_alone' : 'rfq.p_service', []];
        }
        if (array_diff($addons, ShipmentFile::ADDONS)) {
            $p[] = ['rfq.p_service', []];
        }

        $rows = $q['rows'] ?? [];
        if (! $rows) {
            $p[] = ['rfq.p_rows', []];
        }
        foreach (array_values($rows) as $i => $r) {
            if (! self::rowOk((string) $service, (array) $r)) {
                $p[] = ['rfq.p_row_incomplete', ['n' => $i + 1]];
            }
        }

        if (empty($f['origin'])) {
            $p[] = ['rfq.p_origin', []];
        }
        if (empty($f['destination'])) {
            $p[] = ['rfq.p_dest', []];
        }
        $inco = $f['incoterm'] ?? null;
        $allowed = in_array($service, self::SEA, true) ? self::INCOTERMS_SEA : self::INCOTERMS_OTHER;
        if (! $inco || ! in_array($inco, $allowed, true)) {
            $p[] = ['rfq.p_incoterm', []];
        }
        if ($inco === 'EXW' && empty($f['pickup_address'])) {
            $p[] = ['rfq.p_exw', []];
        }
        if (in_array('trucking', $addons, true) && empty($f['delivery_address'])) {
            $p[] = ['rfq.p_delivery', []];
        }
        if ($service === 'courier' && (empty($f['pickup_address']) || empty($f['delivery_address']))) {
            $p[] = ['rfq.p_courier_addr', []];
        }

        if (empty($q['ready'])) {
            $p[] = ['rfq.p_ready', []];
        } elseif (! ($f['_skip_past_check'] ?? false) && Carbon::parse($q['ready'], Dates::tz())->lt(Dates::today())) {
            $p[] = ['rfq.p_ready_past', []];
        }

        $cargo = $q['cargo'] ?? 'general';
        if ($cargo === 'hazardous' && (empty($q['un']) || empty($q['imo']) || empty($q['pg']) || empty($q['msds']))) {
            $p[] = ['rfq.p_haz', []];
        }
        if ($cargo === 'reefer' && (! isset($q['temp']) || $q['temp'] === '')) {
            $p[] = ['rfq.p_temp', []];
        }
        if ($cargo === 'oog' && (empty($q['oog_l']) || empty($q['oog_w']) || empty($q['oog_h']))) {
            $p[] = ['rfq.p_oog', []];
        }
        if (in_array($service, ['fcl', 'se'], true) && empty($q['free_days'])) {
            $p[] = ['rfq.p_free', []];
        }
        if (! in_array($q['freight_terms'] ?? null, ['prepaid', 'collect'], true)) {
            $p[] = ['rfq.p_freight', []];
        }
        $pay = $q['pay'] ?? null;
        if (! in_array($pay, ['cash_in_advance', 'against_documents', 'on_arrival', 'credit'], true)) {
            $p[] = ['rfq.p_pay', []];
        } elseif ($pay === 'credit' && empty($q['credit_days'])) {
            $p[] = ['rfq.p_credit', []];
        }

        return $p;
    }

    public static function rowOk(string $service, array $r): bool
    {
        $pos = fn ($k) => isset($r[$k]) && is_numeric($r[$k]) && $r[$k] > 0;
        return match ($service) {
            'fcl' => in_array($r['type'] ?? null, self::CONTAINERS, true) && $pos('count'),
            'se' => in_array($r['type'] ?? null, ['Flat Rack', 'Open Top'], true) && $pos('count'),
            'lcl', 'air', 'courier' => $pos('pieces') && $pos('l') && $pos('w') && $pos('h') && $pos('kg'),
            'land' => in_array($r['truck'] ?? null, ['FTL', 'LTL'], true) && $pos('count') && $pos('kg'),
            'bulk' => $pos('tonnes'),
            'roro' => $pos('count') && $pos('l') && $pos('w') && $pos('h') && $pos('kg'),
            default => false,
        };
    }

    /** Problems as translated strings in the current locale. */
    public static function messages(array $f): array
    {
        return array_map(fn ($x) => __($x[0], $x[1]), self::problems($f));
    }

    public static function forFile(ShipmentFile $file, bool $skipPast = false): array
    {
        return self::problems([
            'client_id' => $file->client_id, 'service' => $file->service, 'addons' => $file->addons ?? [],
            'origin' => $file->origin, 'destination' => $file->destination, 'incoterm' => $file->incoterm,
            'pickup_address' => $file->pickup_address, 'delivery_address' => $file->delivery_address,
            'inq' => $file->inq ?? [], '_skip_past_check' => $skipPast,
        ]);
    }
}
