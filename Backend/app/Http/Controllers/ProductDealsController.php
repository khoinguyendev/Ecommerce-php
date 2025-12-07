<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductDealsController extends Controller
{
    //
    function hotDeals()
    {
        // $now = Carbon::now();
        // $hotDeal = Deal::where('starts', '<', $now)
        //     ->where('ends', '>', $now)
        //     ->orderBy('sale', 'desc')
        //     ->first();

        // return $hotDeal;
    }
}
