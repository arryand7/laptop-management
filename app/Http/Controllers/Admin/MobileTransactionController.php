<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\LaptopTransactionController as StaffTransactionController;

class MobileTransactionController extends StaffTransactionController
{
    protected function viewName(): string
    {
        return 'admin.transactions.mobile';
    }
}
