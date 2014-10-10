var Notificater = function () {
    "use strict";

    var self = this;
    var notification_area = document.createElement('div');

    this.show = function () {
        notification_area.style.left = '20px';
    };

    this.hide = function () {
        notification_area.style.left = '-340px';
    };

    this.notify = function (error) {
        self.show();

        setTimeout(this.hide, 3000);

        if (notification_area.innerText) {
            notification_area.innerText = error.htmlsanitise();
        } else {
            notification_area.textContent = error.htmlsanitise();
        }
    };

    notification_area.className = 'notification';
    notification_area.addEventListener('click', function () {
        self.hide();
    }, false);

    document.body.appendChild(notification_area);
};