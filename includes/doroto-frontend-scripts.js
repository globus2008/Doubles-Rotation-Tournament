document.addEventListener('DOMContentLoaded', function () {
    var refreshContainer = document.getElementById('doroto-refresh-container');
    if (refreshContainer) {
        var seconds = parseInt(refreshContainer.getAttribute('data-seconds'), 10);

        if (!isNaN(seconds) && seconds > 0) {
            setTimeout(function () {
                location.reload();
            }, seconds * 1000);
        }
    }
});

jQuery(document).ready(function($) {
    // When you click on the title
    $(".doroto-clickable-title").click(function() {
        // Get container id by click
        var containerId = $(this).next(".doroto-content-container").attr("id");

        // Hide all containers except the selected one
        $(".doroto-content-container").not("#" + containerId).hide();

        // Display of the selected container
        $("#" + containerId).slideToggle();

        // Saving state to sessionStorage
        sessionStorage.setItem('openedPanel', containerId);
    });

    // Checking sessionStorage for saved state
    var openedPanel = sessionStorage.getItem('openedPanel');

    // If any panel was previously open, display it
    if (openedPanel) {
        $("#" + openedPanel).show();
    }
});