<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificacaoController extends Controller
{

    // NOT IMPLEMENTED

    public function __construct()
    {
        header("access-control-allow-origin: https://sandbox.pagseguro.uol.com.br");
    }

    public function index(Request $request)
    {
    }

    public function store(Request $request)
    {
        dd($request, __METHOD__);
    }
}
