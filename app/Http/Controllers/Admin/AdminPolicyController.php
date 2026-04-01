<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PolicyPage;
use Illuminate\Http\Request;

class AdminPolicyController extends Controller
{
    public function editPrivacy()
    {
        $page = PolicyPage::firstOrCreate(
            ['key' => 'privacy_policy'],
            ['title' => 'Privacy Policy', 'content' => '']
        );

        return view('admin.policies.edit', [
            'page' => $page,
            'moduleLabel' => __('Privacy Policy'),
        ]);
    }

    public function updatePrivacy(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $page = PolicyPage::firstOrCreate(
            ['key' => 'privacy_policy'],
            ['title' => 'Privacy Policy', 'content' => '']
        );

        $page->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'updated_by' => auth('admin')->id(),
        ]);

        return redirect()->route('admin.privacy-policy.edit')->with('success', __('Privacy Policy updated.'));
    }

    public function editTerms()
    {
        $page = PolicyPage::firstOrCreate(
            ['key' => 'terms_conditions'],
            ['title' => 'Terms & Conditions', 'content' => '']
        );

        return view('admin.policies.edit', [
            'page' => $page,
            'moduleLabel' => __('Terms & Conditions'),
        ]);
    }

    public function updateTerms(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $page = PolicyPage::firstOrCreate(
            ['key' => 'terms_conditions'],
            ['title' => 'Terms & Conditions', 'content' => '']
        );

        $page->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'updated_by' => auth('admin')->id(),
        ]);

        return redirect()->route('admin.terms-conditions.edit')->with('success', __('Terms & Conditions updated.'));
    }
}

