<?php

namespace App\Http\Controllers;

use App\Models\ChampionsLeague;
use App\Models\FootballNews;
use App\Models\GeneralNews;
use App\Models\Headlines;
use App\Models\TopStories;
use App\Models\TransferNews;
use Carbon\Carbon;
use App\Services\GeneralService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }


    public function deleteNews(Request $request){
        $results = collect([]);
        $results = $results->merge(ChampionsLeague::where('uri', $request->uri)->get());
        $results = $results->merge(TransferNews::where('uri', $request->uri)->get());
        $results = $results->merge(TopStories::where('uri', $request->uri)->get());
        $results = $results->merge(FootballNews::where('uri', $request->uri)->get());
        $results = $results->merge(Headlines::where('uri', $request->uri)->get());
        $results = $results->merge(GeneralNews::where('uri', $request->uri)->get());

        // Delete records
        foreach ($results as $result) {
            $result->delete();
        }

        return response()->json([
            'message' => 'success',
        ], 200);
    }


    public function createNews(Request $request){
        $this->validate($request, [
            'ranking' => 'nullable',
            'headline' => 'required',
            // 'picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'picture' => 'required',
            'byline' => 'required',
            'description_text' => 'required',
            'description_html' => 'required',
            'body_text' => 'required',
            'body_html' => 'required',
            'language' => 'required',
            'type' => 'required',
            'subject' => 'required',
        ]);

        $uri = str_replace('-', '', Str::uuid()->toString());
        $today = Carbon::now();
        $dates = [
            'date_created' => $today,
            'versioncreated' => $today,
            'issued' => $today,
        ];

        if($request->picture){
            $image = $request->picture;
            $cloudinary = Cloudinary::uploadApi()->upload($image);
            $returned = [...$cloudinary];
            $picture = $returned['secure_url'];
            $request->merge(['picture' => $picture]);
        }

        switch ($request->type) {
            case 'champion-league':
                $data = new ChampionsLeague();
                break;
            case 'transfer-news':
                $data = new TransferNews();
                break;
            case 'top-stories':
                $data = new TopStories();
                break;
            default:
                return response()->json(['error' => 'Invalid type specified'], 400);
        }

        $data->uri = $uri;
        $data->ranking = $request->ranking;
        $data->dates = $dates;
        $data->byline = $request->byline;
        $data->headline = $request->headline;
        $data->description_text = $request->description_text;
        $data->description_html = $request->description_html;
        $data->body_text = $request->body_text;
        $data->body_html = $request->body_html;
        $data->subject = $request->subject;
        $data->language = $request->language;
        $data->picture = $picture;
        $data->save();

        $result = GeneralNews::create([
            'uri' => $uri,
            'ranking' => $request->ranking,
            'dates' => $dates,
            'byline' => $request->byline,
            'headline' => $request->headline,
            'description_text' => $request->description_text,
            'description_html' => $request->description_html,
            'body_text' => $request->body_text,
            'body_html' => $request->body_html,
            'subject' => $request->subject,
            'language' => $request->language,
            'picture' => $request->picture,
        ]);

        return response()->json([
            'message' => 'success',
            'data' => $result,
        ], 201);
    }


    public function getAllNews(){
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


    public function getTransferNews(){
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


    public function getChampionsLeague(){
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


    public function getFootballNews(){
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


    public function getTopStories(){
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


    // public function getTopStories(){
    //     $data = FootballNews::where('uri', '!=', null)->get();

    //     return response()->json([
    //         'message' => 'success',
    //         'data' => $data
    //     ], 200);
    // }


    public function getNews(Request $request){
        $type = $request->type;

        if($type == 'all-headlines'){
            $data = Headlines::all();
        }elseif($type == 'tranfer-news'){
            $data = TransferNews::all();
        }elseif($type == 'champions-leagues'){
            $data = ChampionsLeague::all();
        }elseif($type == 'top-stories'){
            $data = TopStories::all();
        }elseif($type == 'football-news'){
            $data = FootballNews::all();
        }elseif($type == 'all-news'){
            $data = GeneralNews::all();
        }else{
            return response()->json([
                'message' => 'failed: type not found',
            ], 404);
        }

        return response()->json([
            'message' => 'success',
            'type' => $type,
            'data' => $data
        ], 200);
    }


    public function getNewsByID(Request $request){
        $uri = $request->uri;

        $count = 0;

        $data = Headlines::where('uri', $uri)->first();
        if ($data) {
            $count++;
        } else {
            $data = TransferNews::where('uri', $uri)->first();
            if ($data) {
                $count++;
            } else {
                $data = ChampionsLeague::where('uri', $uri)->first();
                if ($data) {
                    $count++;
                } else{
                        $source = 'News API';
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

                            $data = [
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
                                ];
                        }
                        if ($data) {
                            $count++;
                        }
                }
            }
        }

        if ($count == 0) {
            $message = 'Not found';
        } else {
            $message = 'Success';
        }

        return response()->json([
            'message' => $message,
            'num_tables' => $count,
            'data' => $data
        ], 200);
    }

    public function filterNews(Request $request) {
        $tag = $request->tag;
        $tag = 'tag:'.$tag;
        // filter news by tag in subject using whereJsonContains
        $data = GeneralNews::WhereJsonContains('subject', [['code' => $tag]])->get();
        // $data = GeneralNews::where('subject', 'like', '%' . $tag . '%')->get();
        if($data->count() == 0){
            return response()->json([
                'message' => 'Not Found',
                'count' => $data->count(),
                'data' => $data
            ], 404);
        }
        return response()->json([
            'message' => 'success',
            'count' => $data->count(),
            'data' => $data
        ], 200);
    }


    public function thisDayLastYear(){


        $data = GeneralNews::whereDate('created_at', '=', Carbon::now()->subYear()->format('Y-m-d'))->get();
        //used to debug for today
        // $data = FootballNews::whereDate('created_at', '=', Carbon::today())->get();


        if($data->isEmpty()){
            $source = 'News API';
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

                $result[] = [
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
                    ];
            }
        }else{
            $source = 'Database';
            $result = $data;
        }

        return response()->json([
            'message' => 'success',
            'source' => $source,
            'data' => $result
        ], 200);

    }

    // public function updateNews(Request $request, $uri) {
    //     $this->validate($request, [
    //         'byline' => 'nullable',
    //         'headline' => 'nullable',
    //         'description_text' => 'nullable',
    //         'description_html' => 'nullable',
    //         'body_text' => 'nullable',
    //         'body_html' => 'nullable',
    //     ]);

    //     $models = [
    //         GeneralNews::class,
    //         Headlines::class,
    //         ChampionsLeague::class,
    //         TransferNews::class,
    //         TopStories::class,
    //         FootballNews::class
    //     ];

    //     foreach ($models as $model) {
    //         $news = $model::where('uri', $uri)->first();

    //         if ($news) {
    //             $news->fill($request->only([
    //                 'byline',
    //                 'headline',
    //                 'description_text',
    //                 'body_text',
    //                 'body_html',
    //                 'description_html'
    //             ]))->save();
    //         }
    //     }

    //     $updatedNews = GeneralNews::where('uri', $uri)->first();

    //     return response()->json([
    //         'message' => 'success',
    //         'data' => $updatedNews
    //     ], 200);
    // }
    public function updateNews(Request $request, $uri) {
        $this->validate($request, [
            'byline' => 'nullable',
            'headline' => 'nullable',
            'description_text' => 'nullable',
            'description_html' => 'nullable',
            'body_text' => 'nullable',
            'body_html' => 'nullable',
            'picture' => 'nullable',
        ]);

        $models = [
            GeneralNews::class,
            Headlines::class,
            ChampionsLeague::class,
            TransferNews::class,
            TopStories::class,
            FootballNews::class
        ];
// dd($request->picture);
        if($request->picture){
            $image = $request->picture;
            $cloudinary = Cloudinary::uploadApi()->upload($image);
            $returned = [...$cloudinary];
            $picture = $returned['secure_url'];
            $request->merge(['picture' => $picture]);
        }

        foreach ($models as $model) {
            $news = $model::where('uri', $uri)->first();

            if ($news) {
                $news->fill($request->only([
                    'byline',
                    'headline',
                    'description_text',
                    'body_text',
                    'body_html',
                    'description_html',
                    'picture',
                ]))->save();

            }
        }

        $updatedNews = GeneralNews::where('uri', $uri)->first();

        return response()->json([
            'message' => 'success',
            'data' => $updatedNews
        ], 200);
    }
}
