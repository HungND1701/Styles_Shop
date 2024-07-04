<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use App\Models\TempImage;

class UploadController extends Controller
{
    public function store(Request $request)
    {

//validate the file upload
        $request->validate([
            'file' => 'required|image|max:10240',
        ]);

//get the credentials in the json file
        $googleConfigFile = file_get_contents(config_path('service-account.json'));

//create a StorageClient object
        $storage = new StorageClient([
            'keyFile' => json_decode($googleConfigFile, true)
        ]);

//get the bucket name from the env file
        $storageBucketName = config('googlecloud.storage_bucket');

//pass in the bucket name
        $bucket = $storage->bucket($storageBucketName);

        $file_request = $request->file('file');

        $image_path = $file_request->getRealPath();

//rename the file
        $file_name = time().'.'.$file_request->extension();

//open the file using fopen
        $fileSource = fopen($image_path, 'r');

//specify the path to the folder and sub-folder where needed
        $googleCloudStoragePath = 'laravel-upload/' . $file_name;

//upload the new file to google cloud storage 
        $bucket->upload($fileSource, [
            'predefinedAcl' => 'publicRead',
            'name' => $googleCloudStoragePath
        ]);

        $url = 'https://storage.googleapis.com/hungnd/'. $googleCloudStoragePath;
        $temp = TempImage::create(['url' => $url]);

        return response()->json($url, 201);
    }

    public function destroy(Request $request)
    {
        // Validate the file deletion request
        $request->validate([
            'url' => 'required|string',
        ]);

        // Get the credentials in the json file
        $googleConfigFile = file_get_contents(config_path('service-account.json'));

        // Create a StorageClient object
        $storage = new StorageClient([
            'keyFile' => json_decode($googleConfigFile, true)
        ]);

        // Get the bucket name from the env file
        $storageBucketName = config('googlecloud.storage_bucket');

        // Pass in the bucket name
        $bucket = $storage->bucket($storageBucketName);

        // Get the file name to be deleted
        $url = $request->input('url');

        // Sử dụng hàm parse_url để lấy phần path của URL
        $parsed_url = parse_url($url, PHP_URL_PATH);

        // Tách đường dẫn để lấy phần "review-image/1719110368.png"
        $path_parts = explode('/', $parsed_url);

        // Lấy phần cuối cùng của mảng là phần bạn cần
        $googleCloudStoragePath = $path_parts[count($path_parts) - 2] . '/' . $path_parts[count($path_parts) - 1];

        // Get the object (file) from the bucket
        $object = $bucket->object($googleCloudStoragePath);

        // Delete the object from the bucket
        $object->delete();

        return response()->json(['message' => 'File deleted successfully'], 200);
    }

    public function storeReviewImage(Request $request)
    {

//validate the file upload
        $request->validate([
            'file' => 'required|image|max:10240',
        ]);

//get the credentials in the json file
        $googleConfigFile = file_get_contents(config_path('service-account.json'));

//create a StorageClient object
        $storage = new StorageClient([
            'keyFile' => json_decode($googleConfigFile, true)
        ]);

//get the bucket name from the env file
        $storageBucketName = config('googlecloud.storage_bucket');

//pass in the bucket name
        $bucket = $storage->bucket($storageBucketName);

        $file_request = $request->file('file');

        $image_path = $file_request->getRealPath();

//rename the file
        $file_name = time().'.'.$file_request->extension();

//open the file using fopen
        $fileSource = fopen($image_path, 'r');

//specify the path to the folder and sub-folder where needed
        $googleCloudStoragePath = 'review-image/' . $file_name;

//upload the new file to google cloud storage 
        $bucket->upload($fileSource, [
            'predefinedAcl' => 'publicRead',
            'name' => $googleCloudStoragePath
        ]);

        $url = 'https://storage.googleapis.com/hungnd/'. $googleCloudStoragePath;
        TempImage::create(['url' => $url]);
        return response()->json($url, 201);
    }

}

        
    
