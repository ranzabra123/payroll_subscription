<?php

namespace App\Libraries;

/**
 * The fixed set of subscription tiers a company can be assigned. Plan
 * *definitions* live here; enforcement of the employee/branch caps
 * against a tenant's live data happens at the point of creation — see
 * EmployeesController::store() and SettingsController::addBranch().
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

    /** Raw employee cap for a plan, or null if unlimited/plan unknown. */
    public static function maxEmployees(?string $key): ?int
    {
        return self::PLANS[$key]['max_employees'] ?? null;
    }

    /** Raw branch cap for a plan, or null if unlimited/plan unknown. */
    public static function maxBranches(?string $key): ?int
    {
        return self::PLANS[$key]['max_branches'] ?? null;
    }
}
