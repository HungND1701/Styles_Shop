<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TempImage;
use Carbon\Carbon;
use Google\Cloud\Storage\StorageClient;

class CleanUpTempImages extends Command
{
    protected $signature = 'cleanup:temp-images';
    protected $description = 'Clean up temporary images that are older than 24 hours';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        info("Cron Job running at ". now());
        $expirationTime = Carbon::now()->subHours(1);

        $expiredImages = TempImage::where('created_at', '<', $expirationTime)->get();

        foreach ($expiredImages as $image) {
            $this->deleteFromCloud($image->url);
            $image->delete();
        }

        $this->info('Cleaned up expired temporary images.');
    }

    private function deleteFromCloud($url)
    {
        $googleConfigFile = file_get_contents(config_path('service-account.json'));

        $storage = new StorageClient([
            'keyFile' => json_decode($googleConfigFile, true)
        ]);

        $storageBucketName = config('googlecloud.storage_bucket');

        $bucket = $storage->bucket($storageBucketName);

        $parsed_url = parse_url($url, PHP_URL_PATH);
        $path_parts = explode('/', $parsed_url);
        $googleCloudStoragePath = $path_parts[count($path_parts) - 2] . '/' . $path_parts[count($path_parts) - 1];

        $object = $bucket->object($googleCloudStoragePath);
        $object->delete();
    }
}
