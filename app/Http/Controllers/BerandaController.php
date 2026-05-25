<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tantangan;
use App\Models\Umkm;
use Illuminate\Support\Facades\Auth;

class BerandaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only('dashboard');
    }

    public function home() 
    {
        $caloriesToday = 0;
        $activeMinutesToday = 0;

        if (Auth::check()) {
            $caloriesToday = 450;
            $activeMinutesToday = 30;
        }

        $challenges = Tantangan::latest()->take(6)->get();

        $allUmkm = Umkm::all();
        $groupedUmkm = $allUmkm->groupBy('kategori');

        $displayUmkm = [];
        foreach ($groupedUmkm as $key => $items) {
            if ($items->count() > 0) {
                $displayUmkm[] = $items->first();
            }
        }
        $displayUmkm = array_slice($displayUmkm, 0, 3);

        return view('beranda', compact(
            'challenges', 
            'displayUmkm', 
            'caloriesToday', 
            'activeMinutesToday'
        ));
    }

    public function index() 
    {
        $semuaTantangan = Tantangan::all();
        $kategoriTantangan = $semuaTantangan->groupBy('kategori');

        $kategoriUMKM = Umkm::all()->groupBy('kategori');

        return view('tantangan.index', [
            'kategori' => $kategoriTantangan,
            'kategoriUMKM' => $kategoriUMKM 
        ]);
    }
}
