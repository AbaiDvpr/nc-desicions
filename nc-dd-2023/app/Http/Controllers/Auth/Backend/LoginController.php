<?php

namespace App\Http\Controllers\Auth\Backend;

use App\Http\Controllers\BaseController;

class LoginController extends BaseController
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function __invoke()
    {
        return view('backend.auth.login');
    }
}
