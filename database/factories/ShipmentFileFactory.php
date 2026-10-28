<?php

namespace Database\Factories;

use App\Domain\RefGenerator;
use App\Models\Client;
use App\Models\ShipmentFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A complete FCL request that passes InquiryValidator. Stage/checks are set
 * through ShipmentFile::withStageWrite() in afterMaking so the model guard stays on.
 *
 * @extends Factory<ShipmentFile>
 */
class ShipmentFileFactory extends Factory
{
    protected $model = ShipmentFile::class;

    public function definition(): array
    {
        return [
            'number' => fn () => RefGenerator::fileNumber(),
            'ref' => fn () => 'TS-'.now()->format('ym').'-'.fake()->unique()->numberBetween(1, 99999),
            'client_id' => Client::factory(),
            'sales_id' => fn (array $a) => Client::withoutGlobalScopes()->find($a['client_id'])->owner_id,
            'direction' => 'import',
            'service' => 'fcl',
            'addons' => ['clearance'],
            'origin' => 'CNSHA',
            'destination' => 'EGALY',
            'incoterm' => 'FOB',
            'inq' => ['rows' => [['type' => "40'HC", 'count' => 2]], 'cargo' => 'general', 'commodity' => 'PP granules', 'ready' => now()->addDays(10)->toDateString(), 'free_days' => 14, 'freight_terms' => 'collect', 'pay' => 'cash_in_advance'],
            'pricing_status' => 'pending',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ShipmentFile $f) {
            if ($f->stage === null) {
                $f->forceStageAttributes(['stage' => 1, 'status' => 'open', 'checks' => ['first_contact' => true], 'stage_entered_at' => now()]);
            }
        })->afterCreating(fn () => null);
    }

    /** Put the file directly at a stage with that stage's earlier checks ticked (tests only). */
    public function atStage(int $stage, array $checks = []): static
    {
        return $this->afterMaking(function (ShipmentFile $f) use ($stage, $checks) {
            $f->forceStageAttributes(['stage' => $stage, 'status' => 'open', 'checks' => $checks + ['first_contact' => true], 'stage_entered_at' => now()]);
        });
    }
}
