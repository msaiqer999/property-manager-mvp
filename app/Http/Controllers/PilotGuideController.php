<?php

namespace App\Http\Controllers;

class PilotGuideController extends Controller
{
    public function __invoke()
    {
        return view('pilot-guide.index');
    }
}
