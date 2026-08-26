# 給与明細ポータル 起動・運用・障害対応手順書

文書版: 1.0  
作成日: 2026-08-25  
対象環境: Windows、Docker Desktop、ローカル学習環境

## 1. システム構成

| サービス | 役割 | 公開先 |
|---|---|---|
| `web` | Nginx、HTTP受付 | <http://localhost:8080> |
| `app` | PHP 8.3 / Laravel 11 | Docker内部の9000番 |
| `db` | MariaDB 11.4 | `localhost:3307` |
| `scheduler` | 予約公開処理を毎分実行 | 外部公開なし |
| `mailpit` | 開発メールの受信・確認 | <http://localhost:8025> |

プロジェクトの絶対パスは次です。

```text
C:\dev\会社プロジェクト
```

## 2. 初回起動

1. Docker Desktopを起動する。
2. PowerShellを開く。
3. 次を順番に実行する。

```powershell
cd "C:\dev\会社プロジェクト"
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan payroll:create-system-admin
```

最後のコマンドでは、最初のシステム管理者の氏名、メールアドレス、8文字以上のパスワードを入力します。パスワード入力中に文字が表示されないのは正常です。

初期データ用Seederは空です。会社、会社管理者、部署、社員、明細テンプレートは画面から登録します。

## 3. 通常起動と停止

PC再起動後など、構築済み環境を起動する場合:

```powershell
cd "C:\dev\会社プロジェクト"
docker compose up -d
```

停止する場合:

```powershell
docker compose down
```

`docker compose down`ではMariaDBのデータは残ります。`docker compose down -v`はDBボリュームを削除し、登録データを失うため、初期化を明示的に行う場合以外は実行しないでください。

## 4. 起動確認

### 4.1 コンテナ状態

```powershell
docker compose ps
```

`app`、`web`、`db`、`scheduler`、`mailpit`が起動していることを確認します。`app`、`web`、`db`は最終的にhealthyになることが正常です。

### 4.2 ヘルスチェック

ブラウザで <http://localhost:8080/health> を開き、次の形式が返ることを確認します。

```json
{
  "status": "ok",
  "app": "給与明細ポータル",
  "database": "ok"
}
```

### 4.3 ログインとメール

- ログイン: <http://localhost:8080/login>
- 開発メール: <http://localhost:8025>

全権限が同じログイン画面を使います。ログイン後、`system_admin`、`company_admin`、`employee`の役割に応じた画面へ遷移します。

## 5. 初期設定と基本運用

1. システム管理者が会社を登録する。
2. システム管理者が会社管理者を登録する。
3. システム管理者が共通の給与明細テンプレートを登録する。
4. 会社管理者が部署と社員を登録する。
5. 会社管理者が共通テンプレートを自社用設定へコピーし、項目を調整する。
6. 会社管理者が通知メール文面を設定する。
7. 会社管理者が給与処理を作成し、画面から見本CSVを取得する。
8. 給与CSVを取り込み、内容を確認する。
9. 公開日時を指定して承認する。
10. Schedulerが公開時刻に明細を公開し、社員へログインURLを通知する。
11. 社員が明細を閲覧またはPDF出力する。
12. 会社管理者が未閲覧・閲覧日時・閲覧回数を確認する。

## 6. 運用・保守コマンド

```powershell
# 全自動テスト
docker compose exec app php artisan test

# 予約公開を手動で1回実行
docker compose exec app php artisan payslips:publish-scheduled

# ルート一覧
docker compose exec app php artisan route:list

# アプリとSchedulerのログを継続表示
docker compose logs -f app scheduler

# 全サービスの直近200行
docker compose logs --tail 200

# DBマイグレーション状態
docker compose exec app php artisan migrate:status

# 依存パッケージの既知脆弱性
docker compose exec app composer audit
```

ログの継続表示は `Ctrl+C` で終了できます。コンテナは停止しません。

## 7. 障害対応

### 7.1 Web画面を開けない

1. Docker Desktopが起動しているか確認する。
2. `docker compose ps`で`web`と`app`を確認する。
3. `docker compose logs --tail 200 web app`を確認する。
4. 停止していれば`docker compose up -d`を実行する。
5. 8080番ポート競合が表示された場合、既に8080番を使うアプリを停止するか、`docker-compose.yml`の公開ポートを変更する。

### 7.2 `/health` が503または `database: error`

1. `docker compose ps`で`db`がhealthyか確認する。
2. `docker compose logs --tail 200 db app`を確認する。
3. 起動直後ならMariaDBの初期化完了まで待ち、再確認する。
4. `docker compose restart db app web`で再起動する。
5. DBボリュームを削除しない。復旧前に`down -v`を実行しない。

### 7.3 ログインできない

- メールアドレスとパスワードを確認する。
- アカウントまたは所属会社が利用停止になっていないか、上位管理者が確認する。
- 1分間に6回を超えて試行した場合は、1分以上待って再試行する。
- 保存済みパスワードはハッシュ化されているため表示できない。必要に応じて上位管理者が新しいパスワードを設定する。

### 7.4 CSV取込に失敗する

- 画面から最新の見本CSVをダウンロードして使用する。
- ファイルサイズが5MB以下であることを確認する。
- 社員CSVでは`employee_number,name,email,department_code`を必須列とする。新規社員は`password`も必要。
- 給与CSVでは`employee_number`と、有効な明細項目コードをすべて含める。
- 社員番号・部署コードがログイン中の会社に存在するか確認する。
- 金額・数値項目に文字が混ざっていないか確認する。
- UTF-8またはShift_JISを使用する。

### 7.5 公開時刻になっても公開されない

1. 給与処理が「承認済み・公開待ち」か確認する。
2. 公開日時が現在時刻以前か確認する。
3. `docker compose ps`で`scheduler`が起動中か確認する。
4. `docker compose logs --tail 200 scheduler`を確認する。
5. 手動確認として`docker compose exec app php artisan payslips:publish-scheduled`を実行する。

### 7.6 メールがMailpitに届かない

- 給与処理が公開済みか確認する。
- 社員のメールアドレスを確認する。
- <http://localhost:8025> を再読み込みする。
- `docker compose logs --tail 200 app scheduler mailpit`を確認する。
- 明細の`notified_at`が空の場合は送信処理失敗の可能性がある。公開処理は明細を公開した後、社員ごとに送信を試みるため、送信失敗でも公開状態は戻らない。

### 7.7 PDFが開けない・日本語が文字化けする

- `app`コンテナが最新Dockerfileで再構築されているか確認する。
- `docker compose up -d --build`を実行する。
- `storage/app/mpdf`へ書込み可能かログで確認する。
- DockerfileのIPA/Noto CJKフォント導入に失敗していないかビルドログを確認する。

## 8. データ保全と本番利用上の注意

- 現在のDB保存先はDocker volume `mariadb-data`です。
- 本書には本番用バックアップ・復元手順は含みません。学習環境のため、必要なら別途設計します。
- Laravel 11はセキュリティサポート終了済みです。実データ・本番環境には使用しないでください。
- 本番化には、サポート中Laravel、HTTPS、秘密情報管理、外部メール、監査ログ、バックアップ、パスワード再設定、多要素認証、負荷試験、脆弱性診断が必要です。
