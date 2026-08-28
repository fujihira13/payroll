# 給与明細ポータル（学習用）

Laravel 11・PHP 8.3・MariaDB・Blade・Dockerで作成した、複数会社対応の給与明細Webシステムです。

> [!WARNING]
> Laravel 11はすでにセキュリティサポートが終了しています。会社指定のバージョンを学ぶため、Composerの既知脆弱性ブロックをこのプロジェクト内だけで例外設定しています。本番・実データには使用せず、実運用ではサポート中のLaravelへ更新してください。

## 主な機能

| 権限 | できること |
|---|---|
| システム管理者 | `/login/manage`からログインし、企業、システム管理者、全社共通のベース帳票を管理 |
| 社員管理者 | `/login`からログインし、`permission=9`で自社の部署・社員、明細設定、給与、メール、閲覧状況を管理 |
| 一般社員 | 公開済みの自分の給与明細だけを閲覧、PDF出力、パスワード変更 |

- `/health` でWebアプリとMariaDBの疎通確認
- Schedulerが毎分、公開予約時刻を迎えた給与を公開してメール通知
- 初回・最終閲覧日時と閲覧回数を記録
- mPDFとIPA/Noto日本語フォントによるPDF生成
- 会社ごとに共通テンプレートをコピーして項目を変更可能
- システム管理者と企業利用者のログイン入口・認証ガードを分離
- `/login`利用者は`permission=1`を一般社員、`permission=9`を社員管理者として判定
- 5回失敗時のアカウントロック、仮パスワード、初回変更強制
- 会社IDを使ったテナント分離

## 起動方法

必要なのはDocker Desktopです。プロジェクト直下で実行します。

```powershell
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan payroll:create-system-admin
```

3つ目のコマンドで、最初のシステム管理者の氏名・ログインID・パスワードを対話入力します。`DatabaseSeeder`は意図的に空で、サンプルの社員・給与データは入りません。

- 企業利用者ログイン: <http://localhost:8080/login>
- システム管理者ログイン: <http://localhost:8080/login/manage>
- メール確認（Mailpit）: <http://localhost:8025>
- ヘルスチェック: <http://localhost:8080/health>
- MariaDB（ホストから）: `localhost:3307`

停止は `docker compose down` です。DBデータはDocker volumeに残ります。

起動時に`permissions`サービスがLaravelの実行時ディレクトリの所有者を補正し、`app`と`scheduler`は`www-data`ユーザーで動作します。これにより、Artisanコマンドや画面表示で作成されるログ・Bladeキャッシュがroot所有になり、画面が500エラーになることを防ぎます。

DBデータも消して完全に初期化する操作は `docker compose down -v` です。これは復元できないため、必要な場合だけ実行してください。

## 基本の操作順

1. システム管理者が会社と初期社員管理者を登録する。
2. 初期社員管理者へ一度だけ表示された仮パスワードを安全に通知する。
3. システム管理者が給与明細のベース帳票、項目、初期スロットを作る。
4. 社員管理者が部署と社員を登録する（画面またはCSV）。
5. 社員管理者がベース帳票を選び、項目割当と確認を経て自社用帳票を作る。
6. 社員管理者が給与処理を作り、明細設定に対応したCSVを取り込む。
7. 内容を確認し、公開日時を指定して承認する。
8. Schedulerが自動公開し、社員にログインURLをメール送信する。
9. 社員がログインして明細を閲覧またはPDF出力する。
10. 社員管理者が給与処理画面で未読・既読、閲覧日時、回数を確認する。

## CSV仕様

社員CSVは社員一覧画面から見本をダウンロードできます。

```csv
employee_number,login_id,name,email,department_code,permission,password
E001,staff001,山田 太郎,taro@example.test,SALES,1,ChangeMe123!
```

- `employee_number` は同じ会社内で一意です。
- `login_id` は同じ会社内で一意です。省略可能な旧CSVでは社員番号をログインIDとして扱います。
- `email` はシステム全体で一意です。
- `permission`は一般社員が`1`、社員管理者が`9`です。これ以外の値は取り込めません。
- 新規社員では `password` が必須です。既存社員の更新では空欄なら現在のパスワードを保持します。
- UTF-8（BOMあり・なし）とShift_JISを読み取れます。

給与CSVは給与処理の詳細画面から、その会社の明細項目に合った見本をダウンロードできます。先頭列は必ず `employee_number`、残りは明細設定の項目コードです。

```csv
employee_number,basic_salary,overtime,income_tax
E001,300000,20000,15000
```

支給項目を合計したものが総支給、控除項目を合計したものが控除合計、差額が差引支給額です。

## 開発コマンド

```powershell
# 全テスト
docker compose exec app php artisan test

# 予約公開処理を今すぐ1回実行
docker compose exec app php artisan payslips:publish-scheduled

# ルート一覧
docker compose exec app php artisan route:list

# ログ確認
docker compose logs -f app scheduler

# 依存パッケージの脆弱性確認
docker compose exec app composer audit
```

## 構成

- `app/Models/Admin.php`: `/login/manage`専用のシステム管理者アカウント
- `app/Enums`: 旧データ互換ロール、明細状態、項目種別
- `app/Http/Controllers/System`: システム管理者機能
- `app/Http/Controllers/Company`: permission 9の社員管理者機能
- `app/Http/Controllers/Employee`: 一般社員機能
- `app/Services`: CSV取込と予約公開の業務処理
- `database/migrations`: テーブル定義
- `resources/views`: Blade画面とPDF・メールテンプレート
- `routes/web.php`: URLと権限ミドルウェア
- `routes/console.php`: Scheduler登録
- `docker`: Nginx・MariaDB設定
- `docs/LEARNING_GUIDE.md`: コードを読む順番と仕組みの解説
- `docs/deliverables/README.md`: 外部設計・テスト仕様・見積・運用手順の成果物一覧

## テスト用DB

自動テストは通常の `payroll` DBではなく `payroll_test` DBを使います。`RefreshDatabase`でテストごとに初期化されるため、開発画面に登録したデータを壊しません。
# payroll
