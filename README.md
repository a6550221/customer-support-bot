# 客服系統 HelpDesk

## 技術棧
- 後端：Laravel 11 + MySQL 8 + Pusher
- 前端：Vue 3 + Element Plus + Vite
- 部署：Railway

---

## Railway 部署步驟（非技術人員版）

### 第一步：準備 Pusher 帳號（免費）
1. 前往 https://pusher.com 並免費註冊
2. 建立一個新 App（Cluster 選 **ap3**）
3. 在 App Keys 頁面，記下：`app_id`、`key`、`secret`、`cluster`

---

### 第二步：部署後端到 Railway

1. 登入 Railway → New Project → Deploy from GitHub Repo
2. 選擇 `a6550221/customer-support-bot`
3. Add Service → Database → Add MySQL
4. 選後端服務 → Settings → Root Directory 設為 `backend`
5. Variables 新增：

```
APP_KEY=（Generate 自動產生）
DB_HOST=mysql.railway.internal
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=（從 MySQL Variables 複製 MYSQL_ROOT_PASSWORD）
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=你的值
PUSHER_APP_KEY=你的值
PUSHER_APP_SECRET=你的值
PUSHER_APP_CLUSTER=ap3
MAIL_MAILER=log
```

6. Deploy → 完成後 Domains → Generate Domain（複製後端網址）

---

### 第三步：部署前端到 Railway

1. 同一 Project → + New → GitHub Repo（同 repo）
2. Root Directory 設為 `frontend`
3. Variables 新增：

```
VITE_API_URL=後端網址
VITE_PUSHER_APP_KEY=你的 Pusher key
VITE_PUSHER_APP_CLUSTER=ap3
```

4. Deploy → Domains → Generate Domain（複製前端網址）

---

### 第四步：補填後端環境變數

```
APP_URL=後端網址
FRONTEND_URL=前端網址
SANCTUM_STATEFUL_DOMAINS=前端網址（去掉 https://）
```

回到後端點 Redeploy。完成！

---

## 預設帳號

| 帳號 | 密碼 | 角色 |
|------|------|------|
| admin@helpdesk.com | password123 | 管理員 |
| supervisor@helpdesk.com | password123 | 主管 |
| agent1@helpdesk.com | password123 | 坐席 |

---

## 嵌入 Live Chat Widget

在您的網站 `</body>` 前加入：

```html
<script src="https://your-backend.railway.app/widget/chat-widget.js" async></script>
```
