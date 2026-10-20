<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $order = Order::factory()
            ->for($user)
            ->create(['status' => OrderStatus::Confirmed, 'total' => 0]);

        $items = OrderItem::factory()
            ->count(2)
            ->for($order)
            ->create();

        $order->update(['total' => $items->sum('subtotal')]);
    }
}
