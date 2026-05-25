<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tantangan;
use App\Models\Registrasi;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class TantanganController extends Controller
{
    public function index()
    {
        $kategori = Tantangan::all()->groupBy('kategori');
        return view('tantangan.index', compact('kategori'));
    }

    public function show($slug)
    {
        $event = Tantangan::where('slug', $slug)->firstOrFail();
        $statusDaftar = null;
        if (Auth::check()) {
            $statusDaftar = Registrasi::where('user_id', Auth::id())
                            ->where('tantangan_id', $event->id)
                            ->first();
        }

        return view('tantangan.detail', compact('event', 'statusDaftar'));
    }

    public function register($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::user(); 
        
        $event = Tantangan::findOrFail($id);

        $exists = Registrasi::where('user_id', $user->id)
                            ->where('tantangan_id', $id)
                            ->exists();

        if ($exists) {
            return back()->with('error', 'Kamu sudah terdaftar di tantangan ini!');
        }

        Registrasi::create([
            'user_id' => $user->id,
            'tantangan_id' => $id,
            'status' => 'aktif',
            'sudah_klaim_daftar' => true
        ]);

        $user->increment('coins', 10);

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Tantangan Selesai!',
            'message' => 'Kamu telah mencapai target ' . $event->judul . '. Klik di sini untuk klaim koin!',
            'url' => route('tantangan.show', $event->slug),
        ]);

        return redirect()->route('tantangan.index')->with('success', 'Berhasil mendaftar tantangan! +10 koin telah ditambahkan.');
    }

    public function klaimKoinSisa($id)
    {
        $user = Auth::user();
        
        $pendaftaran = Registrasi::with('tantangan')
                                ->where('user_id', $user->id)
                                ->where('tantangan_id', $id)
                                ->first();

        if (!$pendaftaran) {
            return back()->with('error', 'Kamu belum daftar tantangan ini.');
        }

        if ($pendaftaran->sudah_mengikuti_tantangan) {
            return back()->with('error', 'Kamu sudah ambil hadiah untuk tantangan ini.');
        }

        // $jarakLari = $this->getJarakDariAPI($user); 

        // if ($jarakLari >= $pendaftaran->tantangan->jarak) {
        //     $totalHadiah = $pendaftaran->tantangan->koin; 
        //     $user->increment('coins', $totalHadiah);
        //     $pendaftaran->update(['sudah_mengikuti_tantangan' => true]);
        //     return back()->with('success', 'Hebat! Kamu menyelesaikan ' . $pendaftaran->tantangan->judul . ' dan dapat ' . $totalHadiah . ' koin!');
        // }

        // return back()->with('error', 'Aktivitas kamu belum mencapai target ' . $pendaftaran->tantangan->jarak . '. Ayo semangat!');

        // Untuk TESTING:
        $jarakLari = 100;
        $targetJarak = (float) $pendaftaran->tantangan->jarak; 

        if ($jarakLari >= $targetJarak) {
            $totalHadiah = $pendaftaran->tantangan->koin; 

            $user->increment('coins', $totalHadiah);
            $pendaftaran->update(['sudah_mengikuti_tantangan' => true]);
            
            return redirect()->route('tantangan.index')->with('success', 'Selamat kamu sudah menyelesaikan tantangannya! Kamu mendapat ' . $totalHadiah . ' koin sebagai hadiahnya!');
        }

        return redirect()->route('tantangan.index')->with('error', 'Aktivitas kamu belum mencapai target ' . $pendaftaran->tantangan->jarak . '. Ayo semangat!');

    }
}
