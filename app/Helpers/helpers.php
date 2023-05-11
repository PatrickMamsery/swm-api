
<?php

use Illuminate\Support\Facades\Auth;
use App\Models\Log as CustomLog;
use Illuminate\Support\Facades\Log;

if (! function_exists('referenceNumber')) {
    function referenceNumber($l = 8) {
        return substr(md5(uniqid(mt_rand(), true)), 0, $l);
    }
}

if (! function_exists('addLog')) {
    function addLog(String $action,String $description,String $platform="dashboard") {

        try {
            return CustomLog::create([
                "action"=> $action,
                "description" => $description,
                "platform"=> $platform,
                "user"=> Auth::user()->id
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
        
        
    }
}

// generate random string for password reset
// Helper functions
if (! function_exists('generateRandomString')) {
    function generateRandomString($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}