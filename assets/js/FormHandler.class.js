var FormHandler = function (form) {
    if (form.tagName !== "FORM")
        throw new TypeError("the form must be of type FORM");

    var self = this;

    this.form = form;
    this.disabled = false;
    this.onSuccess = null;
    this.onSubmit = null;
    this.onFail = null;
    this.onEnd = null;
    this.displayer = null;

    this.reloadInputs();

    this.submit = function (e) {
        if (e)
            e.preventDefault();

        if (self.disabled)
            return false;

        if (typeof self.onSubmit === 'function') {
            if (self.onSubmit() === false)
                return false;
        }

        self.disabled = true;
        self.displayMessage('Chargement...');
        $.post(form.action, $(form).serialize(), function (data, textStatus) {
            self.disabled = false;

            try {
                if (textStatus == "success") {
                    var result = $.parseJSON(data);

                    if (result.error !== undefined && result.error !== false) {
                        if (self.displayer !== null) {
                            self.displayMessage(result.error);
                        } else {
                            console.log(result);
                        }
                    } else {
                        if (typeof(self.onSuccess) === "function")
                            self.onSuccess(result);
                    }
                } else {
                    throw exception("Communication error");
                }
            } catch (e) {
                console.log(textStatus, data, e.message);

                if (typeof(self.onFail) === "function")
                    self.onFail(result);
            }

            if (typeof(self.onEnd) === "function")
                self.onEnd(data);
        });
    };

    form.addEventListener('submit', this.submit, true);
};

FormHandler.prototype.displayMessage = function (message) {
    if (this.displayer === undefined)
        return;

    if (typeof this.displayer === 'function') {
        return this.displayer(message);
    }

    if (typeof message === 'array' || typeof message === 'object') {
        if (this.displayer.value === undefined)
            this.displayer.innerHTML = message.join('<br>');
        else
            this.displayer.value = message.join('\n');
    } else {
        if (this.displayer.value === undefined)
            this.displayer.innerHTML = message;
        else
            this.displayer.value = message;
    }
};

FormHandler.prototype.reloadInputs = function () {
    this.inputs = [];
    for (var i in this.form.children) {
        if (this.form.children[i].attributes === undefined || this.form.children[i].attributes.name === undefined || this.form.children[i].attributes.name === "")
            continue;

        this.inputs[this.form.children[i].name] = {help: null, validation: null, keyup: null, change: null};

        var self = this;
        this.form.children[i].addEventListener("focus", function () {
            if (self.displayer === null || typeof self.inputs[this.name].help !== 'string')
                return;

            self.displayMessage(self.inputs[this.name].help);
        }, false);

        this.form.children[i].addEventListener("change", function (e) {
            if (typeof self.inputs[this.name].change === 'function') {
                self.inputs[this.name].change(this.value, e);
            }
        }, false);

        this.form.children[i].addEventListener("keyup", function (e) {
            if (typeof self.inputs[this.name].validation === 'function') {
                if (self.inputs[this.name].validation(this.value)) {
                    $(this).removeClass('wrong');
                } else {
                    $(this).addClass('wrong');
                }
            }

            if (typeof self.inputs[this.name].keyup === 'function') {
                self.inputs[this.name].keyup(this.value, e);
            }
        }, false);
    }
};