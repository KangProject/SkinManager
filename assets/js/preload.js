String.prototype.replaceAll = function (match, pattern) {
    var result = this;
    while (result.match(match) !== null) {
        result = result.replace(match, pattern);
    }
    return result;
};

String.prototype.htmlsanitise = function () {
    var result = this;
    result = result.replaceAll('<', '&lt;');
    result = result.replaceAll('>', '&gt;');
    result = result.replaceAll("\n", '<br>');
    result = result.replace(/(https|ftp|http):\/\/([a-zA-Z0-9-_\-\/\?\=\\.]*)/g, '<a href="$1:\/\/$2">$1:\/\/$2</a>');
    result = result.replace(/(#......)/g, '<span style="color: $1">$1</span>');
    return result;
};

String.prototype.repeat = function (count) {
    var result = '';
    var pattern = this;
    while (count > 0) {
        if (count & 1) result += pattern;
        count >>= 1, pattern += pattern;
    }
    return result;
};

String.prototype.trunc = function (n) {
    return this.substr(0, n - 1) + (this.length > n ? '&hellip;' : '');
};

Array.prototype.remove = function () {
    var what, a = arguments, L = a.length, ax;
    while (L && this.length) {
        what = a[--L];
        while ((ax = this.indexOf(what)) !== -1) {
            this.splice(ax, 1);
        }
    }
    return this;
};