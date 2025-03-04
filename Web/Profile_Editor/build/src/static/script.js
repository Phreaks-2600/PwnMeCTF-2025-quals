document.addEventListener("DOMContentLoaded", function () {
    var textarea = document.querySelector("textarea");
    var charCount = document.getElementById("char-count");

    textarea.addEventListener("input", function () {
        var count = textarea.value.length;
        charCount.textContent = count + " characters";
    });
});
