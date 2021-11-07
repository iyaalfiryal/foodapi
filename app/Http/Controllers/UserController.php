<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    // // Library Password Validation for use in Register
    use PasswordValidationRules;


    // API Login User
    public function login(Request $request)
    {
        // Validation User
        try {
            // Validation Input User
            $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);

            // Validation Data User
            $credentials = request(['email', 'password']);
            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Failed', 500);
            }

            // If Data User InCorrect -> Response Error
            $user = User::where('email', $request->email)->first(); // Check Email
            if (!Hash::check($request->password, $user->password, [])) { // Check Password
                throw new \Exception('Invalid Credentials');
            }

            // If Data User Correct -> Login User
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Login Success');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Login Failed', 500);
        }
    }

    // API Register User
    public function register(Request $request)
    {
        try {
            // Validation Input User
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => $this->passwordRules()
            ]);
            // Create Data User
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'houseNumber' => $request->houseNumber,
                'phoneNumber' => $request->phoneNumber,
                'city' => $request->city,
                'password' => Hash::make($request->password),
            ]);

            // Get Data User
            $user = User::where('email', $request->email)->first();

            // Get Token User
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            // Return Data Token & User for Login
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ]);
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Register Failed', 500);
        }
    }

    // API Logout User
    public function logout(Request $request)
    {
        // Get Data User was Login by token
        $token = $request->user()->currentAccessToken()->delete();

        return ResponseFormatter::success($token, 'Token Revoked');
    }

    // API Get Data User was Login
    public function fetch(Request $request)
    {
        return ResponseFormatter::success(
            $request->user(),
            'Get Data User Success'
        );
    }

    // API Update Profile User
    public function updateProfile(Request $request)
    {
        // Get All Data User
        $data = $request->all();

        // Get Data User was Login
        $user = Auth::user();
        $user->save;
        // $user->update($data);

        return ResponseFormatter::success($user, 'Profile Updated');
    }

    // API Update Photo Profile User
    public function updatePhoto(Request $request)
    {
        // Create Validation Photo size < 2mb
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:2048'
        ]);

        // If validator Failed -> Response Error
        if ($validator->fails()) {
            return ResponseFormatter::error(
                ['error' => $validator->errors()],
                'Update Photo Failed',
                401
            );
        }

        // Check File Photo
        if ($request->file('file')) {
            // Upload File Photo
            $file = $request->file->store('assets/user', 'public');
            // Save Photo in Database -> Url Photo
            $user = Auth::user();
            $user->profile_photo_path = $file;
            $user->save;
            // $user->update();

            return ResponseFormatter::success([$file], 'File Success Upload');
        };
    }
}