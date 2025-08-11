<?php

namespace Database\Seeders;

use App\Models\PrivacyPolicy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrivacyPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if a privacy policy already exists
        if (PrivacyPolicy::count() === 0) {
            PrivacyPolicy::create([
                'content' => '<h1>Privacy Policy</h1>
                <p>Last updated: August 10, 2025</p>

                <h2>Introduction</h2>
                <p>Welcome to our Privacy Policy. This document explains how we collect, use, and protect your personal information when you use our services.</p>

                <h2>Information We Collect</h2>
                <p>We may collect the following types of information:</p>
                <ul>
                    <li>Personal identification information (Name, email address, phone number, etc.)</li>
                    <li>Usage data and analytics</li>
                    <li>Cookies and tracking information</li>
                </ul>

                <h2>How We Use Your Information</h2>
                <p>We use the collected information for various purposes including:</p>
                <ul>
                    <li>Providing and maintaining our service</li>
                    <li>Notifying you about changes to our service</li>
                    <li>Providing customer support</li>
                    <li>Improving our service</li>
                </ul>

                <h2>Data Security</h2>
                <p>We implement appropriate security measures to protect your personal information. However, no method of transmission over the Internet or electronic storage is 100% secure.</p>

                <h2>Changes to This Privacy Policy</h2>
                <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.</p>

                <h2>Contact Us</h2>
                <p>If you have any questions about this Privacy Policy, please contact us.</p>'
            ]);
        }
    }
}
