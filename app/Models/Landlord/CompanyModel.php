<?php

namespace App\Models\Landlord;

use CodeIgniter\Model;

class CompanyModel extends Model
{
    protected $DBGroup       = 'landlord';
    protected $table         = 'companies';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'name', 'slug', 'db_host', 'db_name', 'db_username', 'db_password',
        'status', 'subscription_plan', 'trial_ends_at', 'subscription_expires_at',
        'contact_email',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'              => 'required|min_length[2]|max_length[150]',
        'slug'              => 'required|regex_match[/^[a-z0-9_]{2,40}$/]|is_unique[companies.slug,id,{id}]',
        'status'            => 'required|in_list[trial,active,suspended,cancelled]',
        'subscription_plan' => 'permit_empty|in_list[starter,business,enterprise]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Find an active (non-deleted) company by its login slug/code.
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', strtolower($slug))->first();
    }

    /**
     * Whether a company row is currently blocked from access
     * (suspended/cancelled, or past its subscription expiry).
     */
    public function isBlocked(array $company): bool
    {
        if (in_array($company['status'], ['suspended', 'cancelled'], true)) {
            return true;
        }

        if (!empty($company['subscription_expires_at'])
            && strtotime($company['subscription_expires_at']) < time()) {
            return true;
        }

        return false;
    }
}
