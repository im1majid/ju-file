<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use App\Models\Share;
use App\Services\ToolCatalog;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $cats = ToolCatalog::categories();
        $totalOps = Operation::count();
        $totalShares = Share::active()->count();
        $catCounts = Operation::selectRaw('category, COUNT(*) as cnt')
            ->groupBy('category')
            ->pluck('cnt', 'category')
            ->all();

        return view('home', compact('cats', 'totalOps', 'totalShares', 'catCounts'));
    }
}

