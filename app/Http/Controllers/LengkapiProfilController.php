<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LengkapiProfilController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('lengkapi-profil', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'nama' => 'required|min:4',
            'email' => 'required|unique:users,email,' . Auth::id(),
            'kata-sandi' => 'nullable|min:8',
            'nomor-telepon' => 'required|digits_between:10,13',
            'jenis-kelamin' => 'required|in:laki,perempuan',
            'tinggi-badan' => 'required|numeric|min:1',
            'berat-badan' => 'required|numeric|min:1',
            'tanggal-lahir' => 'required',
            'kota-kabupaten' => 'required',
            'aplikasi-kesehatan' => 'required|in:google_fit,strava',
            'e-wallet' => 'required|in:gopay,ovo,dana,shopeepay'
        ]);

        $user = Auth::user();

        $user->name = $request->nama;
        $user->email = $request->email;
        $user->no_telp = $request->input('nomor-telepon');
        $user->jenis_kelamin = $request->input('jenis-kelamin');
        $user->tinggi_badan = $request->input('tinggi-badan');
        $user->berat_badan = $request->input('berat-badan');
        $user->tanggal_lahir = $request->input('tanggal-lahir');
        $user->kota = $request->input('kota-kabupaten');
        $user->aplikasi_sehat = $request->input('aplikasi-kesehatan');
        $user->e_wallet = $request->input('e-wallet');

        if ($request->filled('kata-sandi')) {
            $user->password = Hash::make($request->input('kata-sandi'));
        }

        $user->save();

        return redirect()->route('beranda')->with('success', 'Profil kamu berhasil diperbarui!');
    }
}
