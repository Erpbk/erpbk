<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminBlog;
use App\Models\AdminCompany;
use App\Models\AdminTestimonial;
use App\Models\AdminUser;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'companies_total' => AdminCompany::query()->count(),
            'companies_pending' => AdminCompany::query()->where('status', 'pending')->count(),
            'blogs_total' => AdminBlog::query()->count(),
            'testimonials_total' => AdminTestimonial::query()->count(),
            'admin_users_total' => AdminUser::query()->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}

