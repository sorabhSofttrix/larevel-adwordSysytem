<?php

namespace App\Http\Controllers;

use App\AccountStatusChange;
use Illuminate\Http\Request;

class AccountStatusChangeController extends Controller
{
    /**
     * Create a new  instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt', ['except' => ['login']]);
    }

}
