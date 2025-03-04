$(document).ready(function() {
    $('#reportForm').on('submit', function(e) {
        e.preventDefault();
        var url = $('#urlInput').val();
        var messageElement = $('#message');

        $.ajax({
            type: 'POST',
            url: '/report',
            data: { url: url },
            success: function(response) {
                var messageHtml = `In progress, you can check logs <a href="${response}"style="color: green; text-decoration: underline;">here</a>`;
                messageElement.html(messageHtml).removeClass('error').addClass('success');
            },
            error: function(xhr) {
                messageElement.text('Erreur: ' + xhr.responseText).removeClass('success').addClass('error');
            }
        });
    });
});

// Implements search functionality, filtering articles to display only those matching the search words (considering whole words case-insensitive matches)

function getSearchQuery() {
    const params = new URLSearchParams(window.location.search);
    // Utiliser une valeur par défaut de chaîne vide si le paramètre n'existe pas
    return params.get('q') ? params.get('q').toLowerCase() : '';
}

document.addEventListener('DOMContentLoaded', function() {
    const searchQuery = getSearchQuery();
    document.getElementById('search-input').value = searchQuery; 
    if (searchQuery) {
        searchArticles(searchQuery);
    }
});

document.getElementById('search-icon').addEventListener('click', function() {
    searchArticles();
});

document.getElementById('search-input').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        searchArticles();
    }
});

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
