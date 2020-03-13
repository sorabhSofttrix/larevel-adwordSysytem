<?php

namespace App\Http\Controllers;

use App\HourBilling;
use App\AdwordsAccount;
use Illuminate\Http\Request;
use Validator;


class HourBillingController extends Controller
{
    /**
     * Create a new HourBillingController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login']]);
    }
}
