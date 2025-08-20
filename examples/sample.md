# Project Title

Welcome to the **Markdown Parser** project! \ This parser converts Markdown files into HTML. This document is designed to test various Markdown features supported by the parser.

---

## Table of Contents

1. [Features](#features)
2. [Installation](#installation)
3. [Usage](#usage)
4. [Examples](#examples)
5. [License](#license)

---

## Features

- **Headers** from `#` to `######`
- *Italic*, **bold**, and `inline code`
- [Links](https://www.example.com) and images
- Lists:
  - Unordered lists
  - Ordered lists
  - Nested lists
- Blockquotes
- Code blocks
- Horizontal rules

Here is a link to [OpenAI](https://www.openai.com).
Check this site too: http://www.example.com
But do not link this: `http://www.example.com`

Gone camping! :tent: Be back soon.

That is so funny! :joy:

---

## Installation

To install this project, clone the repository and run the setup script:

```bash
git clone https://github.com/example/markdown-parser.git
cd markdown-parser
npm install
```

## Usage

Simply run the following command to parse your Markdown file:

```bash
node parse.js README.md
```

This will convert the Markdown into HTML and output it in the `dist` folder.

---

## Examples

### Headers

# Header 1
## Header 2
### Header 3
#### Header 4
##### Header 5
###### Header 6

### Emphasis

This text is **bold** and this text is *italic*. You can also combine them for ***bold and italic***.

Inline code looks like this: `console.log("Hello, world!");`

This is some text with footnotes[^1][^2].

[^1]: This is footnote 1.
[^2]: This is footnote 2.



### Blockquotes

> This is a blockquote.
> 
> - It can contain lists,
> - **Bold text**, and
> - *Italic text*

> Nested blockquote:
> > Another level of quote.

### Lists

#### Unordered List

- Item 1
  - Subitem 1.1
  - Subitem 1.2
- Item 2

#### Ordered List

1. First item
2. Second item
   1. Subitem 2.1
   2. Subitem 2.2
3. Third item

### Images

![OpenAI Logo](https://openai.com/favicon.ico)

### Links

Visit the [OpenAI website](https://www.openai.com) for more information.

---

### Horizontal Rule

---

### Code Blocks

```javascript
function greet() {
    console.log("Hello, world!");
}
greet();
```

```python
def greet():
    print("Hello, world!")

greet()
```

## License

```
This project is licensed under the MIT License. See the LICENSE file for more information.
```

---

### Explanation of the Content

This Markdown file tests the following features:

1. **Headers**: Six levels of headers.
2. **Emphasis**: Bold, italic, and combined bold+italic text.
3. **Inline Code**: Simple inline code in text.
4. **Blockquotes**: Single and nested blockquotes with lists and formatted text inside.
5. **Lists**: Unordered lists with subitems and ordered lists with nested subitems.
6. **Images**: A sample image with alt text.
7. **Links**: Hyperlinks to external URLs.
8. **Horizontal Rules**: Divider lines with `---`.
9. **Code Blocks**: Multiple fenced code blocks with language annotations (JavaScript and Python).
10. **Persian Rules**: Divider lines with ...



| Column 1      | Column 2      |
| ------------- | ------------- |
| Cell 1, Row 1 | Cell 2, Row 1 |
| Cell 1, Row 2 | Cell 1, Row 2 |


This is ==highlighted text== in a sentence.
~~The world is flat.~~ We now know that the world is round.  H~2~O  X^2^



...شعر

روزها اندیشه‌ام این است و همه شب گفته‌ام -- که چرا غافل از احوال دل خویشتنم
    ز کجا آمده‌ام آمدنم بهر چه بود -- ز کجا میروم آخر ننمایی وطنم

مانده‌ام سخت شگفت کز چه سبب ساخت مرا
یا چه بود است مراد وی از این ساختنم
...


### شمارش فارسی

۱. سرزمین
۲. کشور
۳. سرباز


- [ ] کاری که باید بکنم
- [x] کاری که انجام دادم
