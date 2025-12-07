<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Newsletter\NewsletterFacade as NewsLetter;

class NewsLetterController extends Controller
{
    //
    public function store(Request $request)
    {
        // Check if the email is not already subscribed
        if (!NewsLetter::isSubscribed($request->value)) {
            // Subscribe the email
            NewsLetter::subscribePending($request->value);
            return 'Thanks For Subscribing! Check your email for next steps!';
        }

        // Return a message indicating that the email is already subscribed
        return 'Sorry, you have already subscribed!';
    }
}
