import './scss/admin.scss'

if (window.location.hash) {
    let highlightHash = location.hash.slice(1)
    let highlightId = highlightHash.slice(highlightHash.search(/(?<=^|&)highlight=/)).split('&')[0].split('=')[1];

    let elem = document.getElementById(highlightId);

    if (elem) {
        elem.classList.add('highlight');
    }
}
