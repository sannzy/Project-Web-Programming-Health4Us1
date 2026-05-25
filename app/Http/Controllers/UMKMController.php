<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Umkm;
use Illuminate\Support\Facades\Auth;

class UMKMController extends Controller
{
    public function index()
    {
        $allUmkm = Umkm::all();
        $kategori = $allUmkm->groupBy('kategori');
        $healthkoin = Auth::check() ? Auth::user()->coins : 0;

        return view('umkm.index', compact('kategori', 'healthkoin'));
    }

    public function show($slug)
    {
        $product = Umkm::where('slug', $slug)->firstOrFail();
        $healthkoin = Auth::check() ? Auth::user()->coins : 0;

        return view('umkm.show', compact('product', 'healthkoin'));
    }

    public function tukar($slug)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Login dulu yuk!');
        }

        $product = Umkm::where('slug', $slug)->firstOrFail();

        if ($user->coins < $product->healthkoin) {
            return redirect()->route('umkm.index')->with('error', 'Coins kamu tidak cukup untuk ' . $product->nama);
        }

        $user->coins -= $product->healthkoin;
        $user->save();

        return redirect()->route('umkm.index')->with('success', 'Berhasil menukar ' . $product->nama);
    }
}
