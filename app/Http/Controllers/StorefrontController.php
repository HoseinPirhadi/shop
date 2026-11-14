<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Store/Index');
    }

    public function category(string $slug): Response
    {
        return Inertia::render('Store/Categories/Show', [
            'slug' => $slug,
        ]);
    }

    public function product(string $slug): Response
    {
        return Inertia::render('Store/Products/Show', [
            'slug' => $slug,
        ]);
    }
}
