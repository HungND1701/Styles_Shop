<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Status;
use App\Models\PaymentMethod;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        Status::insert(
            [
                ['id'=>1, 'name' => 'Chờ xác nhận'],
                ['id'=>2, 'name' => 'Đã xác nhận'],
                ['id'=>3, 'name' => 'Chờ lấy hàng'],
                ['id'=>4, 'name' => 'Đang giao hàng'],
                ['id'=>5, 'name' => 'Đã giao'],
                ['id'=>6, 'name' => 'Đã Hủy'],
                ['id'=>7, 'name' => 'Hoàn thành'],
                ['id'=>8, 'name' => 'Thất bại'],
                ['id'=>9, 'name' => 'Đã trả hàng'],
                ['id'=>10, 'name' => 'Đã hoàn tiền'],
            ]
        );  
        PaymentMethod::insert(
            [
                ['id'=>2, 'name' => 'Vnpay'],
                ['id'=>3, 'name' => 'Momo'],
                ['id'=>4, 'name' => 'Qrcode'],
            ]
        );   
    }
}
