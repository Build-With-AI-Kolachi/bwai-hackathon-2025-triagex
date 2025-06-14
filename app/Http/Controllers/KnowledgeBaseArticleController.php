<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgeBaseArticleController extends Controller
{
    public function index()
    {
        $articles = KnowledgeBaseArticle::orderBy('title')->get();
        return Inertia::render('KnowledgeBase/Index', [
            'articles' => $articles,
        ]);
    }

    public function create()
    {
        return Inertia::render('KnowledgeBase/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'keywords' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        KnowledgeBaseArticle::create([
            'title' => $request->title,
            'content' => $request->content,
            'keywords' => $request->keywords ? explode(',', $request->keywords) : null,
            'category' => $request->category,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('knowledge-base.index')->with('success', 'Article created successfully.');
    }

    public function edit(KnowledgeBaseArticle $knowledgeBase) // Using route model binding
    {
        return Inertia::render('KnowledgeBase/Edit', [
            'article' => $knowledgeBase,
        ]);
    }

    public function update(Request $request, KnowledgeBaseArticle $knowledgeBase)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'keywords' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $knowledgeBase->update([
            'title' => $request->title,
            'content' => $request->content,
            'keywords' => $request->keywords ? explode(',', $request->keywords) : null,
            'category' => $request->category,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('knowledge-base.index')->with('success', 'Article updated successfully.');
    }

    public function destroy(KnowledgeBaseArticle $knowledgeBase)
    {
        $knowledgeBase->delete();
        return redirect()->route('knowledge-base.index')->with('success', 'Article deleted successfully.');
    }
}