<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\OwnedBySales;
use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * One shipment file (table "files"). Stage, checklist and status may only be
 * changed inside FileStageService::write(); any other save that touches them
 * is rejected here.
 */
class ShipmentFile extends Model
{
    use Audited, HasFactory, OwnedBySales;

    protected $table = 'files';

    protected $salesOwnerColumn = 'sales_id';

    public const MAIN_SERVICES = ['fcl', 'lcl', 'air', 'land', 'bulk', 'roro', 'se', 'courier'];

    public const ADDONS = ['clearance', 'trucking', 'insurance', 'other'];

    public const PROTECTED_FIELDS = ['stage', 'checks', 'status', 'lost_stage', 'lost_category', 'lost_reason', 'closed_at', 'stage_entered_at'];

    protected $fillable = [
        'number', 'ref', 'client_id', 'sales_id', 'ops_id', 'direction', 'service', 'addons', 'origin', 'destination', 'incoterm',
        'pickup_address', 'delivery_address', 'inq', 'ship', 'bl_no', 'carrier', 'etd', 'atd', 'eta', 'acid_no', 'pricing_status',
        'quote_etd', 'quote_transit_days', 'quote_carrier', 'chosen_vendor_quote_id',
    ];

    private static bool $stageWriteOpen = false;

    protected function casts(): array
    {
        return [
            'addons' => 'array', 'inq' => 'array', 'checks' => 'array', 'ship' => 'array',
            'etd' => 'date', 'atd' => 'date', 'eta' => 'date', 'quote_etd' => 'date',
            'stage' => 'integer', 'stage_entered_at' => 'datetime', 'closed_at' => 'datetime', 'margin_approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ShipmentFile $f) {
            if ($f->exists && ! self::$stageWriteOpen && $f->isDirty(self::PROTECTED_FIELDS)) {
                throw new LogicException('Stage, checklist and status can only change through FileStageService.');
            }
        });
    }

    /** Used only by FileStageService. */
    public static function withStageWrite(Closure $fn): mixed
    {
        self::$stageWriteOpen = true;
        try {
            return $fn();
        } finally {
            self::$stageWriteOpen = false;
        }
    }

    public function forceStageAttributes(array $attrs): void
    {
        foreach ($attrs as $k => $v) {
            $this->setAttribute($k, $v);
        }
    }

    public function check(string $key): bool
    {
        return (bool) (($this->checks ?? [])[$key] ?? false);
    }

    public function hasAddon(string $a): bool
    {
        return in_array($a, $this->addons ?? [], true);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function ops(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ops_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(FileHistory::class, 'file_id')->latest('id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(FileNote::class, 'file_id')->latest();
    }

    public function events(): HasMany
    {
        return $this->hasMany(FileEvent::class, 'file_id')->latest('happened_at');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(FileUpdate::class, 'file_id')->latest();
    }

    public function booking(): HasOne
    {
        return $this->hasOne(FileBooking::class, 'file_id');
    }

    public function vendorQuotes(): HasMany
    {
        return $this->hasMany(VendorQuote::class, 'file_id');
    }

    public function costLines(): HasMany
    {
        return $this->hasMany(CostLine::class, 'file_id');
    }

    public function costReviews(): HasMany
    {
        return $this->hasMany(CostReview::class, 'file_id')->latest();
    }

    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class, 'file_id')->orderBy('sort');
    }

    public function quoteRounds(): HasMany
    {
        return $this->hasMany(QuoteRound::class, 'file_id')->orderBy('round');
    }

    public function repriceRequests(): HasMany
    {
        return $this->hasMany(RepriceRequest::class, 'file_id')->latest();
    }

    public function payTerms(): HasOne
    {
        return $this->hasOne(FilePayTerm::class, 'file_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'file_id')->latest();
    }

    public function docChecks(): HasMany
    {
        return $this->hasMany(DocCheck::class, 'file_id')->latest();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'file_id');
    }

    public function chosenQuote(): BelongsTo
    {
        return $this->belongsTo(VendorQuote::class, 'chosen_vendor_quote_id');
    }

    public function openBlockers(): int
    {
        return $this->docChecks()->where('severity', 'blocker')->where('status', 'open')->count();
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }
}
