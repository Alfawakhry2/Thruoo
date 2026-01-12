<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    // dd(request()->host());
    echo request()->host();
});
