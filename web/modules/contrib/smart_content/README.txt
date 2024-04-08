CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Glossary
 * Configuration
 * Security
 * Maintainers


INTRODUCTION
------------

Smart Content is a toolset to enable real-time, anonymous website
personalization on any Drupal 8 website. Out of the box, it allows site
administrators to display different content for anonymous or authenticated users
based on browser conditions.

  * For a full description of the module, visit the project page:
    https://www.drupal.org/project/smart_content

  * To submit bug reports and feature suggestions, or to track changes:
    https://www.drupal.org/project/issues/smart_content


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

  * Smart Content Datalayer
  (https://www.drupal.org/project/smart_content_datalayer)
  Collects data from smart content displayed on a page and provides it to analytics
  platforms like Google Tag Manager via the dataLayer object.


INSTALLATION
------------

  * Install as you would normally install a contributed Drupal module.
  Visit:
  https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
  for further information.

  * We recommend you install and enable Smart Content Block and Smart Content
  Browser, both included as sub-modules, to better experience this module's
  capabilities out of the box.


GLOSSARY
--------

  * Condition: A single case that can be tested and determined to be either true
  or false.

  * Group: Conditions can be combined and evaluated using an 'and' or 'or' operator.

  * Segment: One or multiple conditions that represent defining characteristics of a
  user group when they evaluate true. A Segment can require that either all or any of
  the conditions within it evaluate true for the Segment itself to evaluate true.

  * Segment Set: One or multiple Segments that represent all user groups a personalization
  campaign will target.

  * Reaction: A response to a Segment within a Segment Set evaluating true. Out of the box,
  the content administrator can select a block to display when their Segment evaluates true.

  * Decision Block: A block, defined by the Smart Content Block sub-module, that displays
  blocks based on whether the Segments associated with them evaluate true.

CONFIGURATION
-------------

Smart Content does not require any initial configuration until you are ready to
begin creating and placing Decision Blocks. To configure a Decision Block, follow
these steps:

  1. Navigate to the Block Layout page (admin/structure/block) and click the
  'Place block' button next to the region you would like to place a Decision Block
  in.

  2. Find 'Decision Block' in the list of blocks and click the 'Place block' button.

  3. In the Segment settings fieldset, choose an existing Segment Set, or create
  a custom segment set that will only be available in this Decision Block.

  4. For each Segment within the Segment Set, choose a condition and then choose a
  block that should display when that condition(s) evaluate true. Be sure to click
  the "Add Block" button to select your chosen block as part of the Segment.

  5. Use the "Set as default segment" checkbox to choose whether a segment should
  be displayed regardless of how the condition evaluates.

  6. Define any other visibility options you require in the vertical tabs at the
  bottom of the block edit form.

  7. Save the block and place it in a region that is being rendered by your
  theme's templates.

To add a Segment Set that can be re-used around the site follow these steps:

  1. Navigate to Structure -> Smart Content -> Manage Segment Sets in the admin
  toolbar menu.

  2. Click the "Add Global Segment Set" button.

  3. Add all of the Segments and Conditions that should be part of the Global Segment
  Set and click "Save."

To use a Global Segment Set, add a Decision Block and choose the the Global Segment
Set from the segment set select list in the "Segment settings" fieldset.


SECURITY
--------

Smart Content is NOT intended to be used as a substitute for serverside access
control. Conditions are evaluated clientside and can be viewed, changed or
manipulated by someone with the right knowledge and skillset. This module is
primarily focused on improving user experience and providing additional
contextual conditions for displaying content, not restricting access through
secure means or evaluating conditions that contain personally identifiable
information.


MAINTAINERS
-----------

Current maintainers:

  * Michael Lander (michaellander); Primary Developer
  https://www.drupal.org/u/michaellander

  * Gurwinder Antal (gantal); Developer
  https://www.drupal.org/u/gantal


This project has been sponsored by:

  * Elevated Third
    Empowering B2B marketing ecosystems with strategic thinking, top-notch user
    experience design and world-class Drupal development.
