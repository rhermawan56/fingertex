<?php

namespace App\Models;

// use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Employee extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    private static $JWT_TOKEN = '';

    public static function loginApi()
    {
        $url = "http://103.76.15.27/webhook_api/api/login";

        try {
            $response = Http::post(
                $url,
                [
                    'username' => env('API_USERNAME'),
                    'password' => env('API_PASSWORD')
                ]
            );

            $response = $response->json();
            self::$JWT_TOKEN = $response['token'];

            return response()->json([
                'status' => true,
                'messages' => 'Success Login'
            ], 200);

        } catch (\Exception $e) {
            
            return response()->json([
                'status' => false,
                'messages' => $e->getMessage()
            ], 400);
        }
    }

    public static function getDataEmployees(Request $request)
    {
        self::loginApi();
        $url = "http://103.76.15.27/webhook_api/api/get_employees";

        try {
            $where = [];
            $wherein = [];
            $raw = [];
            $start = [];
            $length = [];

            $dataSend = [
                'start' => $request['start'],
                'length' => $request['length'],
                'raw' => ["divisi = '6'"]
            ];
            $response = Http::withToken(self::$JWT_TOKEN)->post($url, $dataSend);
            $response = $response->json();

            return response()->json([
                'status' => true,
                'data' => $response['data'],
                'rows' => $response['rows'],
                'messages' => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => [],
                'rows' => 0,
                'messages' => $e->getMessage()
            ], 400);
        }
    }
}
