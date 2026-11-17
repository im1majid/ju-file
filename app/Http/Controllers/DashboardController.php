<?php

namespace App\Http\Controllers;

use App\Models\Share;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $q        = trim((string) $request->query('q', ''));
        $category = (string) $request->query('category', '');
        $status   = (string) $request->query('status', 'active');
        $sort     = (string) $request->query('sort', 'newest');

        $categories = ['image' => 'صور', 'video' => 'فيديو', 'audio' => 'صوت', 'document' => 'مستندات', 'other' => 'أخرى'];

        $query = $user->shares();
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('original_name', 'like', "%{$q}%");
            });
        }
        if (isset($categories[$category])) {
            $query->where('category', $category);
        }
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'expired') {
            $query->where('expires_at', '<=', now());
        }

        match ($sort) {
            'oldest'    => $query->orderBy('id', 'asc'),
            'downloads' => $query->orderBy('downloads', 'desc'),
            'size'      => $query->orderBy('size', 'desc'),
            default     => $query->orderBy('id', 'desc'),
        };

        $shares = $query->limit(200)->get();

        $activeSharesQuery = $user->shares()->active();
        $stats = [
            'files'     => (clone $activeSharesQuery)->count(),
            'downloads' => (int) (clone $activeSharesQuery)->sum('downloads'),
            'size'      => (int) (clone $activeSharesQuery)->sum('size'),
        ];

        $opsCount = $user->operations()->count();
        $history = $user->operations()->latest('id')->limit(10)->get();

        return view('dashboard', compact('user', 'shares', 'stats', 'opsCount', 'history', 'categories', 'q', 'category', 'status', 'sort'));
    }

    public function destroyShare(Request $request, int $id): RedirectResponse
    {
        $share = Share::where('id', $id)->where('user_id', Auth::id())->first();
        if ($share) {
            @unlink(storage_path('app/uploads/' . $share->stored_name));
            $share->delete();
            return back()->with('success', 'تم حذف الملف ورابطه.');
        }
        return back()->with('danger', 'الملف غير موجود.');
    }

    public function clearHistory(): RedirectResponse
    {
        Auth::user()->operations()->delete();
        return back()->with('success', 'تم مسح سجل العمليات.');
    }
}

