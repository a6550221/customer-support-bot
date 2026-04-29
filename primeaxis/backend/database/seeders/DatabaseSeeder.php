<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(['email' => 'admin@primeaxis.com'], [
            'name'     => '系統管理員',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'status'   => 'online',
        ]);

        // Agents
        $chen = User::firstOrCreate(['email' => 'chen@primeaxis.com'], [
            'name'     => '陳小明',
            'password' => Hash::make('password123'),
            'role'     => 'supervisor',
            'status'   => 'online',
        ]);

        $wang = User::firstOrCreate(['email' => 'wang@primeaxis.com'], [
            'name'     => '王大華',
            'password' => Hash::make('password123'),
            'role'     => 'agent',
            'status'   => 'online',
        ]);

        $lin = User::firstOrCreate(['email' => 'lin@primeaxis.com'], [
            'name'     => '林曉月',
            'password' => Hash::make('password123'),
            'role'     => 'agent',
            'status'   => 'offline',
        ]);

        // Sample orders
        $orders = [
            ['order_no' => 'PA-2024-0893', 'customer_name' => '李偉強',      'route' => 'HK → SH', 'weight' => '8.2 kg',  'status' => 'transit',   'assignee_id' => $chen->id],
            ['order_no' => 'PA-2024-0892', 'customer_name' => 'ABC Corp',    'route' => 'GZ → TW', 'weight' => '24.5 kg', 'status' => 'pending',   'assignee_id' => $wang->id],
            ['order_no' => 'PA-2024-0891', 'customer_name' => 'Global Trade','route' => 'GZ → HK', 'weight' => '45 kg',   'status' => 'active',    'assignee_id' => $chen->id],
            ['order_no' => 'PA-2024-0890', 'customer_name' => '張美玲',      'route' => 'HK → BJ', 'weight' => '12.5 kg', 'status' => 'exception', 'assignee_id' => $chen->id],
            ['order_no' => 'PA-2024-0889', 'customer_name' => '陳志豪',      'route' => 'HK → TW', 'weight' => '3.1 kg',  'status' => 'active',    'assignee_id' => $wang->id],
            ['order_no' => 'PA-2024-0888', 'customer_name' => '劉建國',      'route' => 'SH → HK', 'weight' => '18 kg',   'status' => 'transit',   'assignee_id' => $lin->id],
            ['order_no' => 'PA-2024-0887', 'customer_name' => 'Sunrise Ltd', 'route' => 'HK → SG', 'weight' => '67 kg',   'status' => 'closed',    'assignee_id' => $lin->id],
        ];

        foreach ($orders as $orderData) {
            $order = Order::firstOrCreate(['order_no' => $orderData['order_no']], $orderData);

            if ($order->wasRecentlyCreated) {
                TrackingEvent::create([
                    'order_id' => $order->id,
                    'text'     => '訂單已建立',
                    'type'     => 'primary',
                ]);
            }
        }
    }
}
