(function() {
    // 15 minutes in milliseconds
    const IDLE_TIMEOUT_MS = 15 * 60 * 1000;
    // Ping server every 5 minutes if there is activity
    const PING_INTERVAL_MS = 5 * 60 * 1000; 

    let lastActivityTime = Date.now();
    let lastPingTime = Date.now();

    // Dynamically calculate base path by going up to the root folder (cswd_butuan)
    // We know the current URL is something like /cswd_project/cswd/cswd_butuan/...
    // Let's use a reliable path.
    const basePath = '/cswd_project/cswd/cswd_butuan';

    function resetIdleTimer() {
        lastActivityTime = Date.now();

        // If it's been more than PING_INTERVAL_MS since we last pinged the server, ping now
        if (Date.now() - lastPingTime > PING_INTERVAL_MS) {
            pingServer();
        }
    }

    function checkIdleTime() {
        if (Date.now() - lastActivityTime > IDLE_TIMEOUT_MS) {
            // Idle timeout reached, log out
            window.location.href = basePath + '/login form/index.php?error=session_expired';
        }
    }

    function pingServer() {
        lastPingTime = Date.now();
        fetch(basePath + '/auth/ping_session.php')
            .catch(err => console.error('Error pinging session:', err));
    }

    // Listen to various activity events
    window.addEventListener('mousemove', resetIdleTimer, { passive: true });
    window.addEventListener('keydown', resetIdleTimer, { passive: true });
    window.addEventListener('mousedown', resetIdleTimer, { passive: true });
    window.addEventListener('touchstart', resetIdleTimer, { passive: true });
    window.addEventListener('scroll', resetIdleTimer, { passive: true });

    // Check idle status every 10 seconds
    setInterval(checkIdleTime, 10000);
})();

