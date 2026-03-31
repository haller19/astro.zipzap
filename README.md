# ZiPZAP Portfolio — Astro + Vercel

ZiPZAP のポートフォリオサイトです。

## 技術スタック

- **フレームワーク**: [Astro](https://astro.build/) v4
- **デプロイ**: [Vercel](https://vercel.com/) (Static Output)
- **フォント**: DM Sans + DM Mono (Google Fonts)

## ページ構成

| ページ | パス | 内容 |
|--------|------|------|
| Home | `/` | Hero・実績ピックアップ・スキル概要・CTA |
| About | `/about` | プロフィール・経歴タイムライン |
| Skills | `/skills` | スキルセット・Tech Stack・資格 |
| Works | `/works` | 制作実績一覧（全6件） |
| Contact | `/contact` | お問い合わせフォーム |

## セットアップ

```bash
# 依存関係をインストール
npm install

# 開発サーバーを起動 (http://localhost:4321)
npm run dev

# 本番ビルド
npm run build

# ビルド結果をローカルでプレビュー
npm run preview
```

## Vercel へのデプロイ

### 方法 1: GitHub 連携（推奨）

1. このプロジェクトを GitHub にプッシュ
2. [vercel.com](https://vercel.com/) でアカウント作成・ログイン
3. 「New Project」→ GitHub リポジトリを選択
4. Framework Preset: `Astro` を選択
5. 「Deploy」ボタンを押すだけ 🎉

### 方法 2: Vercel CLI

```bash
npm i -g vercel
vercel login
vercel --prod
```

## お問い合わせフォームの実装

現在はデモ表示のみです。実際の送信機能は以下の方法で実装できます：

### オプション A: Vercel の Email 機能

```
vercel env add CONTACT_EMAIL
```

### オプション B: Resend (メール送信API)

```bash
npm install resend
```

`src/pages/api/contact.ts` を作成して API ルートを追加。

### オプション C: Formspree

フォームの `action` に Formspree のエンドポイントを設定するだけ。

## カスタマイズ

### 実績データの編集

`src/data/works.js` を編集してください。

### カラーの変更

`src/styles/global.css` の `:root` セクションにある CSS 変数を変更してください。

```css
:root {
  --c-teal: #2BBFA0;   /* メインカラー */
  --c-accent: #F5A623; /* アクセントカラー */
}
```

### フォントの変更

`src/layouts/Layout.astro` の Google Fonts リンクと `src/styles/global.css` の `--font-sans` を変更してください。
