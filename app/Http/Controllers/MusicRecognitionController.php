<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MusicRecognitionController extends Controller
{
    private $apiKey;
    private $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('API_KEY'); 
        $this->apiUrl = env('API_URL');
    }

    public function index()
    {
        return view('music.index');
    }

    public function recognize(Request $request)
    {
        $request->validate([
            'audio_file' => 'required|file|mimes:mp3,wav,m4a|max:10240',
        ]);

        $file = $request->file('audio_file');
        $path = $file->store('temp');

        $response = Http::attach(
            'file', 
            file_get_contents(Storage::path($path)), 
            $file->getClientOriginalName()
        )->post($this->apiUrl, [
            'api_token' => $this->apiKey,
            'return' => 'apple_music,spotify'
        ]);

        Storage::delete($path);

        if ($response->successful() && $response['status'] === 'success' && $response['result']) {
            $result = $response['result'];
            return response()->json([
                'success' => true,
                'data' => [
                    'title' => $result['title'],
                    'artist' => $result['artist'],
                    'spotify_url' => $result['spotify']['external_urls']['spotify'] ?? null,
                    'apple_music_url' => $result['apple_music']['url'] ?? null,
                    'album_art' => $result['spotify']['album']['images'][0]['url'] ?? null,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Song not found or error occurred'
        ]);
    }
}