<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BannerHomepage;
use Illuminate\Http\Response;
use Exception;

class BannerHomepageController extends Controller
{
    public function index()
    {
        try {
            $banners = BannerHomepage::all();
            return response()->json($banners, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not fetch banners',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $banner = BannerHomepage::findOrFail($id);
            return response()->json($banner, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not fetch the banner',
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'stt' => 'required|integer',
                'url' => 'required|string|max:255',
                'is_active' => 'nullable|boolean'
            ]);

            $banner = BannerHomepage::create($validatedData);
            return response()->json($banner, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not create the banner',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'stt' => 'required|integer',
                'url' => 'required|string|max:255',
                'is_active' => 'required|boolean'
            ]);

            $banner = BannerHomepage::findOrFail($id);
            $banner->update($validatedData);
            return response()->json($banner, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not update the banner',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($id)
    {
        try {
            $banner = BannerHomepage::findOrFail($id);
            $banner->delete();
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not delete the banner',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

}
