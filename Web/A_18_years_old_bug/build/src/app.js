const cookieParser = require("cookie-parser");
const express = require("express");
const goto = require("./bot");
const app  = express();
app.use(cookieParser());
app.set("view engine", "ejs");
app.use("/static", express.static("static"));

app.get("/", (req, res) => {
    var url = req.query.url || req.cookies.url || "/sandbox?html=Hello World!";
    res.cookie("url", url);
    res.set("Cache-Control", "public, max-age=30");
    res.render("index", { url: url });
})

app.get("/sandbox", (_, res) => {
    res.set("X-Frame-Options", "sameorigin");
    res.render("sandbox");
})

app.get("/bot", async (req, res) => {
    res.set("Content-Type", "text/plain");
    if (req.query.url?.startsWith("http")) {
        await goto(req.query.url);
        res.send("URL checked by the bot!");
    } else {
        res.send("The ?url= parameter must starts with http!");
    }
})

const PORT = 3000;
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});
