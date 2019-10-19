<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function Register($int_code = null) {

        return view('register') ->with('invite_code', $int_code);;
    }

    public function tradingview() {
        return view('tradingview');
    }

}
