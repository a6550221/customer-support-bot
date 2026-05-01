<?php

namespace Database\Seeders;

use App\Models\FollowupTask;
use App\Models\KnowledgeBase;
use App\Models\Order;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Clean up legacy PA-2024-xxxx orders (replaced by proper PA-2025/2026 ones) ──
        $legacyIds = Order::where('order_no', 'like', 'PA-2024-%')->pluck('id');
        if ($legacyIds->isNotEmpty()) {
            TrackingEvent::whereIn('order_id', $legacyIds)->delete();
            Order::whereIn('id', $legacyIds)->delete();
        }

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

        // ── Orders (2025-11 ~ 2026-05) ─────────────────────────────────────────
        // Format: [order_no, customer_name, route, weight, status, assignee, created_at, notes]
        $orders = [
            // === Nov 2025 (7 orders) ===
            ['PA-2025-0887', '李偉強',       'HK → SH', '8.2 kg',  'transit',   $chen->id, '2025-11-05 09:30:00', ''],
            ['PA-2025-0888', 'ABC Corp',     'GZ → TW', '24.5 kg', 'closed',    $wang->id, '2025-11-08 14:20:00', ''],
            ['PA-2025-0889', 'Global Trade', 'GZ → HK', '45 kg',   'closed',    $chen->id, '2025-11-12 10:15:00', ''],
            ['PA-2025-0890', '張美玲',       'HK → BJ', '12.5 kg', 'exception', $chen->id, '2025-11-18 16:45:00', '地址信息不完整，需重新確認'],
            ['PA-2025-0891', '陳志豪',       'HK → TW', '3.1 kg',  'closed',    $wang->id, '2025-11-22 11:30:00', ''],
            ['PA-2025-0892', '劉建國',       'SH → HK', '18 kg',   'closed',    $lin->id,  '2025-11-27 09:00:00', ''],
            ['PA-2025-0893', 'Sunrise Ltd',  'HK → SG', '67 kg',   'closed',    $lin->id,  '2025-11-29 15:30:00', ''],

            // === Dec 2025 (4 orders) ===
            ['PA-2025-0894', '黃偉明',       'HK → BJ', '5.8 kg',  'closed',    $chen->id, '2025-12-03 10:00:00', ''],
            ['PA-2025-0895', '深圳新興貨運', 'GZ → HK', '88 kg',   'closed',    $wang->id, '2025-12-10 14:30:00', ''],
            ['PA-2025-0896', 'Pacific Link', 'HK → SG', '32 kg',   'transit',   $lin->id,  '2025-12-17 09:45:00', ''],
            ['PA-2025-0897', '吳志強',       'SH → HK', '15 kg',   'closed',    $chen->id, '2025-12-24 11:00:00', ''],

            // === Jan 2026 (3 orders) ===
            ['PA-2026-0001', '上海聯合貨運', 'SH → HK', '52 kg',   'closed',    $wang->id, '2026-01-06 10:30:00', ''],
            ['PA-2026-0002', 'Chen Trading', 'HK → TW', '9.4 kg',  'closed',    $chen->id, '2026-01-14 15:20:00', ''],
            ['PA-2026-0003', '廣州貿易有限公司', 'GZ → TW', '41 kg', 'exception', $lin->id, '2026-01-21 09:00:00', '清關文件不齊全'],

            // === Feb 2026 (3 orders) ===
            ['PA-2026-0004', '陳麗華',       'HK → BJ', '7.2 kg',  'closed',    $chen->id, '2026-02-05 11:00:00', ''],
            ['PA-2026-0005', 'Taiwan Express','TW → HK', '28 kg',   'closed',    $wang->id, '2026-02-13 14:15:00', ''],
            ['PA-2026-0006', '梁小明',       'HK → SH', '3.6 kg',  'closed',    $lin->id,  '2026-02-25 16:30:00', ''],

            // === Mar 2026 (3 orders) ===
            ['PA-2026-0007', 'Global Freight','HK → SG', '74 kg',   'closed',    $chen->id, '2026-03-04 09:30:00', ''],
            ['PA-2026-0008', '劉志強',       'SH → HK', '19 kg',   'closed',    $wang->id, '2026-03-12 13:45:00', ''],
            ['PA-2026-0009', '北京商貿公司', 'HK → BJ', '36 kg',   'active',    $lin->id,  '2026-03-20 10:00:00', ''],

            // === Apr 2026 (4 orders) ===
            ['PA-2026-0010', '梁小華',       'GZ → TW', '11.5 kg', 'closed',    $chen->id, '2026-04-02 14:00:00', ''],
            ['PA-2026-0011', 'InterTrade Ltd','HK → SH', '55 kg',   'closed',    $wang->id, '2026-04-09 10:30:00', ''],
            ['PA-2026-0012', '趙明',         'SH → HK', '8.8 kg',  'transit',   $chen->id, '2026-04-16 15:00:00', ''],
            ['PA-2026-0013', '林珊珊',       'HK → TW', '4.2 kg',  'exception', $lin->id,  '2026-04-23 09:15:00', '收件方地址變更，待確認'],

            // === May 2026 (3 orders) ===
            ['PA-2026-0014', '孫國強',       'HK → BJ', '22 kg',   'active',    $wang->id, '2026-05-05 11:00:00', ''],
            ['PA-2026-0015', '深圳速運有限公司','GZ → HK','95 kg',  'pending',   $chen->id, '2026-05-12 14:30:00', ''],
            ['PA-2026-0016', 'Asia Connect', 'HK → SG', '47 kg',   'pending',   $lin->id,  '2026-05-16 09:00:00', ''],
        ];

        foreach ($orders as [$no, $customer, $route, $weight, $status, $assigneeId, $createdAt, $notes]) {
            $order = Order::firstOrCreate(
                ['order_no' => $no],
                [
                    'customer_name' => $customer,
                    'route'         => $route,
                    'weight'        => $weight,
                    'status'        => $status,
                    'assignee_id'   => $assigneeId,
                    'notes'         => $notes,
                ]
            );

            // Always set the correct timestamp so reports work by date range
            DB::table('orders')->where('id', $order->id)->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Add initial tracking event only on first creation
            if ($order->wasRecentlyCreated) {
                TrackingEvent::create([
                    'order_id' => $order->id,
                    'text'     => '訂單已建立',
                    'type'     => 'primary',
                ]);
                if ($status === 'closed') {
                    TrackingEvent::create([
                        'order_id' => $order->id,
                        'text'     => '訂單已完成配送',
                        'type'     => 'success',
                    ]);
                } elseif ($status === 'exception') {
                    TrackingEvent::create([
                        'order_id' => $order->id,
                        'text'     => '訂單進入異常狀態，客服跟進中',
                        'type'     => 'danger',
                    ]);
                }
            }
        }

        // ── Knowledge Base ─────────────────────────────────────────────────────
        if (KnowledgeBase::count() === 0) {
            $kb = [
                ['type' => 'faq',      'question' => '貨物在運輸途中損壞怎麼辦？',     'answer' => 'PrimeAxis 對所有貨物提供保險，請在收件後24小時內拍照上傳並聯繫客服，我們將在3個工作日內處理賠償申請。', 'usage_count' => 248],
                ['type' => 'faq',      'question' => '預計到達時間如何查詢？',          'answer' => '您可以通過官網訂單追蹤頁面，或直接聯繫客服提供訂單號，我們實時同步物流數據。', 'usage_count' => 312],
                ['type' => 'policy',   'question' => '取消訂單政策',                    'answer' => '訂單在取件前可免費取消。取件後如需取消，需視貨物所在位置收取相應費用。詳情請參閱服務條款第5.2條。', 'usage_count' => 120],
                ['type' => 'template', 'question' => '異常訂單回覆範本',                'answer' => '親愛的[客戶姓名]，您的訂單 [訂單號] 目前遇到以下問題：[問題描述]。我們正在積極處理，預計在[時間]內解決。如有疑問請聯繫我們。', 'usage_count' => 189],
                ['type' => 'guide',    'question' => '如何處理收件地址不完整的訂單？',   'answer' => '1) 立即將訂單標記為「異常」狀態 2) 通過郵件或電話聯繫客戶確認完整地址 3) 更新訂單信息 4) 安排重新派送', 'usage_count' => 95],
                ['type' => 'faq',      'question' => '可以修改收件地址嗎？',             'answer' => '貨物到達目的城市倉庫前可修改收件地址，需提前24小時通知。到達後如需修改將收取額外費用。', 'usage_count' => 167],
            ];
            foreach ($kb as $item) {
                KnowledgeBase::create($item);
            }
        }

        // ── Followup tasks ─────────────────────────────────────────────────────
        if (FollowupTask::count() === 0) {
            $tasks = [
                ['title' => '跟進客戶林珊珊訂單 PA-2026-0013 異常問題',   'order_no' => 'PA-2026-0013', 'customer' => '林珊珊',      'priority' => 'high',   'status' => 'inprogress', 'due_time' => '12:00', 'note' => '收件地址已變更，等待客戶確認', 'assignee_id' => $lin->id],
                ['title' => '確認 Asia Connect 清關材料',                   'order_no' => 'PA-2026-0016', 'customer' => 'Asia Connect', 'priority' => 'high',   'status' => 'todo',       'due_time' => '14:00', 'note' => '需提供貨物清單及報關單',      'assignee_id' => $chen->id],
                ['title' => '跟進深圳速運有限公司取件安排',                 'order_no' => 'PA-2026-0015', 'customer' => '深圳速運',    'priority' => 'medium', 'status' => 'todo',       'due_time' => '16:00', 'note' => '預約14:00-17:00取件窗口',     'assignee_id' => $wang->id],
                ['title' => '更新孫國強訂單物流時間軸',                     'order_no' => 'PA-2026-0014', 'customer' => '孫國強',      'priority' => 'medium', 'status' => 'todo',       'due_time' => '11:00', 'note' => '',                            'assignee_id' => $wang->id],
                ['title' => '確認趙明訂單抵達香港時間',                     'order_no' => 'PA-2026-0012', 'customer' => '趙明',        'priority' => 'medium', 'status' => 'inprogress', 'due_time' => '15:30', 'note' => '貨物已過關，等待派送通知',    'assignee_id' => $chen->id],
                ['title' => '處理北京商貿公司訂單派送',                     'order_no' => 'PA-2026-0009', 'customer' => '北京商貿公司','priority' => 'low',    'status' => 'todo',       'due_time' => '17:00', 'note' => '聯繫收件方確認派送時間',       'assignee_id' => $lin->id],
                ['title' => '發送廣州貿易異常通知',                         'order_no' => 'PA-2026-0003', 'customer' => '廣州貿易',    'priority' => 'high',   'status' => 'done',       'due_time' => '09:30', 'note' => '已通知客戶補充清關文件',        'assignee_id' => $lin->id],
            ];
            foreach ($tasks as $task) {
                FollowupTask::create($task);
            }
        }
    }
}
