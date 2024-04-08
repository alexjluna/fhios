/**
 * @file
 * Wrapper for storing data in local storage.
 */

(function (Drupal) {

  let browserStorage = function () {
    this.bin = JSON.parse(window.localStorage.getItem('_scs'));
    if (this.bin === null) {
      window.localStorage.setItem('_scs', JSON.stringify({}));
    }
    this.bin = JSON.parse(window.localStorage.getItem('_scs'));
  };

  browserStorage.prototype.getValue = function (name, bin) {
    bin = bin !== undefined ? bin : 'default';
    return (this.bin.hasOwnProperty(bin) && this.bin[bin].hasOwnProperty(name) && this.bin[bin][name].hasOwnProperty('value') ? this.bin[bin][name].value : null);
  };

  browserStorage.prototype.getItem = function (name, bin) {
    bin = bin !== undefined ? bin : 'default';
    return (this.bin.hasOwnProperty(bin) && this.bin[bin].hasOwnProperty(name) ? this.bin[bin][name] : null);
  };

  browserStorage.prototype.setValue = function (name, value, maxAge, bin) {
    maxAge = maxAge !== undefined ? maxAge : 50000;

    bin = bin !== undefined ? bin : 'default';

    if (maxAge === -1) {
      maxAge = 50 * 365 * 24 * 60 * 60 * 1000;
    }

    let expiration = new Date(Date.now() + maxAge);
    this.bin[bin] = this.bin[bin] || {};
    this.bin[bin][name] = {
      name: name,
      value: value,
      expiration: expiration.toUTCString(),
    };

    window.localStorage.setItem('_scs', JSON.stringify(this.bin));
  };

  browserStorage.prototype.isExpired = function (name, bin) {
    var value = this.getItem(name, bin);
    if (value !== null) {
      return new Date() > new Date(value.expiration);
    }
    return true;
  };

  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.storage = new browserStorage;

})(Drupal);
