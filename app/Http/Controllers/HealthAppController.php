<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class HealthAppController extends Controller
{
    public function connectGoogleFit() {
        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/fitness.activity.read',
                'https://www.googleapis.com/auth/fitness.location.read'
            ])
            ->with(['access_type' => 'offline', 'prompt' => 'consent']) 
            ->redirect();
    }

    public function handleGoogleFitCallback() {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = Auth::user();
            $user->update([
                'aplikasi_sehat' => 'google_fit',
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken,
            ]);

            return redirect()->route('lengkapi-profil')->with('success', 'Google Fit berhasil terhubung!');
        } catch (\Exception $e) {
            return redirect()->route('lengkapi-profil')->with('error', 'Gagal menghubungkan Google Fit: ' . $e->getMessage());
        }
    }

    public function aktivitas()
    {
        $user = Auth::user(); 

        $caloriesToday = 450;
        $activeMinutesToday = 30;
        $weeklySteps = [8000, 9500, 10000, 8000];

        return view('aktivitas', compact(
            'user', 
            'caloriesToday', 
            'activeMinutesToday', 
            'weeklySteps'
        ));
    }

    private function getGoogleFitData($user)
    {
        $client = new \Google_Client();
        $client->setAccessToken($user->google_token);

        if ($client->isAccessTokenExpired()) {
            if ($user->google_refresh_token) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                $user->update(['google_token' => $newToken['access_token']]);
            }
        }

        $fitness = new \Google_Service_Fitness($client);
        
        $startTime = Carbon::now()->startOfDay()->getTimestampMs();
        $endTime = Carbon::now()->getTimestampMs();

        $steps = $this->fetchFitnessData($fitness, 'derived:com.google.step_count.delta:com.google.android.gms:estimated_steps', $startTime, $endTime);

        $calories = $this->fetchFitnessData($fitness, 'derived:com.google.calories.expended:com.google.android.gms:merge_calories_expended', $startTime, $endTime);

        $activeMinutes = $this->fetchFitnessData($fitness, 'derived:com.google.active_minutes:com.google.android.gms:merge_active_minutes', $startTime, $endTime);

        return [
            'stepsToday' => $steps,
            'caloriesToday' => round($calories),
            'activeMinutesToday' => round($activeMinutes),
        ];
    }

    private function fetchFitnessData($fitness, $sourceId, $startTime, $endTime)
    {
        $request = new \Google_Service_Fitness_AggregateRequest();
        $request->setAggregateBy([['dataSourceId' => $sourceId]]);
        $request->setBucketByTime(['durationMillis' => 86400000]);
        $request->setStartTimeMillis($startTime);
        $request->setEndTimeMillis($endTime);

        $stats = $fitness->users_dataset->aggregate("me", $request);
        
        $total = 0;
        foreach ($stats->getBucket() as $bucket) {
            foreach ($bucket->getDataset() as $dataset) {
                foreach ($dataset->getPoint() as $point) {
                    foreach ($point->getValue() as $value) {
                        $total += $value->getFpVal() ?? $value->getIntVal();
                    }
                }
            }
        }
        return $total;
    }
}
