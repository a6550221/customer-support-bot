<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\QuickReply;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(['email' => 'admin@helpdesk.com'], [
            'name'     => 'Admin',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'status'   => 'online',
        ]);

        // Supervisor
        User::firstOrCreate(['email' => 'supervisor@helpdesk.com'], [
            'name'       => '主管 Wang',
            'password'   => Hash::make('password123'),
            'role'       => 'supervisor',
            'department' => 'Customer Support',
            'status'     => 'online',
        ]);

        // Agents
        foreach (['Agent Li', 'Agent Chen', 'Agent Zhang'] as $i => $name) {
            User::firstOrCreate(['email' => 'agent' . ($i + 1) . '@helpdesk.com'], [
                'name'       => $name,
                'password'   => Hash::make('password123'),
                'role'       => 'agent',
                'department' => 'Customer Support',
                'status'     => 'online',
            ]);
        }

        // Categories
        $billing = Category::firstOrCreate(['name' => '帳單與付款', 'parent_id' => null]);
        $tech    = Category::firstOrCreate(['name' => '技術支援', 'parent_id' => null]);
        $general = Category::firstOrCreate(['name' => '一般諮詢', 'parent_id' => null]);
        Category::firstOrCreate(['name' => '退款申請', 'parent_id' => $billing->id]);
        Category::firstOrCreate(['name' => '登入問題', 'parent_id' => $tech->id]);

        // Sample customer
        Customer::firstOrCreate(['email' => 'customer@example.com'], [
            'name'    => '測試客戶',
            'phone'   => '0912-345-678',
            'company' => 'Example Corp',
        ]);

        // Global quick replies
        QuickReply::firstOrCreate(['title' => '感謝您的耐心等候'], [
            'content'   => '感謝您的耐心等候，我們會盡快為您處理。',
            'is_global' => true,
            'agent_id'  => $admin->id,
        ]);
        QuickReply::firstOrCreate(['title' => '問題已解決確認'], [
            'content'   => '您好，您的問題已經處理完畢，如有其他需要請隨時聯絡我們。',
            'is_global' => true,
            'agent_id'  => $admin->id,
        ]);
        QuickReply::firstOrCreate(['title' => '需要更多資訊'], [
            'content'   => '為了更好地協助您，請提供以下資訊：\n1. 訂單編號\n2. 問題發生時間\n3. 錯誤截圖（如有）',
            'is_global' => true,
            'agent_id'  => $admin->id,
        ]);
    }
}
