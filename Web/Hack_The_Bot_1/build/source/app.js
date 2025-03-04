const express = require('express');
const path = require('path');
const fs = require('fs');
const { spawn } = require('child_process');
const puppeteer = require('puppeteer');
const { format } = require('date-fns');

const app = express();
const port = 5000;

const logPath = '/tmp/bot_folder/logs/';
const browserCachePath = '/tmp/bot_folder/browser_cache/';

const cookie = {
    name: 'Flag',
    value: "PWNME{D1d_y0U_S4iD-F1lt33Rs?}",
    sameSite: 'Strict'
};

app.use(express.urlencoded({ extended: true }));

app.use(express.static(path.join(__dirname, 'public')));

app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'ejs');

if (!fs.existsSync(logPath)) {
    fs.mkdirSync(logPath, { recursive: true });
}

if (!fs.existsSync(browserCachePath)) {
    fs.mkdirSync(browserCachePath, { recursive: true });
}

const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

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

app.get('/', (req, res) => {
    res.render('index');
});

app.get('/report', (req, res) => {
    res.render('report');
});

app.post('/report', (req, res) => {
    const url = req.body.url;
    const name = format(new Date(), "yyMMdd_HHmmss");
    startBot(url, name);
    res.status(200).send(`logs/${name}.log`);
});

app.listen(port, () => {
    console.log(`App running at http://0.0.0.0:${port}`);
});
