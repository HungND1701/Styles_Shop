<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryHomepage;
use Illuminate\Http\Response;
use Exception;

class CategoryHomepageController extends Controller
{
    public function index()
    {
        try {
            $categories = CategoryHomepage::with('category')->orderBy('stt', 'asc')->get();

            // Tính toán số lượng sản phẩm cho từng category
            $categories->each(function ($categoryHomepage) {
                $categoryHomepage->category->each(function ($category) {
                    $category->product_count = $category->countProducts();
                });
            });

            // Chuyển đổi các categoryHomepage thành mảng để thêm thuộc tính tạm thời vào JSON
            $categoriesArray = $categories->toArray();

            return response()->json($categoriesArray, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not fetch categories',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getCategoriesWithProduct()
    {
        try {
            $categories = CategoryHomepage::with('category.products.tag', 'category.products.colorProduct.color', 'category.products.colorProduct.sizes.size', 'category.products.colorProduct.images')->orderBy('stt', 'asc')->get();

            // Chuyển đổi các categoryHomepage thành mảng để thêm thuộc tính tạm thời vào JSON
            $categoriesArray = $categories->toArray();

            return response()->json($categoriesArray, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not fetch categories',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $category = CategoryHomepage::findOrFail($id);
            return response()->json($category, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not fetch the category',
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'category_id' => 'required|integer',
                'stt' => 'required|integer'
            ]);

            $categoryHomepage = CategoryHomepage::create($validatedData);

            $categoryHomepage->load('category');
            $categoryHomepage->category->product_count =  $categoryHomepage->category->countProducts();
            return response()->json($categoryHomepage, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not create the category',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'category_id' => 'required|integer',
                'stt' => 'required|integer'
            ]);

            $category = CategoryHomepage::findOrFail($id);
            $category->update($validatedData);
            return response()->json($category, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not update the category',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy($id)
    {
        try {
            $category = CategoryHomepage::findOrFail($id);
            $category->delete();
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not delete the category',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
