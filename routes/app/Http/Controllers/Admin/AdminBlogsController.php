<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminBlog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminBlogsController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminBlog::query()->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $blogs = $query->paginate(20);
        return view('admin.blogs.index', compact('blogs'));
    }

    public function create()
    {
        return view('admin.blogs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
        ]);

        $slug = Str::slug($validated['title']);

        // Ensure unique slug even if two titles are similar.
        $baseSlug = $slug;
        $i = 0;
        while (AdminBlog::query()->where('slug', $slug)->exists()) {
            $i++;
            $slug = $baseSlug . '-' . $i;
        }

        $blog = AdminBlog::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published'
                ? ($validated['published_at'] ? $validated['published_at'] : now())
                : null,
            'created_by' => auth('admin')->id(),
        ]);

        return redirect()->route('admin.blogs.index')->with('success', __('Blog created successfully.'));
    }

    public function edit(AdminBlog $blog)
    {
        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(Request $request, AdminBlog $blog)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
        ]);

        $slug = Str::slug($validated['title']);

        $existingSlug = AdminBlog::query()
            ->where('slug', $slug)
            ->where('id', '!=', $blog->id)
            ->exists();

        if ($existingSlug) {
            $slug = $slug . '-' . $blog->id;
        }

        $blog->update([
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published'
                ? ($validated['published_at'] ? $validated['published_at'] : now())
                : null,
        ]);

        return redirect()->route('admin.blogs.index')->with('success', __('Blog updated successfully.'));
    }

    public function destroy(AdminBlog $blog)
    {
        $blog->delete();
        return redirect()->route('admin.blogs.index')->with('success', __('Blog deleted successfully.'));
    }
}

