const puppeteer = require("puppeteer");
const sleep = ms => new Promise(r => setTimeout(r, ms));

const goto = async (url) => {
    const browser = await puppeteer.launch({
        product: "firefox",
        executablePath: "/usr/bin/firefox"
    });
    const page = await browser.newPage();
    page.setDefaultNavigationTimeout(5 * 1000);

    await page.goto("http://127.0.0.1:3000");
    await page.evaluate((flag) => {
        localStorage.setItem("flag", flag);
    }, process.env.FLAG || "PWNME{FAKE_FLAG}");

    try {
        await page.goto(url);
        await sleep(3 * 1000);
        await page.evaluate(() => {
            location.reload();
        })
        await sleep(1 * 1000);
    } catch(error) {
        console.error(`Error navigating to URL: ${error}`);
    }
    await browser.close();
}

module.exports = goto;
