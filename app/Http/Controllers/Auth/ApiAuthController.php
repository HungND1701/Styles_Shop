<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiAuthController extends Controller
{
    
    public function register (Request $request) {
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phoneNumber' => 'required|string|max:30',
            'password' => 'required|string|min:6|confirmed',
            'type' => 'nullable|integer',
        ]);
        if ($validator->fails())
        {
            Log::error('Lỗi xác thực: ', $validator->errors()->toArray());
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $request['password']=Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $request['type'] = $request->has('type') ? $request['type']  : 0;
        $user = User::create($request->toArray());
        // $token = $user->createToken('Laravel Password Grant Client')->accessToken;
        // $response = ['token' => $token];
        return response(['message'=>'Đăng kí thành công'], 200);
    }

    public function login (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                
                // Cấu hình thông tin người dùng gửi về
                $userInfo = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => $user->type,
                ];
                
                $response = [
                    'user' => $userInfo,
                    'token' => $token
                ];

                return response($response, 200);
            } else {
                $response = ["message" => "Mật khẩu không đúng"];
                return response($response, 422);
            }
        } else {
            $response = ["message" =>'Không tồn tại tài khoản'];
            return response($response, 422);
        }
    }

    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }
}
