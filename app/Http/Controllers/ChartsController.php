<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Song;

class ChartsController extends Controller
{
    function processCharts() {
        /* Fetch HTML */

        $chartsUrl = 'http://www.officialcharts.com/charts/singles-chart/';

        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $chartsUrl);
        $html = $res->getBody();

        /* Extract songs */

        $pattern = '/' . str_replace('WS', '\s*', str_replace('PL', '.*', str_replace('MA', '(.*)', preg_quote('<div class="title">WS<a href="PL">MA</a>WS</div>', '/')))) . '/';
        // dd($pattern);
        preg_match_all($pattern, $html, $titles);
        $titles = array_map('strtolower', $titles[1]);

        $pattern = '/' . str_replace('WS', '\s*', str_replace('PL', '.*', str_replace('MA', '(.*)', preg_quote('<div class="artist">WS<a href="PL">MA</a>WS</div>', '/')))) . '/';
        // dd($pattern);
        preg_match_all($pattern, $html, $artists);
        $artists = array_map('strtolower', $artists[1]);

        foreach ($artists as $index => $artist) {
            $title = $titles[$index];

            echo $artist . ' - ' . $title . '<br />';

            /* Add title to database if necessary */

            $song = Song::where('artist', $artist)->where('name', $title)->get();
            if ($song->isEmpty()) {
                $song = Song::create(['artist' => $artist, 'name' => $title, 'youtube_id' => '', 'converted' => false]);
            }
        }
    }

    function fetchYoutubeIds() {
        /* Initialize */

        $youtubeApiKey = env('YOUTUBE_API_KEY');
        $youtubeUrl = 'https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=1&q=[NAME]&type=video&key=' . $youtubeApiKey;

        /* Fetch unprocessed songs */

        $songs = Song::where('youtube_id', '')->get();
        $songs->each(function($song, $index) use ($youtubeUrl) {
            /* Build Youtube URL */

            echo $song->artist . ' - ' . $song->name . '<br />';

            $url = str_replace('[NAME]', urlencode($song->artist . ' ' . $song->name), $youtubeUrl);

            /* Fetch Youtube ID */

            $client = new \GuzzleHttp\Client(['verify' => false, ]);
            $response = $client->request('GET', $url);
            $json = json_decode($response->getBody());

            if (isset($json->items[0]->id->videoId)) {
                $youtubeId = $json->items[0]->id->videoId;

                $song->youtube_id = $youtubeId;
                $song->save();
                echo '- id: ' . $youtubeId . '<br />';
            } else {
                echo '- no id<br />';
            }
        });
    }

    function convertSongs() {
        /* Initialize */

        $convertInfoUrl = 'http://www.youtubeinmp3.com/fetch/?format=JSON&video=https://www.youtube.com/watch?v=[ID]';

        /* Fetch unprocessed songs */

        $songs = Song::where('youtube_id', '!=', '')->where('converted', false)->get();
        $songs->each(function($song, $index) use ($convertInfoUrl) {
            /* Fetch convert URL */

            $url = str_replace('[ID]', $song->youtube_id, $convertInfoUrl);

            $client = new \GuzzleHttp\Client(['verify' => false, ]);
            $response = $client->request('GET', $url);
            $json = json_decode($response->getBody());

            dd($json);
        });
    }
}
