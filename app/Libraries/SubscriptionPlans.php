<?php

namespace App\Libraries;

/**
 * The fixed set of subscription tiers a company can be assigned. These
 * are plan *definitions* (limits shown for reference/selection) — actual
 * enforcement of the employee/branch caps against a tenant's live data
 * is not implemented here; this only drives what superadmin sees and
 * assigns when creating/upgrading a company.
 */
class SubscriptionPlans
{
    public const PLANS = [
        'starter' => [
            'label'         => 'Starter',
            'max_employees' => 50,
            'max_branches'  => 1,
        ],
        'business' => [
            'label'         => 'Business',
            'max_employees' => 200,
            'max_branches'  => 5,
        ],
        'enterprise' => [
            'label'         => 'Enterprise',
            'max_employees' => null, // unlimited
            'max_branches'  => null, // unlimited
        ],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::PLANS);
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && isset(self::PLANS[$key]);
    }

    public static function label(?string $key): string
    {
        return self::PLANS[$key]['label'] ?? 'No Plan';
    }

    public static function employeesLabel(string $key): string
    {
        $max = self::PLANS[$key]['max_employees'] ?? null;
        return $max === null ? 'Unlimited' : "Up to {$max}";
    }

    public static function branchesLabel(string $key): string
    {
        $max = self::PLANS[$key]['max_branches'] ?? null;
        if ($max === null) {
            return 'Unlimited';
        }
        return $max === 1 ? '1 Branch' : "Up to {$max} Branches";
    }
}
