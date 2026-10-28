<?php

namespace App\Domain;

/**
 * The 7 stages, who owns each one, and each stage's checklist.
 * item => [required, owner role override or null]
 */
class Workflow
{
    public const STAGES = [
        1 => ['owner' => 'sales', 'items' => ['first_contact' => [false, null]]],
        2 => ['owner' => 'sales', 'items' => ['rates_requested' => [false, 'pricing'], 'quotation_sent' => [false, null], 'trial_offered' => [false, null], 'client_approval' => [true, null]]],
        3 => ['owner' => 'ops', 'items' => ['sales_handover' => [false, 'sales'], 'supplier_ready' => [false, null], 'acid_nafeza' => [true, null], 'booking_done' => [false, null], 'docs_reviewed' => [false, null]]],
        4 => ['owner' => 'ops', 'items' => ['bl_checked' => [false, null], 'client_informed' => [false, null], 'docs_to_broker' => [false, null], 'proforma_ready' => [false, null]]],
        5 => ['owner' => 'customs', 'items' => ['arrival_notice' => [false, null], 'do_collected' => [false, null], 'customs_cleared' => [false, null], 'release_approved' => [true, 'accounts'], 'delivered' => [true, null], 'actual_costs_handed' => [false, 'ops']]],
        6 => ['owner' => 'accounts', 'items' => ['final_invoice' => [false, null], 'payment_collected' => [true, null], 'suppliers_paid' => [false, null]]],
        7 => ['owner' => 'sales', 'items' => ['after_sale_call' => [true, null], 'feedback_logged' => [false, null], 'next_order_asked' => [false, null], 'referrals_asked' => [false, null]]],
    ];

    public static function owner(int $stage): string
    {
        return self::STAGES[$stage]['owner'];
    }

    public static function items(int $stage): array
    {
        return self::STAGES[$stage]['items'];
    }

    public static function stageOf(string $item): ?int
    {
        foreach (self::STAGES as $n => $s) {
            if (isset($s['items'][$item])) {
                return $n;
            }
        }
        return null;
    }

    public static function itemOwner(string $item): string
    {
        $n = self::stageOf($item);
        return self::STAGES[$n]['items'][$item][1] ?? self::owner($n);
    }

    public static function required(string $item): bool
    {
        $n = self::stageOf($item);
        return (bool) self::STAGES[$n]['items'][$item][0];
    }
}
