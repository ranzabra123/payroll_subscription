<?php

namespace App\Controllers\Superadmin;

use App\Models\Landlord\CompanyModel;
use CodeIgniter\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $model = new CompanyModel();

        $companies = $model->findAll();
        $counts    = ['trial' => 0, 'active' => 0, 'suspended' => 0, 'cancelled' => 0];

        foreach ($companies as $c) {
            $counts[$c['status']] = ($counts[$c['status']] ?? 0) + 1;
        }

        return view('superadmin/dashboard/index', [
            'title'  => 'Superadmin Dashboard',
            'counts' => $counts,
            'total'  => count($companies),
            'recent' => array_slice(array_reverse($companies), 0, 5),
        ]);
    }
}
