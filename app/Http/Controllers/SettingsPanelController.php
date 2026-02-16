<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsPanelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Settings panel entry: redirect to Company Details (no dashboard page).
     */
    public function index()
    {
        return redirect()->route('settings-panel.company');
    }
}
