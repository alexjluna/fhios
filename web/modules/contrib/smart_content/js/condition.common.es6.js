/**
 * @file
 * Gets condition values for conditions provided by Smart Content.
 */

(function (Drupal) {
  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.plugin = Drupal.smartContent.plugin || {};
  Drupal.smartContent.plugin.Field = Drupal.smartContent.plugin.Field || {};
  Drupal.smartContent.plugin.Condition = Drupal.smartContent.plugin.Condition || {};

  let promiseRaceMatch = function (promises, match) {
    if (promises.length < 1) {
      return false;
    }

    // There is no way to know which promise is false, so we map it to a new
    // promise to return the index when it fails.
    let indexPromises = promises.map((p, index) => p.then(() => index));

    return Promise.race(indexPromises).then(async(index) => {
      let p = promises.splice(index, 1)[0];
      let result = await p;
      if (result === match) {
        return p;
      }
      else {
        if (promises.length < 1) {
          return !match;
        }
        return promiseRaceMatch(promises, match);
      }
    });
  };

  Drupal.smartContent.plugin.Field['group'] = function (condition, smartContentManager) {
    let conditions = [];
    // todo: how to handle rejection? early returns/processing?
    Object.keys(condition.conditions).forEach(key => {
      let promise = smartContentManager.processCondition(condition.conditions[key]);
      conditions.push(promise);
    });
    // If group is OR, early return as soon as a 'true' match comes back.
    if (condition.settings.op == 'OR') {
      return promiseRaceMatch(conditions, true);
    }
    // If group is AND, early return as soon as a false match comes back.
    return promiseRaceMatch(conditions, false);
  };



  Drupal.smartContent.plugin.Condition['group'] = function (condition, value) {
    return value;
  };


  Drupal.smartContent.plugin.Field['is_true'] = function (condition) {
    return true;
  }

  Drupal.smartContent.plugin.Condition['is_true'] = function (condition) {
    return true;
  }

})(Drupal);
