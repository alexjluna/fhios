/**
 * @file
 * Client-side evaluation for all condition types provided by Smart Content.
 */

(function (Drupal) {

  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.plugin = Drupal.smartContent.plugin || {};
  Drupal.smartContent.plugin.ConditionType = Drupal.smartContent.plugin.ConditionType || {};

  Drupal.smartContent.plugin.ConditionType['type:textfield'] = function (condition, value) {
    let context = value || '';
    switch (condition.settings['op']) {
      case 'equals':
        return (value != null) && (String(context).toLowerCase() === condition.settings['value'].toLowerCase());

      case 'contains':
        return (value != null) && (String(context).toLowerCase().includes(condition.settings['value'].toLowerCase()));

      case 'starts_with':
        return (value != null) && (String(context).toLowerCase().substring(0, condition.settings['value'].length) === condition.settings['value'].toLowerCase());

      case 'empty':
        return (value === null) || (context.length === 0);
    }
    return false;
  };

  Drupal.smartContent.plugin.ConditionType['type:key_value'] = function (condition, value) {
    let context = value || '';
    switch (condition.settings['op']) {
      case 'equals':
        return (value != null) && (String(context).toLowerCase() === condition.settings['value'].toLowerCase());

      case 'contains':
        return (value != null) && (String(context).toLowerCase().includes(condition.settings['value'].toLowerCase()));

      case 'starts_with':
        return (value != null) && (String(context).toLowerCase().substring(0, condition.settings['value'].length) === condition.settings['value'].toLowerCase());

      case 'empty':
        return (value === null) || (context.length === 0);

      case 'is_set':
        return (value !== null);
    }
    return false;
  };

  Drupal.smartContent.plugin.ConditionType['type:boolean'] = function (condition, value) {
    return Boolean(value);
  };

  Drupal.smartContent.plugin.ConditionType['type:number'] = function (condition, value) {
    switch (condition.settings['op']) {
      case 'equals':
        return (value != null) && (Number(value) === Number(condition.settings['value']));

      case 'gt':
        return (value != null) && (Number(value) > Number(condition.settings['value']));

      case 'lt':
        return (value != null) && (Number(value) < Number(condition.settings['value']));

      case 'gte':
        return (value != null) && (Number(value) >= Number(condition.settings['value']));

      case 'lte':
        return (value != null) && (Number(value) <= Number(condition.settings['value']));
    }
    return false;
  };

  Drupal.smartContent.plugin.ConditionType['type:value'] = function (condition, value) {
    return value;
  };

  Drupal.smartContent.plugin.ConditionType['type:select'] = function (condition, value) {
    return condition.settings['value'] === value;
  };

  Drupal.smartContent.plugin.ConditionType['type:array_textfield'] = function (condition, value) {
    let context = value || '';
    switch (condition.settings['op']) {
      case 'contains_equals':
        if (Array.isArray(context) && context.length > 0) {
          for (var i = 0, len = context.length; i < len; i++) {
            if (String(context[i]).toLowerCase() === condition.settings['value'].toLowerCase()) {
              return true;
            }
          }
        }
        break;

      case 'contains_contains':
        if (Array.isArray(context) && context.length > 0) {
          for (var i = 0, len = context.length; i < len; i++) {
            if (String(context[i]).toLowerCase().includes(condition.settings['value'].toLowerCase())) {
              return true;
            }
          }
        }
        break;

      case 'contains_starts_with':
        if (Array.isArray(context) && context.length > 0) {
          for (var i = 0, len = context.length; i < len; i++) {
            if (String(context[i]).toLowerCase().substring(0, condition.settings['value'].length) === condition.settings['value'].toLowerCase()) {
              return true;
            }
          }
        }
        break;

      case 'empty':
        return (value === null) || !Array.isArray(context) || (context.length === 0);
    }
    return false;
  };

})(Drupal);
