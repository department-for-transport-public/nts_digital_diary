// When clicking the day links on the household compare link page, keep the
// currently-selected tab selected after the page loads
function initHouseholdCompareSync() {
    let dayLinksContainer = document.getElementById('day-links');
    let diaryKeeperTabsContainer = document.getElementById('diary-keepers');

    if (!dayLinksContainer || !diaryKeeperTabsContainer) {
        return;
    }

    dayLinksContainer.addEventListener('click', function(e) {
        if(e.target.tagName.toUpperCase() !== 'A') {
            return;
        }

        e.preventDefault();

        // Calculate new target from clicked link's href + the current hash (e.g. #tab-diary-keeper-2)
        window.location = e.target.href + (window.location.hash ?? '');
    });
}

export {
    initHouseholdCompareSync
};
