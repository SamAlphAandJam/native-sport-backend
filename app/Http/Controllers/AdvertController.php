<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Models\Advert;
use Carbon\Carbon;
use Illuminate\Validation\Validator;



class AdvertController extends Controller
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

    //

    public function index(Request $request)
    {
        $id = $request->id;
        if($id) {
            $ads = Advert::where('id', $id)->get();
            if ($ads->isEmpty()) {
                return response()->json([
                    'message' => 'not found',
                    'data' => null
                ], 404);
            }
        } else {
            $ads = Advert::all();
        }
        return response()->json([
            'message' => 'success',
            'data' => $ads
        ], 200);
    }


    public function filter(Request $request)
    {
        $page = strtolower($request->page);
        $country = strtolower($request->country);
        $platform = strtolower($request->platform);
        $section = strtolower($request->section);

        $ads = Advert::where('platform', $platform)->where('country', $country)->where('page', $page)->where('section', $section)->get();

        if ($ads->isEmpty()) {
            return response()->json([
                'message' => 'not found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'message' => 'success',
            'data' => $ads
        ], 200);
    }

    public function filterPage(Request $request)
    {
        $page = strtolower($request->page);
        $country = strtolower($request->country);
        $platform = strtolower($request->platform);

        $ads = Advert::where('platform', $platform)->where('country', $country)->where('page', $page)->get();

        if ($ads->isEmpty()) {
            return response()->json([
                'message' => 'not found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'message' => 'success',
            'data' => $ads
        ], 200);
    }


    public function filterPlatform(Request $request)
    {
        $country = strtolower($request->country);
        $platform = strtolower($request->platform);

        $adsQuery = Advert::where('platform', $platform);

        if ($country !== 'all') {
            $adsQuery->where('country', $country);
        }

        $ads = $adsQuery->get();

        if ($ads->isEmpty()) {
            return response()->json([
                'message' => 'not found',
                'data' => null
            ], 404);
        }


        return response()->json([
            'message' => 'success',
            'data' => $ads
        ], 200);
    }


    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'nullable',
            'page' => 'required',
            // 'media' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'duration' => 'required',
            'width' => 'required',
            'height' => 'required',
            'country' => 'required',
            'platform' => 'required',
            'section' => 'required',
            'media' => 'required',
            'mediaType' => 'required',
            'redirect_url' => 'required',
        ]);
        $media_url = '';

        // upload image to cloudinary
        // if($request->mediaType == 'picture'){
        //     $image = $request->file('media');
        //     $image_url = $image->getRealPath();
        //     $cloudinary = Cloudinary::uploadApi()->upload($image_url, [
        //         'transformation' => [
        //             [
        //                 'width' => $request->width,
        //                 'height' => $request->height,
        //                 'crop' => 'fit'
        //             ]
        //         ]
        //     ]);
        //     $returned = [...$cloudinary];
        //     $media_url = $returned['secure_url'];

        // }
        if($request->mediaType == 'picture'){
            $image = $request->media;
            $cloudinary = Cloudinary::uploadApi()->upload($image, [
                'transformation' => [
                    [
                        'width' => $request->width,
                        'height' => $request->height,
                        'crop' => 'fit'
                    ]
                ]
            ]);
            $returned = [...$cloudinary];
            $media_url = $returned['secure_url'];
        }
        elseif($request->mediaType == 'gif'){
            $gif = $request->media;
            $cloudinary = Cloudinary::uploadApi()->upload($gif, [
                "resource_type" => "auto",
                "format" => "gif",
                "transformation" => [
                    "width" => $request->width,
                    "height" => $request->height,
                    "crop" => "scale"
                ]
            ]);
            $returned = [...$cloudinary];
            $media_url = $returned['secure_url'];
        }elseif($request->mediaType == 'video'){
            $video = $request->file('media');
            $video_url = $video->getRealPath();
            $cloudinary = Cloudinary::uploadApi()->upload($video_url, [
                "resource_type" => "video"
            ]);
            $returned = [...$cloudinary];
            $media_url = $returned['secure_url'];
        }

        // save ad to database
        $ad = Advert::create([
            // 'user_id' => auth()->user()->id,
            'name' => $request->name,
            'media_url' => $media_url,
            'mediaType' => $request->mediaType,
            'duration' => $request->duration,
            'width' => $request->width,
            'height' => $request->height,
            'page' => strtolower($request->page),
            'country' => strtolower($request->country),
            'platform' => strtolower($request->platform),
            'section' => strtolower($request->section),
            'redirect_url' => $request->redirect_url,
        ]);
        return response()->json([
            'message' => 'success',
            'data' => $ad,
        ], 201);
    }


    public function destroy(Request $request)
    {
        $id = $request->id;
        $ad = Advert::find($id);
        if(!$ad){
            return response()->json([
                'message' => 'not found',
            ], 404);
        }

        $ad->delete();
        return response()->json([
            'message' => 'success',
            'data' => $ad,
        ], 200);
    }



    // public function show($id)
    // {
    //     $ad = Advert::find($id);
    //     return response()->json([
    //         'message' => 'success',
    //         'data' => $ad,
    //     ], 200);
    // }

    public function update(Request $request, $id)
    {
        $ad = Advert::find($id);
        if(!$ad){
            return response()->json([
                'message' => 'not found',
            ], 404);
        }
        $validatedData = $this->validate($request, [
            'name' => 'nullable',
            'page' => 'nullable',
            'duration' => 'nullable',
            'width' => 'nullable',
            'height' => 'nullable',
            'country' => 'nullable',
            'platform' => 'nullable',
            'section' => 'nullable',
            'media' => 'nullable',
            'mediaType' => 'nullable',
            'redirect_url' => 'nullable',
        ]);

        // Upload new media to Cloudinary if it exists
        if($request->media){
            $media_url = '';
            // upload image to cloudinary
            if($request->mediaType == 'picture'){
                $image = $request->media;
                $cloudinary = Cloudinary::uploadApi()->upload($image, [
                    'transformation' => [
                        [
                            'width' => $request->width,
                            'height' => $request->height,
                            'crop' => 'fit'
                        ]
                    ]
                ]);
                $returned = [...$cloudinary];
                $media_url = $returned['secure_url'];
            }elseif($request->mediaType == 'gif'){
                $gif = $request->media;
                $cloudinary = Cloudinary::uploadApi()->upload($gif, [
                    "resource_type" => "auto",
                    "format" => "gif"
                ]);
                $returned = [...$cloudinary];
                $media_url = $returned['secure_url'];

            }elseif($request->mediaType == 'video'){
                $video = $request->file('media');
                $video_url = $video->getRealPath();
                $cloudinary = Cloudinary::uploadApi()->upload($video_url, [
                    "resource_type" => "video"
                ]);
                $returned = [...$cloudinary];
                $media_url = $returned['secure_url'];
            }
            if($media_url != ''){
                $ad->media_url = $media_url;
            }
        }

        // Update the ad with the new data
        // dd($validatedData);
        $ad->fill($validatedData);
        $ad->save();

        // Return the updated ad
        return response()->json([
            'message' => 'success',
            'data' => $ad,
        ], 200);
    }


}
