<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTestimonial;
use Illuminate\Http\Request;

class AdminTestimonialsController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminTestimonial::query()->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $testimonials = $query->paginate(20);
        return view('admin.testimonials.index', compact('testimonials'));
    }

    public function create()
    {
        return view('admin.testimonials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'rating' => 'nullable|integer|min:1|max:5',
            'published_at' => 'nullable|date',
        ]);

        $testimonial = AdminTestimonial::create([
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'status' => $validated['status'],
            'rating' => $validated['rating'] ?? null,
            'published_at' => $validated['status'] === 'published'
                ? ($validated['published_at'] ? $validated['published_at'] : now())
                : null,
            'created_by' => auth('admin')->id(),
        ]);

        return redirect()->route('admin.testimonials.index')->with('success', __('Testimonial created successfully.'));
    }

    public function edit(AdminTestimonial $testimonial)
    {
        return view('admin.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, AdminTestimonial $testimonial)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'rating' => 'nullable|integer|min:1|max:5',
            'published_at' => 'nullable|date',
        ]);

        $testimonial->update([
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'status' => $validated['status'],
            'rating' => $validated['rating'] ?? null,
            'published_at' => $validated['status'] === 'published'
                ? ($validated['published_at'] ? $validated['published_at'] : now())
                : null,
        ]);

        return redirect()->route('admin.testimonials.index')->with('success', __('Testimonial updated successfully.'));
    }

    public function destroy(AdminTestimonial $testimonial)
    {
        $testimonial->delete();
        return redirect()->route('admin.testimonials.index')->with('success', __('Testimonial deleted successfully.'));
    }
}

