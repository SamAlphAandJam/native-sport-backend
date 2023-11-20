<?php
namespace App\Http\Helpers;

use App\Models\Advert;
use App\Models\ChampionsLeague;
use App\Models\FootballNews;
use App\Models\GeneralNews;
use App\Models\Headlines;
use App\Models\TopStories;
use App\Models\TransferNews;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

use DOTNET;
use Termwind\Components\Dd;



    class Helper
        {
            /**
             * Update popular trips
             *
             * @return void
             */

             public static function runClearAdverts(){
                // Get all adverts from the database
                $adverts = Advert::all();

                // Loop through each advert and check if it's expired
                foreach ($adverts as $advert) {
                    $duration = $advert->duration; // Get the number of days from the duration column
                    $createdDate = Carbon::parse($advert->created_at); // Parse the created_at date using Carbon

                    // Check if the ad is expired
                    if ($createdDate->addDays($duration)->isPast()) {
                        // If the ad is expired, delete it from the database
                        $advert->delete();
                    }
                }
             }


             public static function populateHeadlines(){
                $url = 'https://content.api.pressassociation.io/v1/item?sort=issued:desc&sort=uri:asc&limit=30&offset=0&fields=total,limit,offset,item(uri,headline,subject,associations,description_text,subject,body_text,byline,firstcreated,versioncreated,issued,body_html,description_html,language,ranking)';
                $headers = [
                    'Accept' => 'application/json',
                    'apikey' => env('NEWS_API_KEY')
                    ];

                    $newsApi = new Client();
                    $raw_response = $newsApi->get($url, ['headers' => $headers]);
                    $request = $raw_response->getBody()->getContents();
                    $request = json_decode($request, true);
                    $data = $request['item'];
                    $result = [];

                foreach ($data as $obj) {
                    $uri = $obj['uri'];
                    $firstcreated = $obj['firstcreated'];
                    $versioncreated = $obj['versioncreated'];
                    $issued = $obj['issued'];
                    $ranking = isset($obj['ranking']) ? $obj['ranking'] : null;
                    $body_text = $obj['body_text'];
                    $body_html = $obj['body_html'];
                    $dates = [
                        'date_created' => $firstcreated,
                        'versioncreated' => $versioncreated,
                        'issued' => $issued,
                    ];
                    $description_text = $obj['description_text'];
                    $description_html = $obj['description_html'];
                    $byline = $obj['byline'];
                    $headline = $obj['headline'];
                    $subject = $obj['subject'];
                    $language = $obj['language'];
                    $picture = $obj['associations']['featureimage']['renditions']['1x1']['href'];

                    $existingHeadline = Headlines::where('uri', $uri)->first();

                    if (!$existingHeadline) {
                        $result[] = Headlines::create([
                            'uri' => $uri,
                            'ranking' => $ranking,
                            'dates' => $dates,
                            'byline' => $byline,
                            'headline' => $headline,
                            'description_text' => $description_text,
                            'description_html' => $description_html,
                            'body_text' => $body_text,
                            'body_html' => $body_html,
                            'subject' => $subject,
                            'language' => $language,
                            'picture' => $picture,
                        ]);
                    }

                    //save in General News Table
                    $existingGeneralNews = GeneralNews::where('uri', $uri)->first();

                    if (!$existingGeneralNews) {
                        GeneralNews::create([
                            'uri' => $uri,
                            'ranking' => $ranking,
                            'dates' => $dates,
                            'byline' => $byline,
                            'headline' => $headline,
                            'description_text' => $description_text,
                            'description_html' => $description_html,
                            'body_text' => $body_text,
                            'body_html' => $body_html,
                            'subject' => $subject,
                            'language' => $language,
                            'picture' => $picture,
                        ]);
                    }

                }

                return $result;
            }


             public static function populateTransferNews(){
                $url = 'https://content.api.pressassociation.io/v1/service/paservice:sport:football/item?subject=tag:transfers&offset=0&limit=32';
                $headers = [
                    'Accept' => 'application/json',
                    'apikey' => env('NEWS_API_KEY')
                    ];

                $newsApi = new Client();
                $raw_response = $newsApi->get($url, ['headers' => $headers]);
                $request = $raw_response->getBody()->getContents();
                $request = json_decode($request, true);
                $data = $request['item'];

                $result = []; // create an empty array to store the results

                foreach ($data as $obj) {
                    $uri = $obj['uri'];
                    $firstcreated = $obj['firstcreated'];
                    $versioncreated = $obj['versioncreated'];
                    $issued = $obj['issued'];
                    $ranking = isset($obj['ranking']) ? $obj['ranking'] : null;
                    $body_text = $obj['body_text'];
                    $body_html = $obj['body_html'];
                    $dates = [
                        'date_created' => $firstcreated,
                        'versioncreated' => $versioncreated,
                        'issued' => $issued,
                    ];
                    $description_text = $obj['description_text'];
                    $description_html = $obj['description_html'];
                    $byline = $obj['byline'];
                    $headline = $obj['headline'];
                    $subject = $obj['subject'];
                    $language = $obj['language'];
                    $picture = $obj['associations']['featureimage']['renditions']['1x1']['href'];

                    // add the current element to the result array
                    $existingHeadline = TransferNews::where('uri', $uri)->first();

                    if (!$existingHeadline) {
                        $result[] = TransferNews::create([
                            'uri' => $uri,
                            'ranking' => $ranking,
                            'dates' => $dates,
                            'byline' => $byline,
                            'headline' => $headline,
                            'description_text' => $description_text,
                            'description_html' => $description_html,
                            'body_text' => $body_text,
                            'body_html' => $body_html,
                            'subject' => $subject,
                            'language' => $language,
                            'picture' => $picture,
                        ]);
                    }

                    //save in General News Table
                    $existingGeneralNews = GeneralNews::where('uri', $uri)->first();

                    if (!$existingGeneralNews) {
                        GeneralNews::create([
                            'uri' => $uri,
                            'ranking' => $ranking,
                            'dates' => $dates,
                            'byline' => $byline,
                            'headline' => $headline,
                            'description_text' => $description_text,
                            'description_html' => $description_html,
                            'body_text' => $body_text,
                            'body_html' => $body_html,
                            'subject' => $subject,
                            'language' => $language,
                            'picture' => $picture,
                        ]);
                    }

                }

                return $result;
             }


             public static function populateChampionsLeague(){
                $url = 'https://content.api.pressassociation.io/v1/item?subject=tag:champions-league&offset=0';
                $headers = [
                    'Accept' => 'application/json',
                    'apikey' => env('NEWS_API_KEY')
                    ];

                $newsApi = new Client();
                $raw_response = $newsApi->get($url, ['headers' => $headers]);
                $request = $raw_response->getBody()->getContents();
                $request = json_decode($request, true);
                $data = $request['item'];
                $result = [];

                foreach ($data as $obj) {
                    $uri = $obj['uri'];
                    $firstcreated = $obj['firstcreated'];
                    $versioncreated = $obj['versioncreated'];
                    $issued = $obj['issued'];
                    $ranking = isset($obj['ranking']) ? $obj['ranking'] : null;
                    $body_text = $obj['body_text'];
                    $body_html = $obj['body_html'];
                    $dates = [
                        'date_created' => $firstcreated,
                        'versioncreated' => $versioncreated,
                        'issued' => $issued,
                    ];
                    $description_text = $obj['description_text'];
                    $description_html = $obj['description_html'];
                    $byline = $obj['byline'];
                    $headline = $obj['headline'];
                    $subject = $obj['subject'];
                    $language = $obj['language'];
                    $picture = $obj['associations']['featureimage']['renditions']['1x1']['href'];

                    $existingHeadline = ChampionsLeague::where('uri', $uri)->first();

                    if (!$existingHeadline) {
                        $result[] = ChampionsLeague::create([
                            'uri' => $uri,
                            'ranking' => $ranking,
                            'dates' => $dates,
                            'byline' => $byline,
                            'headline' => $headline,
                            'description_text' => $description_text,
                            'description_html' => $description_html,
                            'body_text' => $body_text,
                            'body_html' => $body_html,
                            'subject' => $subject,
                            'language' => $language,
                            'picture' => $picture,
                        ]);
                    }

                    //save in General News Table
                    $existingGeneralNews = GeneralNews::where('uri', $uri)->first();

                    if (!$existingGeneralNews) {
                        GeneralNews::create([
                            'uri' => $uri,
                            'ranking' => $ranking,
                            'dates' => $dates,
                            'byline' => $byline,
                            'headline' => $headline,
                            'description_text' => $description_text,
                            'description_html' => $description_html,
                            'body_text' => $body_text,
                            'body_html' => $body_html,
                            'subject' => $subject,
                            'language' => $language,
                            'picture' => $picture,
                        ]);
                    }

                }

                return $result;
            }


            public static function populateFootballNews(){
                $url = 'https://content.api.pressassociation.io/v1/service/paservice:sport:football/item';
                $headers = [
                    'Accept' => 'application/json',
                    'apikey' => env('NEWS_API_KEY')
                    ];

                $newsApi = new Client();
                $raw_response = $newsApi->get($url, ['headers' => $headers]);
                $request = $raw_response->getBody()->getContents();
                $request = json_decode($request, true);
                $data = $request['item'];
                $result = [];

                foreach ($data as $obj) {
                    $uri = $obj['uri'];
                    $firstcreated = $obj['firstcreated'];
                    $versioncreated = $obj['versioncreated'];
                    $issued = $obj['issued'];
                    $ranking = isset($obj['ranking']) ? $obj['ranking'] : null;
                    $body_text = $obj['body_text'];
                    $body_html = $obj['body_html'];
                    $dates = [
                        'date_created' => $firstcreated,
                        'versioncreated' => $versioncreated,
                        'issued' => $issued,
                    ];
                    $description_text = $obj['description_text'];
                    $description_html = $obj['description_html'];
                    $byline = $obj['byline'];
                    $headline = $obj['headline'];
                    $subject = $obj['subject'];
                    $language = $obj['language'];
                    $picture = $obj['associations']['featureimage']['renditions']['1x1']['href'];

                    $existingHeadline = FootballNews::where('uri', $uri)->first();

                    if (!$existingHeadline) {
                        $result[] = FootballNews::create([
                            'uri' => $uri,
                            'ranking' => $ranking,
                            'dates' => $dates,
                            'byline' => $byline,
                            'headline' => $headline,
                            'description_text' => $description_text,
                            'description_html' => $description_html,
                            'body_text' => $body_text,
                            'body_html' => $body_html,
                            'subject' => $subject,
                            'language' => $language,
                            'picture' => $picture,
                        ]);
                    }

                    //save in General News Table
                    $existingGeneralNews = GeneralNews::where('uri', $uri)->first();

                    if (!$existingGeneralNews) {
                        GeneralNews::create([
                            'uri' => $uri,
                            'ranking' => $ranking,
                            'dates' => $dates,
                            'byline' => $byline,
                            'headline' => $headline,
                            'description_text' => $description_text,
                            'description_html' => $description_html,
                            'body_text' => $body_text,
                            'body_html' => $body_html,
                            'subject' => $subject,
                            'language' => $language,
                            'picture' => $picture,
                        ]);
                    }

                }

                return $result;
            }


            public static function populateTopStories(){
                $url = 'https://content.api.pressassociation.io/v1/item?sort=ranking:asc&sort=firstcreated:desc&limit=29&start=now-24h';
                $headers = [
                    'Accept' => 'application/json',
                    'apikey' => env('NEWS_API_KEY')
                    ];

                $newsApi = new Client();
                $raw_response = $newsApi->get($url, ['headers' => $headers]);
                $request = $raw_response->getBody()->getContents();
                $request = json_decode($request, true);
                $data = $request['item'];
                $result = [];

                foreach ($data as $obj) {
                    $uri = $obj['uri'];
                    $firstcreated = $obj['firstcreated'];
                    $versioncreated = $obj['versioncreated'];
                    $issued = $obj['issued'];
                    $ranking = isset($obj['ranking']) ? $obj['ranking'] : null;
                    $body_text = $obj['body_text'];
                    $body_html = $obj['body_html'];
                    $dates = [
                        'date_created' => $firstcreated,
                        'versioncreated' => $versioncreated,
                        'issued' => $issued,
                    ];
                    $description_text = $obj['description_text'];
                    $description_html = $obj['description_html'];
                    $byline = $obj['byline'];
                    $headline = $obj['headline'];
                    $subject = $obj['subject'];
                    $language = $obj['language'];
                    $picture = $obj['associations']['featureimage']['renditions']['1x1']['href'];

                    $existingHeadline = TopStories::where('uri', $uri)->first();

                    if (!$existingHeadline) {
                        $result[] = TopStories::create([
                            'uri' => $uri,
                            'ranking' => $ranking,
                            'dates' => $dates,
                            'byline' => $byline,
                            'headline' => $headline,
                            'description_text' => $description_text,
                            'description_html' => $description_html,
                            'body_text' => $body_text,
                            'body_html' => $body_html,
                            'subject' => $subject,
                            'language' => $language,
                            'picture' => $picture,
                        ]);
                    }

                    //save in General News Table
                    $existingGeneralNews = GeneralNews::where('uri', $uri)->first();

                    if (!$existingGeneralNews) {
                        GeneralNews::create([
                            'uri' => $uri,
                            'ranking' => $ranking,
                            'dates' => $dates,
                            'byline' => $byline,
                            'headline' => $headline,
                            'description_text' => $description_text,
                            'description_html' => $description_html,
                            'body_text' => $body_text,
                            'body_html' => $body_html,
                            'subject' => $subject,
                            'language' => $language,
                            'picture' => $picture,
                        ]);
                    }
                }

                return $result;
            }


            public static function clearOldNews(){

                $date = Carbon::now()->subDays(2);

                // Find all headlines created before three days ago
                $oldHeadlines = Headlines::where('created_at', '<', $date)->get();
                $oldTransferNews = TransferNews::where('created_at', '<', $date)->get();
                $oldChampionsLeague = ChampionsLeague::where('created_at', '<', $date)->get();
                $oldTopStories = TopStories::where('created_at', '<', $date)->get();
                $oldFootballNews = FootballNews::where('created_at', '<', $date)->get();

                // Loop through the old headlines and delete them
                foreach ($oldHeadlines as $headline) {
                    $headline->delete();
                }
                foreach ($oldFootballNews as $footballNews) {
                    $footballNews->delete();
                }
                foreach ($oldTopStories as $topStories) {
                    $topStories->delete();
                }
                foreach ($oldChampionsLeague as $championsLeague) {
                    $championsLeague->delete();
                }
                foreach ($oldTransferNews as $transferNews) {
                    $transferNews->delete();
                }
            }


            public static function clearGeneralNews(){
                // Get the current time, minus 13 months
                $thirteenMonthsAgo = Carbon::now()->subMonths(13);

                // Find all GeneralNews created before 13 months ago
                $oldGeneralNews = GeneralNews::where('created_at', '<', $thirteenMonthsAgo)->get();

                // Loop through the old headlines and delete them
                foreach ($oldGeneralNews as $generalNews) {
                    $generalNews->delete();
                }
            }

        }
