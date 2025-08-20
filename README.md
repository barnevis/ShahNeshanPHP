
<a id="نسخه-فارسی"></a>
# شه‌نشان PHP (ShahNeshan PHP)

تبدیل Markdown به HTML در سمت سرور (PHP) با پشتیبانی از ویژگی‌های فارسی، جدول‌ها، فهرست کارها، پانوشت‌ها، افزونه‌ها و استایل‌های سفارشی.

[Read the English version](#ShahNeshanPHP)

---

## نصب

### با Composer (پیشنهادی)

> نیازمندی‌ها: `PHP >= 8.0` و افزونه `mbstring`

اگر بسته روی Packagist نیست، به صورت VCS یا path اضافه کنید:

**به‌صورت مخزن Git:**
```json
// composer.json پروژه مصرف‌کننده
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/YourUser/ShahNeshanPHP" }
  ],
  "require": {
    "shahrooz/shahneshan-php": "dev-main"
  }
}
```

سپس:

```bash
composer update
```

**به‌صورت مسیر محلی:**

```json
{
  "repositories": [
    { "type": "path", "url": "../ShahNeshanPHP", "options": { "symlink": true } }
  ],
  "require": {
    "shahrooz/shahneshan-php": "*"
  }
}
```

### نصب دستی (بدون Composer)

پوشه‌ی این کتابخانه را در پروژه کپی و فایل‌های اصلی را `require` کنید.

---

## استفادهٔ سریع

```php
<?php
require __DIR__.'/vendor/autoload.php';

require_once __DIR__.'/ShahNeshanPHP.php';   // اگر Composer ندارید، فایل‌ها را به‌صورت دستی include کنید

$config = [
  'customStyles' => 'h1{color:blue} mark{background:yellow;}'
];

$engine = new ShahNeshanPHP($config);
$html   = $engine->renderFile(__DIR__.'/README.md');   // یا $engine->renderMarkdown("# سلام دنیا");

echo "<!doctype html><meta charset='utf-8'>
      <link rel='stylesheet' href='statics/style.css'>
      {$html}";
```

> فایل‌های CSS نمونه در `statics/style.css` قرار دارند. می‌توانید استایل‌های خودتان را جایگزین کنید.

---

## ویژگی‌ها

* تیترها، نقل‌قول‌ها، لیست‌های مرتب/نامرتب و چندسطحی
* کد بلاک سه‌تایی \`\`\` با کلاس زبان
* لینک/عکس و لینک‌سازی خودکار URLها (خارج از backtick)
* متن تأکیدی: **bold**، *italic*، `inline code`، ~~strikethrough~~، ==highlight==، H~~2~~O، X^2^
* چک‌لیست‌ها: `- [ ]` و `- [x]`
* پانوشت‌ها: ارجاع `[^1]` و تعریف `[^1]: ...`
* جدول‌ها با ردیف تنظیم چینش ستون‌ها: `| :--- | ---: | :---: |`
* پشتیبانی از جهت راست‌به‌چپ برای فهرست‌ها و موارد فارسی
* بلوک‌های فارسی:

  * `...شعر` (poet) با جداساز `--`
  * `...توجه`، `...نکته`، `...مهم`، `...هشدار`، `...احتیاط` (Alert)

---

## پیکربندی

هنگام ساخت شیء:

```php
$engine = new ShahNeshanPHP([
  'customStyles' => '
    h1 { color: blue; }
    mark { background-color: yellow; }
  '
]);
```

---

## سیستم افزونه‌ها

هر افزونه باید رابط زیر را پیاده‌سازی کند:

```php
interface PluginInterface {
    public function beforeParse($markdown); // string -> string
    public function transformNode($node);   // Node   -> Node
    public function afterRender($html);     // string -> string
}
```

### مثال افزونه (تعویض ایموجی)

```php
class MyEmoji implements PluginInterface {
  public function beforeParse($markdown) {
    return str_replace(':khande:', '😊', $markdown);
  }
  public function transformNode($node) { return $node; }
  public function afterRender($html) { return $html; }
}
```

### نحوهٔ ثبت افزونه

دو روش:

1. ساده (ویرایش `ShahNeshanPHP.php` و افزودن `addPlugin(new MyEmoji())` بعد از EmojiPlugin پیش‌فرض)، یا
2. ساخت دستی اجزا:

```php
$pm = new PluginManager();
$pm->addPlugin(new EmojiPlugin());
$pm->addPlugin(new MyEmoji());

$parser   = new MarkdownParser(['customStyles' => '...'], $pm);
$renderer = new HtmlRenderer($pm);

$nodes = $parser->parseMarkdown($markdown);
$html  = $renderer->nodesToHtml($nodes);
```

---

## مثال‌های نشانه‌گذاری فارسی

### شعر

```
...شعر
خرد رهنمای و خرد دلگشای -- خرد دست گیرد به هر دو سرای
ازو شادمانی وزویت غمیست -- وزویت فزونی وزویت کمیست
...
```

### بلوک‌های هشدار

```
...توجه
نکات مهمی که کاربر باید بداند.
...
```

### جدول با چینش

```
| ستون اول | ستون دوم |
| :------: | -------: |
| وسط      | راست     |
```

---

## مجوز

این پروژه تحت مجوز Apache-2.0 منتشر شده است. متن کامل مجوز در فایل [LICENSE](LICENSE) موجود است.

</br>
</br>
</br>

<a id="ShahNeshanPHP"></a>

# ShahNeshan PHP

Server-side Markdown → HTML in PHP with Persian extensions, tables, task lists, footnotes, plugins, and custom styles.

[مطالعه نسخه فارسی](#نسخه-فارسی)

---

## Install

### Via Composer (recommended)

> Requirements: `PHP >= 8.0`, `ext-mbstring`

If not on Packagist yet, add as VCS or path repository.

**VCS (Git):**

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/YourUser/ShahNeshanPHP" }
  ],
  "require": {
    "shahrooz/shahneshan-php": "dev-main"
  }
}
```

Then:

```bash
composer update
```

**Local path:**

```json
{
  "repositories": [
    { "type": "path", "url": "../ShahNeshanPHP", "options": { "symlink": true } }
  ],
  "require": {
    "shahrooz/shahneshan-php": "*"
  }
}
```

### Manual (no Composer)

Copy this library into your project and `require` the files.

---

## Quick Start

```php
<?php
require __DIR__.'/vendor/autoload.php';
// or manual requires if not using Composer

require_once __DIR__.'/ShahNeshanPHP.php';

$engine = new ShahNeshanPHP([
  'customStyles' => 'h1{color:blue} mark{background:yellow;}'
]);

$html = $engine->renderFile(__DIR__.'/README.md'); // or ->renderMarkdown("# Hello")

echo "<!doctype html><meta charset='utf-8'>
      <link rel='stylesheet' href='statics/style.css'>
      {$html}";
```

---

## Features

* ATX headers, blockquotes, ordered/unordered (nested) lists
* Fenced code blocks \`\`\` with language class
* Links/images + auto-linking bare URLs (outside backticks)
* Emphasis: **bold**, *italic*, `inline`, ~~strike~~, ==highlight==, H~~2~~O, X^2^
* Task lists: `- [ ]` / `- [x]`
* Footnotes: refs `[^1]` & defs `[^1]: ...`
* Tables with alignment row: `| :--- | ---: | :---: |`
* RTL detection for Persian/Arabic lists
* Persian blocks:

  * `...شعر` (poetry) with `--` separator
  * Alert blocks: `...توجه`, `...نکته`, `...مهم`, `...هشدار`, `...احتیاط`

---

## Configuration

```php
$engine = new ShahNeshanPHP([
  'customStyles' => '
    h1 { color: blue; }
    mark { background-color: yellow; }
  '
]);
```

---

## Plugin System

A plugin implements:

```php
interface PluginInterface {
    public function beforeParse($markdown); // string -> string
    public function transformNode($node);   // Node   -> Node
    public function afterRender($html);     // string -> string
}
```

### Example plugin

```php
class MyEmoji implements PluginInterface {
  public function beforeParse($markdown){ return str_replace(':khande:', '😊', $markdown); }
  public function transformNode($node){ return $node; }
  public function afterRender($html){ return $html; }
}
```

### Registering plugins

Either edit `ShahNeshanPHP` to add your plugin, or wire components manually:

```php
$pm = new PluginManager();
$pm->addPlugin(new EmojiPlugin());
$pm->addPlugin(new MyEmoji());

$parser   = new MarkdownParser(['customStyles' => '...'], $pm);
$renderer = new HtmlRenderer($pm);

$html = $renderer->nodesToHtml($parser->parseMarkdown($markdown));
```

---

## Styling

Include the sample CSS files or your own:

```html
<link rel="stylesheet" href="/path/to/statics/style.css">
```

Alert blocks use classes: `.alert.note`, `.alert.tip`, `.alert.important`, `.alert.warning`, `.alert.caution` and poetry uses `.persian.poet` + `.stanza`.

---

## License

Released under the [Apache-2.0](LICENSE).

