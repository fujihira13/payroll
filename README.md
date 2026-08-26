# 給与明細ポータル（学習用）

Laravel 11・PHP 8.3・MariaDB・Blade・Dockerで作成した、複数会社対応の給与明細Webシステムです。

> [!WARNING]
> Laravel 11はすでにセキュリティサポートが終了しています。会社指定のバージョンを学ぶため、Composerの既知脆弱性ブロックをこのプロジェクト内だけで例外設定しています。本番・実データには使用せず、実運用ではサポート中のLaravelへ更新してください。

## 主な機能

| 権限 | できること |
|---|---|
| システム管理者 | 会社、企業管理者、全社共通の給与明細テンプレートを管理 |
| 企業管理者 | 自社の部署・社員、社員CSV、明細設定、給与CSV、承認・予約公開、メール文面、閲覧状況を管理 |
| 一般社員 | 公開済みの自分の給与明細だけを閲覧、PDF出力、パスワード変更 |

- `/health` でWebアプリとMariaDBの疎通確認
- Schedulerが毎分、公開予約時刻を迎えた給与を公開してメール通知
- 初回・最終閲覧日時と閲覧回数を記録
- mPDFとIPA/Noto日本語フォントによるPDF生成
- 会社ごとに共通テンプレートをコピーして項目を変更可能
- ログイン失敗回数の記録と、1分あたり6回の試行制限
- 会社IDを使ったテナント分離

## 起動方法

必要なのはDocker Desktopです。プロジェクト直下で実行します。

```powershell
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan payroll:create-system-admin
```

3つ目のコマンドで、最初のシステム管理者の氏名・メールアドレス・パスワードを対話入力します。`DatabaseSeeder`は意図的に空で、サンプルの社員・給与データは入りません。

- Web画面: <http://localhost:8080>
- メール確認（Mailpit）: <http://localhost:8025>
- ヘルスチェック: <http://localhost:8080/health>
- MariaDB（ホストから）: `localhost:3307`

停止は `docker compose down` です。DBデータはDocker volumeに残ります。

DBデータも消して完全に初期化する操作は `docker compose down -v` です。これは復元できないため、必要な場合だけ実行してください。

## 基本の操作順

1. システム管理者が会社を登録する。
2. システム管理者が、その会社に所属する企業管理者を登録する。
3. システム管理者が給与明細の共通テンプレートと項目を作る。
4. 企業管理者が部署と社員を登録する（画面またはCSV）。
5. 企業管理者が共通テンプレートを選び、自社用の明細設定を作る。
6. 企業管理者が給与処理を作り、明細設定に対応したCSVを取り込む。
7. 内容を確認し、公開日時を指定して承認する。
8. Schedulerが自動公開し、社員にログインURLをメール送信する。
9. 社員がログインして明細を閲覧またはPDF出力する。
10. 企業管理者が給与処理画面で未読・既読、閲覧日時、回数を確認する。

## CSV仕様

社員CSVは社員一覧画面から見本をダウンロードできます。

```csv
employee_number,name,email,department_code,password
E001,山田 太郎,taro@example.test,SALES,ChangeMe123!
```

- `employee_number` は同じ会社内で一意です。
- `email` はシステム全体で一意です。
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

- `app/Enums`: 3権限、明細状態、項目種別
- `app/Http/Controllers/System`: システム管理者機能
- `app/Http/Controllers/Company`: 企業管理者機能
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
