<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /** GET /settings — return system config + notifications as structured JSON */
    public function show()
    {
        $system = Setting::get('system_config', json_encode($this->defaultSystemConfigs()));
        $notifs = Setting::get('notifications',  json_encode($this->defaultNotifications()));

        return response()->json([
            'code' => 200, 'message' => 'success',
            'data' => [
                'system_configs' => json_decode($system, true),
                'notifications'  => json_decode($notifs, true),
            ],
        ]);
    }

    /** PUT /settings — save system config and/or notifications */
    public function update(Request $request)
    {
        if ($request->has('system_configs')) {
            Setting::set('system_config', json_encode($request->system_configs));
        }
        if ($request->has('notifications')) {
            Setting::set('notifications', json_encode($request->notifications));
        }

        return response()->json(['code' => 200, 'message' => 'success', 'data' => null]);
    }

    private function defaultSystemConfigs(): array
    {
        return [
            ['key' => 'auto_assign',      'name' => '自動分配訂單',    'desc' => '新訂單自動平均分配給在線坐席',     'type' => 'switch', 'value' => true],
            ['key' => 'axi_draft',        'name' => 'Axi 智能草擬',    'desc' => '啟用 Axi AI 輔助回覆草擬功能',    'type' => 'switch', 'value' => true],
            ['key' => 'axi_bot',          'name' => 'Axi 機器人自動回', 'desc' => '非辦公時間啟用機器人自動應答',     'type' => 'switch', 'value' => false],
            ['key' => 'lang',             'name' => '系統語言',         'desc' => '介面及報表語言設定',               'type' => 'select', 'value' => '繁體中文', 'options' => ['繁體中文', '简体中文', 'English']],
            ['key' => 'timezone',         'name' => '時區設定',         'desc' => '訂單時間戳使用的時區',             'type' => 'select', 'value' => 'HKT (UTC+8)', 'options' => ['HKT (UTC+8)', 'CST (UTC+8)', 'UTC']],
            ['key' => 'session_timeout',  'name' => '會話超時時長',     'desc' => '坐席無操作自動登出時間（分鐘）',   'type' => 'input',  'value' => '60'],
        ];
    }

    private function defaultNotifications(): array
    {
        return [
            ['key' => 'new_order',    'name' => '新訂單提醒',   'desc' => '有新訂單建立時通知相關客服', 'email' => true,  'push' => true,  'sms' => false],
            ['key' => 'exception',    'name' => '訂單異常警告', 'desc' => '訂單進入異常狀態時即時通知', 'email' => true,  'push' => true,  'sms' => true],
            ['key' => 'overdue',      'name' => '超時未回覆',   'desc' => '郵件超過2小時未回覆提醒',    'email' => false, 'push' => true,  'sms' => false],
            ['key' => 'daily_report', 'name' => '每日工作報告', 'desc' => '每日下班前發送工作彙總',     'email' => true,  'push' => false, 'sms' => false],
            ['key' => 'shift_change', 'name' => '交班提醒',     'desc' => '班次交接時提前30分鐘提醒',   'email' => false, 'push' => true,  'sms' => true],
        ];
    }
}
