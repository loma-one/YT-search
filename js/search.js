document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('search-form');
    const searchResults = document.getElementById('search-results');
    const mainElement = document.querySelector('main');
    let searchQuery = '';
    let nextPageToken = '';
    let isLoading = false;

    searchForm.addEventListener('submit', function(event) {
        event.preventDefault();
        searchQuery = document.getElementById('search_query').value;
        nextPageToken = '';
        searchResults.innerHTML = '';
        loadVideos(searchQuery, nextPageToken);
    });

    function loadVideos(query, pageToken) {
        if (isLoading) return;
        isLoading = true;

        const xhr = new XMLHttpRequest();
        xhr.open('GET', `search_videos.php?search_query=${query}&pageToken=${pageToken}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = xhr.responseText;
                searchResults.insertAdjacentHTML('beforeend', response);
                const nextPageTokenElement = document.getElementById('next-page-token');
                if (nextPageTokenElement) {
                    nextPageToken = nextPageTokenElement.getAttribute('data-token');
                    nextPageTokenElement.remove();
                } else {
                    nextPageToken = '';
                }
                isLoading = false;
            }
        };
        xhr.send();
    }

    mainElement.addEventListener('scroll', function() {
        if (mainElement.scrollTop + mainElement.clientHeight >= mainElement.scrollHeight - 10 && nextPageToken) {
            loadVideos(searchQuery, nextPageToken);
        }
    });
});
