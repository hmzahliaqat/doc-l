<?php

namespace App\Http\Controllers;

use App\Models\SuperAdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    /**
     * Test the SuperAdminSettings integration with email templates
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function testEmailTemplate()
    {
        // This will be automatically populated by the SuperAdminSettingsServiceProvider
        return view('emails.test-template');
    }
}
