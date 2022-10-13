function initSessionReminder() {
    const reminder = document.getElementById('session-reminder')
    if (!reminder) {
        return
    }

    const refreshLink = reminder.querySelector('a')

    let warningDate = new Date(reminder.dataset.warning)
    let expiryDate = new Date(reminder.dataset.expiry)

    let isVisible = false
    let isUpdating = false
    let hasExpired = false

    refreshLink.addEventListener('click', function(e) {
        e.preventDefault()
        isUpdating = true
        isVisible = false
        reminder.classList.remove('visible')

        const req = new XMLHttpRequest()
        req.addEventListener('load', function() {
            const data = JSON.parse(req.response)
            warningDate = new Date(data.warning)
            expiryDate = new Date(data.expiry)
            isUpdating = false
        })
        req.open("GET", refreshLink.getAttribute('href'))
        req.send()
    })

    function checkDate() {
        if (hasExpired) {
            return
        }

        const currentDate = new Date();

        if (currentDate > expiryDate) {
            hasExpired = true
            reminder.classList.add('visible')
            reminder.classList.add('expired')
            return
        }

        if (isVisible || isUpdating) {
            return
        }

        if (currentDate > warningDate) {
            isVisible = true;
            reminder.classList.add('visible')
        }
    }

    setInterval(checkDate, 1000)
}

export default initSessionReminder;