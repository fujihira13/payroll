# コード学習ガイド

## 最初に読む順番

1. `routes/web.php` — 画面URLと、誰が入れるかを確認する
2. `app/Enums/UserPermission.php` — 一般社員1、社員管理者9のパーミッションを確認する
3. `app/Models/Admin.php`と`app/Models/User.php` — `/login/manage`と`/login`のアカウント分離を確認する
4. `app/Http/Middleware/EnsureCompanyManager.php` — permission 9以外を管理機能から拒否する仕組みを読む
5. `database/migrations` — どのデータを保存しているかを読む
6. 各Controller — 入力を検証し、Modelを操作し、Viewを返す流れを読む
7. `resources/views` — Bladeが受け取ったデータをHTMLにする部分を読む
8. `app/Services` — CSV取込と予約公開という少し複雑な処理を読む
9. `tests/Feature` — システムに期待する動作を具体例で読む

## Laravelの1リクエスト

例えば社員が明細を開くと、次の順で動きます。

```text
ブラウザ
  → routes/web.php がURLを照合
  → auth と password.changed ミドルウェアが本人・初回変更状態を確認
  → Employee/PayslipController が自分の公開済み明細か確認
  → Payslip ModelがMariaDBから取得・閲覧日時を更新
  → resources/views/employee/payslips/show.blade.php がHTMLを生成
  → ブラウザに返す
```

BladeはLaravel標準のHTMLテンプレートです。`.blade.php`の中では通常のHTMLに加えて、`{{ $value }}`で安全に文字を表示し、`@if`や`@foreach`で条件・繰り返しを書けます。将来VueやReactへ変更するときも、Controllerの業務処理をAPI側へ寄せれば、Model・DB・Serviceの多くをそのまま利用できます。

## 認証・パーミッションと会社分離

- システム管理者は`admins`へ保存され、`/login/manage`からログインして会社・システム管理者・共通テンプレートを扱います。
- `permission=9`の社員管理者は`/login`からログインし、自社の管理機能を使用します。検索にはログイン中利用者の`company_id`を付けます。
- `permission=1`の一般社員も`/login`からログインし、明細の`employee_id`が自分自身であることを確認します。

URLを隠すだけでは安全になりません。そのため、このアプリはルートの`auth:admin`／`company_manager`ミドルウェアとController内の所有者確認を併用します。

## テンプレートと会社設定を分けた理由

システム管理者のテンプレートは「原本」です。社員管理者が選ぶと、項目を会社用設定へコピーします。コピー後は、会社側が項目名や並びを変えても原本や他社に影響しません。給与明細を作った時点でも項目をJSONとして保存するため、将来テンプレートが変わっても過去明細は変化しません。

## 承認・予約公開・メール

給与処理には `draft`、`scheduled`、`published` の状態があります。

- `draft`: CSVを取り込み、社員管理者が内容を確認中
- `scheduled`: 承認済みで、公開日時を待っている
- `published`: 社員が閲覧できる

Dockerの `scheduler` コンテナが `php artisan schedule:work` を常時実行します。`routes/console.php`の設定により、毎分 `payroll:publish-scheduled` を呼びます。対象があれば `PublishScheduledPayroll` がトランザクション内で公開し、各社員へメールを送ります。メールは給与額やPDFを添付せず、ログインURLだけを通知します。

## 閲覧状況

社員が詳細画面またはPDFを開くたび、`payslips` の次の値を更新します。

- `first_viewed_at`: 最初に見た日時。一度だけ記録
- `last_viewed_at`: 最後に見た日時
- `view_count`: 見た回数

社員管理者は給与処理の詳細でこれを確認できます。

## PDF

`Employee/PayslipController::pdf` がBladeをHTMLに変換し、mPDFへ渡します。DockerイメージにはIPA/Noto CJKフォントがあり、日本語の文字化けを防ぎます。通常画面とPDFでViewを分けているので、印刷向けのレイアウトだけを変更できます。

## テストの見方

- `RoleAndTenantAccessTest`: 権限と他社データの拒否
- `PayslipWorkflowTest`: 給与CSV、予約公開、メール、閲覧記録、PDF
- `ExampleTest`: ヘルスチェックと未ログイン時の動作

`arrange（データ準備）→ act（操作）→ assert（結果確認）` の順に読むと理解しやすくなります。

## 本番化する前に必要なこと

この実装は学習用の土台です。実運用前には少なくとも、サポート中Laravelへの更新、HTTPS、秘密情報管理、キューWorker、メール配信サービス、監査ログ、バックアップ、パスワード再設定、多要素認証、CSVウイルス対策、負荷試験、脆弱性診断が必要です。
