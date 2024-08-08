<?php

namespace App\Http\Controllers;

use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ReplyController extends Controller
{
    public function store(Request $request)
    {
        try {
            $requestValidated = $request->validate([
                'review_id' => 'required|exists:reviews,id',
                'content'  => 'required|string',
            ]);

            $review = Reply::create($requestValidated);

            return response()->json($review, 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Tạo phản hồi thất bại', 'message' => $e->getMessage()], 500);
        }
    }
}
