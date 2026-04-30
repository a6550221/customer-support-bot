<?php

namespace Database\Seeders;

use App\Models\FollowupTask;
use App\Models\KnowledgeBase;
use App\Models\Order;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ──────────────────────────────────────────────────────────────
        $admin = User::firstOrCreate(['email' => 'admin@primeaxis.com'], [
            'name'     => '系統管理員',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'status'   => 'online',
            'active'   => true,
        ]);

        $chen = User::firstOrCreate(['email' => 'chen@primeaxis.com'], [
            'name'     => '陳小明',
            'password' => Hash::make('password123'),
            'role'     => 'supervisor',
            'status'   => 'online',
            'active'   => true,
        ]);

        $wang = User::firstOrCreate(['email' => 'wang@primeaxis.com'], [
            'name'     => '王大華',
            'password' => Hash::make('password123'),
            'role'     => 'agent',
            'status'   => 'online',
            'active'   => true,
        ]);

        $lin = User::firstOrCreate(['email' => 'lin@primeaxis.com'], [
            'name'     => '林曉月',
            'password' => Hash::make('password123'),
            'role'     => 'agent',
            'status'   => 'offline',
            'active'   => true,
        ]);

        // ── Sample orders ──────────────────────────────────────────────────────
        $orders = [
            ['order_no' => 'PA-2024-0893', 'customer_name' => '李偉強',       'route' => 'HK → SH', 'weight' => '8.2 kg',  'status' => 'transit',   'assignee_id' => $chen->id],
            ['order_no' => 'PA-2024-0892', 'customer_name' => 'ABC Corp',     'route' => 'GZ → TW', 'weight' => '24.5 kg', 'status' => 'pending',   'assignee_id' => $wang->id],
            ['order_no' => 'PA-2024-0891', 'customer_name' => 'Global Trade', 'route' => 'GZ → HK', 'weight' => '45 kg',   'status' => 'active',    'assignee_id' => $chen->id],
            ['order_no' => 'PA-2024-0890', 'customer_name' => '張美玲',       'route' => 'HK → BJ', 'weight' => '12.5 kg', 'status' => 'exception', 'assignee_id' => $chen->id],
            ['order_no' => 'PA-2024-0889', 'customer_name' => '陳志豪',       'route' => 'HK → TW', 'weight' => '3.1 kg',  'status' => 'active',    'assignee_id' => $wang->id],
            ['order_no' => 'PA-2024-0888', 'customer_name' => '劉建國',       'route' => 'SH → HK', 'weight' => '18 kg',   'status' => 'transit',   'assignee_id' => $lin->id],
            ['order_no' => 'PA-2024-0887', 'customer_name' => 'Sunrise Ltd',  'route' => 'HK → SG', 'weight' => '67 kg',   'status' => 'closed',    'assignee_id' => $lin->id],
        ];

        foreach ($orders as $orderData) {
            $order = Order::firstOrCreate(['order_no' => $orderData['order_no']], $orderData);
            if ($order->wasRecentlyCreated) {
                TrackingEvent::create(['order_id' => $order->id, 'text' => '訂單已建立', 'type' => 'primary']);
            }
        }

        // ── Knowledge Base ─────────────────────────────────────────────────────
        if (KnowledgeBase::count() === 0) {
            $kb = [
                ['type' => 'faq',      'question' => '貨物在運輸途中損壞怎麼辦？',   'answer' => 'PrimeAxis 對所有貨物提供保險，請在收件後24小時內拍照上傳並聯繫客服，我們將在3個工作日內處理賠償申請。', 'usage_count' => 248],
                ['type' => 'faq',      'question' => '預計到達時間如何查詢？',        'answer' => '您可以通過官網訂單追蹤頁面，或直接聯繫客服提供訂單號，我們實時同步物流數據。', 'usage_count' => 312],
                ['type' => 'policy',   'question' => '取消訂單政策',                  'answer' => '訂單在取件前可免費取消。取件後如需取消，需視貨物所在位置收取相應費用。詳情請參閱服務條款第5.2條。', 'usage_count' => 120],
                ['type' => 'template', 'question' => '異常訂單回覆範本',              'answer' => '親愛的[客戶姓名]，您的訂單 [訂單號] 目前遇到以下問題：[問題描述]。我們正在積極處理，預計在[時間]內解決。如有疑問請聯繫我們。', 'usage_count' => 189],
                ['type' => 'guide',    'question' => '如何處理收件地址不完整的訂單？', 'answer' => '1) 立即將訂單標記為「異常」狀態 2) 通過郵件或電話聯繫客戶確認完整地址 3) 更新訂單信息 4) 安排重新派送', 'usage_count' => 95],
                ['type' => 'faq',      'question' => '可以修改收件地址嗎？',          'answer' => '貨物到達目的城市倉庫前可修改收件地址，需提前24小時通知。到達後如需修改將收取額外費用。', 'usage_count' => 167],
            ];
            foreach ($kb as $item) {
                KnowledgeBase::create($item);
            }
        }

        // ── Followup tasks ─────────────────────────────────────────────────────
        if (FollowupTask::count() === 0) {
            $tasks = [
                ['title' => '跟進客戶張美玲訂單 PA-2024-0890 異常問題',   'order_no' => 'PA-2024-0890', 'customer' => '張美玲',      'priority' => 'high',   'status' => 'inprogress', 'due_time' => '12:00', 'note' => '地址信息不完整，需重新確認',  'assignee_id' => $chen->id],
                ['title' => '回覆 Global Trade 取件確認郵件',               'order_no' => 'PA-2024-0891', 'customer' => 'Global Trade', 'priority' => 'high',   'status' => 'todo',       'due_time' => '11:00', 'note' => '安排14:00-17:00取件窗口',     'assignee_id' => $chen->id],
                ['title' => '確認 ABC Corp 貨物清關情況',                   'order_no' => 'PA-2024-0892', 'customer' => 'ABC Corp',     'priority' => 'medium', 'status' => 'todo',       'due_time' => '15:00', 'note' => '',                            'assignee_id' => $wang->id],
                ['title' => '更新李偉強訂單物流時間軸',                     'order_no' => 'PA-2024-0893', 'customer' => '李偉強',       'priority' => 'medium', 'status' => 'done',       'due_time' => '10:00', 'note' => '已更新抵達香港倉庫記錄',       'assignee_id' => $chen->id],
                ['title' => '通知陳志豪訂單派送完成',                       'order_no' => 'PA-2024-0889', 'customer' => '陳志豪',       'priority' => 'low',    'status' => 'done',       'due_time' => '09:30', 'note' => '已發送確認短信',               'assignee_id' => $wang->id],
                ['title' => '核對劉建國訂單重量文件',                       'order_no' => 'PA-2024-0888', 'customer' => '劉建國',       'priority' => 'medium', 'status' => 'todo',       'due_time' => '16:30', 'note' => '需核對報關單重量',             'assignee_id' => $lin->id],
                ['title' => '跟進 Sunrise Ltd 已結單款項',                  'order_no' => 'PA-2024-0887', 'customer' => 'Sunrise Ltd',  'priority' => 'low',    'status' => 'inprogress', 'due_time' => '17:00', 'note' => '財務確認後標記完成',           'assignee_id' => $lin->id],
            ];
            foreach ($tasks as $task) {
                FollowupTask::create($task);
            }
        }
    }
}
