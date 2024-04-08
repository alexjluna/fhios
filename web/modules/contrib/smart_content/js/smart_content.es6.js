/**
 * @file
 * Main client-side processing for smart content.
 */

(function (Drupal, drupalSettings) {

  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.plugin = Drupal.smartContent.plugin || {};
  Drupal.smartContent.plugin.ConditionType = Drupal.smartContent.plugin.ConditionType || {};
  Drupal.smartContent.plugin.Condition = Drupal.smartContent.plugin.Condition || {};
  Drupal.smartContent.plugin.Field = Drupal.smartContent.plugin.Field || {};
  Drupal.smartContent.instances = Drupal.smartContent || {};

  const defaultSmartContentInstanceId = 'main';

  let SmartContent = function (id) {
    if (typeof id === 'undefined') {
      id = defaultSmartContentInstanceId
    }
    this.id = id;

    let smartContentManager = this;

    /**
     * Manages segment promises.
     *
     * Provides a central means of storing segment promises so that redundant
     * processing of identical segments share the same segment instead of
     * processing separately.
     */
    this.segmentManager = {
      promises: {},
      register: function (segment) {
        if (!this.promises.hasOwnProperty(segment.uuid)) {
          let conditions = [];
          Object.keys(segment.conditions).forEach(key => {
            let promise = smartContentManager.processCondition(segment.conditions[key])
            conditions.push(promise);
          });

          this.promises[segment.uuid] = Promise.all(conditions).then(values => {
            for (let i = 0; i < values.length; i++) {
              if (!values[i]) {
                return false;
              }
            }
            return true;
          }).catch(err => {

          });
        }
        return this.promises[segment.uuid];
      },
      lookup: function (segment_id) {
        if (this.promises.hasOwnProperty(segment_id)) {
          return this.promises[segment_id];
        }
      }
    };

    /**
     * Manage condition field promises.
     *
     * Provides a central means for storing condition field promises so that
     * multiple conditions can depend on the same fields results.  This avoids
     * looking up the same field data multiple times.
     */
    this.conditionFieldManager = {
      promises: {},
      register: function (condition) {
        let unique = (condition.field.unique || condition.field.unique == 'true');
        if (!unique) {
          if (this.promises.hasOwnProperty(condition.field.pluginId)) {
            return this.promises[condition.field.pluginId];
          }
        }
        let promise = new Promise((resolve, reject) => {
          let result = false;
          // todo: allow match by plugin only.
          if (typeof Drupal.smartContent.plugin.Field[condition.field.pluginId] !== 'undefined') {
            result = Drupal.smartContent.plugin.Field[condition.field.pluginId](condition, smartContentManager);
          }
          else if (typeof Drupal.smartContent.plugin.Field[condition.field.pluginId.split(':')[0]] !== 'undefined') {
            result = Drupal.smartContent.plugin.Field[condition.field.pluginId.split(':')[0]](condition, smartContentManager);
          }
          else {
            result = false;
          }
          resolve(result);
        });
        if (!unique) {
          this.promises[condition.field.pluginId] = promise;
        }
        return promise;
      },
    };

    /**
     * Processes the segments.
     *
     * Iterates through segments settings and processes each segment.  Removes
     * the setting to avoid duplicate processing with subsequent ajax calls.
     */
    this.processSegments = function (segments) {
      Object.keys(segments).forEach(key => {
        const segmentSettings = Object.assign({}, segments[key]);
        delete segments[key];
        this.processSegment(segmentSettings)
      });
    };

    /**
     * Processes the segment.
     *
     * Registers the segment for processing in the segmentManager and returns a
     * promise.
     */
    this.processSegment = function (settings) {
      return this.segmentManager.register(settings)
    };

    /**
     * Processes the decisions.
     *
     * Iterates through decisions settings and processes each decision.  Removes
     * the setting to avoid duplicate processing with subsequent ajax calls.
     * Iterates through each reaction on each decision and waits for each
     * subsequent segment to finish processing.  First winner is then processed.
     * If no winner is found, then the default will be used if selected.
     */
    this.processDecisions = function (decisions) {
      Object.keys(decisions).forEach(async (key) => {
        const decision = Object.assign({}, decisions[key]);
        delete decisions[key];
        let reactions = decision.reactions;

        let winner;
        for (const i in reactions) {
          let result = await smartContentManager.segmentManager.lookup(reactions[i].id);
          if (result) {
            winner = reactions[i];
            break;
          }
        }
        if (winner) {
          // Winner found.
          this.processWinner(decision, winner);
        }
        else {
          // No winner found. Check if default is defined.
          if (decision.hasOwnProperty('default')) {
            this.processWinner(decision, reactions[decision.default], true);
          }
        }
      });
    };

    /**
     * Processes the condition.
     *
     * Registers the condition's field for processing in the
     * conditionFieldManager. Then evaluates the field and returns a promise for
     * the result.
     */
    this.processCondition = function (settings) {
      let fieldPromise = this.conditionFieldManager.register(settings);
      // todo: Because this waits for all fields to be satisfied, we cannot early
      // return group processing.
      return Promise.resolve(fieldPromise).then(function (value) {
        let result = false;
        if (typeof Drupal.smartContent.plugin.ConditionType[settings.field.type] !== 'undefined') {
          result = Drupal.smartContent.plugin.ConditionType[settings.field.type](settings, value, smartContentManager);
        }
        else if (typeof Drupal.smartContent.plugin.Condition[settings.field.pluginId] !== 'undefined') {
          result = Drupal.smartContent.plugin.Condition[settings.field.pluginId](settings, value, smartContentManager);
        }
        let negate = settings.settings.hasOwnProperty('negate') && settings.settings.negate == true;
        return negate ? !result : result;
      })
    };

    /**
     * Processes the winning reaction.
     *
     * Takes the winning reaction and processes it.  If contexts are defined,
     * handles processing the reaction with each context.  The decision is
     * required to handle reaction lookup.
     */
    this.processWinner = function (decision, winner, isDefault = false) {
      if (winner.hasOwnProperty('contexts')) {
        for (const i in winner.contexts) {
          let basePath = drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix;
          let q = Object.keys(winner.contexts[i]).map(function (key) {
            return '_sc_context_' + key + '=' + winner.contexts[i][key]
          }).join('&');
          let url = basePath + 'ajax/smart_content/' + decision.storage + '/' + decision.token + '/' + winner.id + '?' + q;
          Drupal.smartContent.ajax(url);

        }
      }
      else {
        let basePath = drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix;
        let url = basePath + 'ajax/smart_content/' + decision.storage + '/' + decision.token + '/' + winner.id;
        Drupal.smartContent.ajax(url);
      }

      // Broadcast the winner.
      let event = new CustomEvent('smart_content_decision', {
        detail: {
          winner: winner.id,
          default: isDefault,
          settings: drupalSettings.smartContent,
        },
      });
      window.dispatchEvent(event);
    }
  }

  /**
   * Initialize Smart Content processing based on settings.
   *
   * @param settings
   */
  Drupal.smartContent.init = function (settings, instance_id) {
    if (typeof instance_id === "undefined") {
      instance_id = defaultSmartContentInstanceId;
    }
    if(!Drupal.smartContent.instances.hasOwnProperty(instance_id)) {
      Drupal.smartContent.instances[instance_id] = new SmartContent(instance_id);
    }
    Drupal.smartContent.instances[instance_id].processSegments(settings.segments);
    Drupal.smartContent.instances[instance_id].processDecisions(settings.decisions);
  };

  /**
   * Wrapper for drupal core ajax.
   *
   * Wraps the drupal core ajax and provides a GET version, along with providing
   * jquery command handling on successful calls.
   *
   * @param url
   */
  Drupal.smartContent.ajax = function (url) {
    let ajaxObject = new Drupal.ajax({
      url: url,
      progress: false,
      async: true,
      success: function (response, status) {
        for (var i in response) {
          if (response.hasOwnProperty(i) && response[i].command && this.commands[response[i].command]) {
            this.commands[response[i].command](this, response[i], status);
          }
        }
      }
    });
    ajaxObject.options.type = "GET";
    ajaxObject.execute();
  }

  Drupal.smartContent.init(drupalSettings.smartContent);

  Drupal.behaviors.smartContent = {
    attach: function (context, settings) {
      if(context !== document) {
        if(settings.smartContent.hasOwnProperty('decisions') && settings.smartContent.hasOwnProperty('decisions')  && !(Object.keys(settings.smartContent.decisions).length === 0 && settings.smartContent.decisions.constructor === Object)) {
          Drupal.smartContent.init(settings.smartContent.smartContent);
        }
      }
    }
  }

})(Drupal, drupalSettings);
