var Modal = function () {
    this.init();
    var self = this;
    this.hidder.addEventListener('click', function (e) {
        self.hide();
    }, false);

    document.body.addEventListener('keyup', function (e) {
        if (e.keyCode === 27) {
            self.hide();
        }
    });

    this.onClose = function () {
        // do nothing ♥
    };
};

Modal.prototype.init = function () {
    this.modal = document.createElement('div');
    this.modal.className = 'modal';
    this.hidder = document.createElement('div');
    this.hidder.className = 'modal_underlay';

    document.body.appendChild(this.modal);
    document.body.appendChild(this.hidder);
};

Modal.prototype.setContent = function (content, callback) {
    this.modal.innerHTML = content;
    if (callback !== undefined)
        callback();
};

Modal.prototype.setContentDOM = function (content, callback) {
    this.modal.innerHTML = '';

    for (var i = 0; i < content.length; i++) {
        this.modal.appendChild(content[i]);
    }

    if (callback !== undefined)
        callback();
};

Modal.prototype.show = function () {
    this.modal.style.top = "12.5%";
    this.hidder.style.display = "block";
};

Modal.prototype.hide = function () {
    this.modal.style.top = "-100%";
    this.hidder.style.display = "none";
    this.onClose();
};