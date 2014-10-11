var SkinManager = function (container, trigger, user_id, isOwn) {
    this.user_id = user_id;
    this.trigger = trigger;
    this.skinView = new Modal();
    this.globalContainer = container;
    this.globalContainer.innerHTML = '<input type="text" placeholder="search"/><div></div>' + this.globalContainer.innerHTML;
    this.skinContainer = this.globalContainer.children[1];
    this.searcher = this.globalContainer.children[0];
    this.skinList = {};
    this.selectedSkins = [];
    this.currentSkin = undefined;
    this.isOwn = (isOwn === undefined) ? false : isOwn;
    this.switchMode = SkinManager.SWITCHMODE.MCSKINSAPI;
    this.display_3D = true;

    this.registerListeners();
    this.init();
};

SkinManager.prototype.registerListeners = function () {
    var self = this;

    this.skinView.onClose = function () {
    };

    if (self.isOwn) {
        document.body.addEventListener('keyup', function (e) {
            if (e.keyCode === 46) {
                var skinList = self.deleteSelected();
                if (skinList) {
                    $.post("json/", {method: 'deleteSkins', skinList: skinList}, function () {
                    });
                }
            }
        });
    }

    this.searcher.addEventListener('keyup', function (e) {
        self.search();
    }, false);

    this.skinContainer.addEventListener('click', function (e) {
        if (e.altKey || !e.ctrlKey) {
            if (self.isOwn)
                self.unselectItem();
        }

        if (e.target.nodeName == "IMG") {
            if (self.isOwn)
                self.selectItem(e.target);

            if (!e.altKey && !e.ctrlKey) {
                self.loadSkin(e.target.attributes.getNamedItem("skin").value);
            }
        }
    }, false);

    if (this.trigger !== undefined) {
        this.trigger.addEventListener('click', function (e) {
            self.toggleSkinList();
            e.preventDefault();
        }, false);
    }
};

SkinManager.prototype.init = function () {
    var self = this;

    $.post("json/", {method: "loadSkins", user: this.user_id}, function (data) {
        var json;

        try {
            json = $.parseJSON(data).reverse();
        } catch (e) {
            json = [];
            notificater.notify("Failled to load skins");
            console.log(e, data);
        }

        for (var i in json) {
            self.addSkin(json[i]);
        }
    });
};

SkinManager.prototype.showSkinList = function () {
    this.globalContainer.style.left = "0px";
    document.getElementById("body").style.marginLeft = "215px";
};

SkinManager.prototype.hideSkinList = function () {
    this.globalContainer.style.left = "-215px";
    document.getElementById("body").style.marginLeft = "0px";
};

SkinManager.prototype.toggleSkinList = function () {
    if (this.globalContainer.style.left === "0px") {
        this.hideSkinList();
    } else {
        this.showSkinList();
    }
};

SkinManager.prototype.selectItem = function (item) {
    if (item === undefined || item.attributes.getNamedItem("skin").value === undefined)
        return;

    if (this.selectedSkins.indexOf(item.attributes.getNamedItem("skin").value) !== -1) {
        this.unselectItem(item);
        return;
    }

    this.selectedSkins.push(item.attributes.getNamedItem("skin").value);
    item.classList.add("selected");
};

SkinManager.prototype.unselectItem = function (item) {
    if (item === undefined) {
        for (var i in this.skinContainer.children) {
            if (this.skinContainer.children[i].classList !== undefined)
                this.skinContainer.children[i].classList.remove("selected");
        }
        this.selectedSkins = Array();
    } else {
        if (item.attributes.getNamedItem("skin").value === undefined)
            return;

        item.classList.remove("selected");
        this.selectedSkins.remove(item.attributes.getNamedItem("skin").value);
        // array_removeValue(item.attributes.getNamedItem("skin").value, this.selectedSkins);
    }
};

SkinManager.prototype.deleteSelected = function () {
    if (this.selectedSkins.length > 0 && !confirm('Are you sure you wish to delete these skins ?'))
        return false;

    this.skinView.hide();

    for (var i in this.selectedSkins) {
        this.removeSkin(this.selectedSkins[i]);
    }

    var returnVal = this.selectedSkins;
    this.selectedSkins = Array();

    return returnVal;
};

SkinManager.prototype.loadSkin = function (skinId) {
    if (this.skinList[skinId] === undefined)
        return;

    var self = this;

    this.currentSkin = this.skinList[skinId];

    var content = [];
    var title = document.createElement('h2');
    title.innerHTML = this.currentSkin.title;

    var canvas = document.createElement('canvas');
    canvas.id = 'skinview';

    var actionDiv = document.createElement('div');
    actionDiv.className = 'skin_actions';

    var skinDesc = document.createElement('p');
    skinDesc.innerHTML = this.currentSkin.description;
    actionDiv.appendChild(skinDesc);

    var buttonSwitch = document.createElement('a');
    var buttonDownload = document.createElement('a');

    buttonSwitch.className = buttonDownload.className = 'btn-blue';

    buttonSwitch.target = '_blank';
    if (self.switchMode === SkinManager.SWITCHMODE.SKINSWITCH) {
        buttonSwitch.href = '#';
        buttonSwitch.addEventListener('click', function (e) {
            e.preventDefault();

            $(buttonSwitch).addClass('disabled');
            var passphrase = prompt(_LANGUAGE['MINECRAFT_PASSWORD_KEY']);

            $.ajax({
                type: "POST",
                url: "json/",
                data: {
                    method: "switchSkin",
                    url: encodeURIComponent('http://minecraft.net/profile/skin/remote?url=http://skin.outadoc.fr/json/?method=getSkin&id=' + skinId),
                    passphrase: passphrase,
                    model: self.currentSkin.model
                },
                success: function (json) {
                    try {
                        json = $.parseJSON(json);
                    } catch (e) {
                        console.log(e, json);
                        json = {};
                    }

                    if (json.error !== false) {
                        notificater.notify(json.error[0]);
                    } else {
                        notificater.notify(_LANGUAGE['SKIN_SWITCHSUCCESS']);
                    }
                }
            }).always(function () {
                $(buttonSwitch).removeClass('disabled');
            });

            return false;
        }, true);
    } else
        buttonSwitch.href = 'http://minecraft.net/profile/skin/remote?url=http://skin.outadoc.fr/json/?method=getSkin&id=' + skinId;

    buttonSwitch.innerHTML = _LANGUAGE['SKIN_SWITCH'];
    actionDiv.appendChild(buttonSwitch);

    buttonDownload.href = 'json/?method=getSkin&id=' + skinId;
    buttonDownload.innerHTML = _LANGUAGE['SKIN_DOWNLOAD'];
    actionDiv.appendChild(buttonDownload);

    var buttonDuplicate = document.createElement('button');
    buttonDuplicate.innerHTML = _LANGUAGE['DUPLICATE'];
    buttonDuplicate.className = 'btn-white';
    buttonDuplicate.onclick = function (e) {
        e.preventDefault();
        $(buttonDuplicate).button('loading');
        $.ajax({
            type: "POST",
            url: "json/",
            data: {method: "duplicateSkin", sourceSkin_id: skinId},
            success: function (json) {
                json = $.parseJSON(json);
                if (json['id'] !== undefined) {
                    self.addSkin(json);
                    self.showSkinList();
                    notificater.notify(_LANGUAGE['SKIN_ADDED']);
                } else {
                    notificater.notify(_LANGUAGE['ERROR_UNKNOW']);
                }
            }
        }).always(function () {
            $(buttonDuplicate).button('reset');
        });
    };

    actionDiv.appendChild(buttonDuplicate);

    if (this.isOwn) {
        var buttonEdit = document.createElement('a');
        var buttonEditGraph = document.createElement('a');

        buttonEdit.href = 'editor/skin/' + skinId + '/';
        buttonEdit.innerHTML = _LANGUAGE['SKIN_EDIT'];
        actionDiv.appendChild(buttonEdit);

        var buttonDelete = document.createElement('button');

        buttonEdit.className = buttonEditGraph.className = buttonDelete.className = 'btn-white';


        buttonDelete.innerHTML = _LANGUAGE['SKIN_DELETE'];
        buttonDelete.onclick = function (e) {
            e.preventDefault();
            var btn = this;
            $(btn).button('loading');
            if (confirm(_LANGUAGE['SKIN_DELETECONFIRM'])) {
                $.ajax({
                    type: "POST",
                    url: "json/",
                    data: {method: "deleteSkins", skinList: [skinId]},
                    success: function (json) {
                        self.skinView.hide();
                        self.removeSkin(skinId);
                    }
                }).always(function () {
                    $(btn).button('reset');
                });
            } else {
                $(btn).button('reset');
            }
        };

        actionDiv.appendChild(buttonDelete);

        buttonEditGraph.innerHTML = _LANGUAGE['SKIN_EDIT_GRAPHICAL'];
        buttonEditGraph.href = 'retouch/skin/' + skinId + '/';
        actionDiv.appendChild(buttonEditGraph);
    }

    content.push(actionDiv);
    content.push(title);
    content.push(canvas);

    this.skinView.setContentDOM(content);

    var skinviewer = new SkinRender(canvas, "assets/skins/" + skinId + ".png", 0.9, false, "assets/skins/2D/" + skinId + ".png", this.display_3D);
    this.skinView.show();
};

SkinManager.prototype.addSkin = function (skinData) {
    if (skinData.id === undefined || skinData.title === undefined || skinData.description === undefined)
        return false;

    this.skinList[skinData.id] = skinData;
    this.skinContainer.innerHTML = "<img skin=\"" + skinData.id + "\" title=\"" + skinData.title + "\" alt=\"" + skinData.title + "\" src=\"assets/skins/2D/" + skinData.id + ".png\"/>" + this.skinContainer.innerHTML;
};

SkinManager.prototype.updateSkin = function (skinData) {
    if (skinData.id === undefined || skinData.title === undefined || skinData.description === undefined)
        return false;

    if (this.skinList[skinData.id] === undefined)
        return false;

    this.skinList[skinData.id] = skinData;
    for (var i in this.skinContainer.children) {
        if (this.skinContainer.children[i].nodeName == "IMG" && this.skinContainer.children[i].attributes.getNamedItem("skin").value == skinData.id) {
            this.skinContainer.children[i].title = skinData.title;
            break;
        }
    }
};

SkinManager.prototype.removeSkin = function (skinId) {
    if (this.skinList[skinId] === undefined)
        return;

    // this.skinList.splice(skinId, 1);
    // this.skinList.remove(skinId);
    delete this.skinList[skinId];
    for (var i in this.skinContainer.children) {
        if (this.skinContainer.children[i].nodeName == "IMG" && this.skinContainer.children[i].attributes.getNamedItem("skin").value == skinId) {
            this.skinContainer.children[i].style.display = "none";
            break;
        }
    }
};

SkinManager.prototype.search = function () {
    for (var i in this.skinContainer.children) {
        if (this.skinContainer.children[i].attributes === undefined)
            continue;

        if (this.skinContainer.children[i].attributes.getNamedItem("title").value.toLowerCase().indexOf(this.searcher.value.toLowerCase()) === -1)
            this.skinContainer.children[i].style.display = "none";
        else
            this.skinContainer.children[i].style.display = "inline-block";
    }
};

SkinManager.SWITCHMODE = {};
SkinManager.SWITCHMODE.SKINSWITCH = 1;
SkinManager.SWITCHMODE.MCSKINSAPI = 0;