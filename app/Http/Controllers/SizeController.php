<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SizeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getSizesByProductTypeId($product_type_id)
    {
        try {
            $sizes = Size::where('product_type_id', $product_type_id)->get();
            return response()->json($sizes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching sizes'], 500);
        }
    }

    public function create(Request $request)
    {
         // Validate dữ liệu đầu vào
         $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'height' => 'required|string|max:255',
            'weight' => 'required|string|max:255',
            'product_type_id' => 'required|integer|exists:product_types,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        
        // Tạo category mới
        $size = Size::create([
            'name' => $request->name,
            'height' => $request->height, 
            'weight' => $request->weight, 
            'product_type_id' => $request->product_type_id, 
        ]);

        // Trả về JSON response
        return response()->json($size, 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Size $size)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Size $size)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Size $size)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Size $size)
    {
        //
    }
}
